<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Services\AdBlockService;

class RoomController extends Controller
{
    // Create room
    public function create(Request $request)
    {
        $request->validate([
            'm3u8_url'    => 'required|string',
            'referer_url' => 'nullable|string'
        ]);
        $room = Room::create([
            'm3u8_url'    => $request->m3u8_url,
            'referer_url' => $request->referer_url
        ]);
        return response()->json([
            'room_id'    => $room->id,
            'access_key' => $room->access_key,
        ]);
    }

    // Render the main video room view (validates key, then renders Blade)
    public function roomView(Request $request, $id)
    {
        $room = Room::findOrFail($id);
        $key  = $request->query('key');

        if ($key !== $room->access_key) {
            abort(403, 'Invalid room key.');
        }

        // If 'uid' param is present, this is an APPROVED VIEWER — not the host.
        // The admin (host) arrives here without a uid param.
        $uid     = $request->query('uid');
        $isAdmin = ($uid === null || $uid === '');

        // Log in a guest user for presence channel authentication
        if (!\Illuminate\Support\Facades\Auth::check()) {
            $username = $request->query('name', $isAdmin ? 'Admin (Host)' : 'Viewer');
            $user = \App\Models\User::firstOrCreate(
                ['email' => session()->getId() . '@guest.com'],
                ['name'  => $username, 'password' => bcrypt('password')]
            );
            $user->name = $username;
            $user->save();
            \Illuminate\Support\Facades\Auth::login($user);
        }

        return view('room', [
            'room'     => $room,
            'roomId'   => $room->id,
            'key'      => $room->access_key,
            'joinUrl'  => url("/join/{$room->id}"),
            'isAdmin'  => $isAdmin,
        ]);
    }

    // Render the waiting / join-request page (public — no key needed)
    public function waitingView($id)
    {
        $room = Room::findOrFail($id);
        return view('waiting', [
            'roomId'  => $room->id,
            'roomKey' => $room->access_key,   // sent back via WebSocket on approval
        ]);
    }

    // Join room (validate key, get stream URL)
    public function show($id, Request $request)
    {
        $room = Room::findOrFail($id);
        if ($request->key !== $room->access_key) {
            abort(403, 'Invalid key');
        }

        if (!\Illuminate\Support\Facades\Auth::check()) {
            $username = $request->query('username', 'Anon');
            $user = \App\Models\User::firstOrCreate(
                ['email' => session()->getId() . '@guest.com'],
                ['name' => $username, 'password' => bcrypt('password')]
            );
            \Illuminate\Support\Facades\Auth::login($user);
        }

        return response()->json([
            'm3u8_url' => $room->m3u8_url,
            'referer_url' => $room->referer_url
        ]);
    }

    // Proxy video buffer to bypass CORS/Referer/User-Agent restrictions
    public function proxyVideo(Request $request)
    {
        $url = $request->query('url');
        if (!$url) return abort(404);

        $client = new \GuzzleHttp\Client();

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        // Derive Origin and Referer. If it's a CDN link for hakunaymatata, the real referer is likely the main site.
        $origin = 'https://' . $host;
        $referer = 'https://' . $host . '/';

        if (str_contains($host, 'hakunaymatata.com')) {
            $origin = 'https://hakunaymatata.com';
            $referer = 'https://hakunaymatata.com/';
        }

        // Use explicitly provided referer if available
        $explicitReferer = $request->query('referer');
        if ($explicitReferer) {
            $referer = $explicitReferer;
            $explicitParsed = parse_url($explicitReferer);
            $origin = ($explicitParsed['scheme'] ?? 'https') . '://' . ($explicitParsed['host'] ?? '');
        }

        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'Referer' => $referer,
            'Origin' => $origin,
            'Accept' => '*/*',
            'Connection' => 'keep-alive',
        ];

        // Forward Range header for video seeking
        if ($request->hasHeader('Range')) {
            $headers['Range'] = $request->header('Range');
        }

        try {
            // Close the session to prevent request blocking for long video streams
            session_write_close();

            $response = $client->request('GET', $url, [
                'headers' => $headers,
                'stream' => true,
                'verify' => false,
                'http_errors' => false,
            ]);

            $responseHeaders = [];
            foreach ($response->getHeaders() as $name => $values) {
                $lowerName = strtolower($name);
                // We shouldn't forward chunked transfer-encoding because laravel stream will chunk it again
                if (!in_array($lowerName, ['transfer-encoding', 'connection'])) {
                    $responseHeaders[$name] = implode(', ', $values);
                }
            }

            $responseHeaders['Access-Control-Allow-Origin'] = '*';

            // Clean any existing output buffers to prevent memory exhaustion
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            return response()->stream(function () use ($response) {
                // Ensure implicit flush is on so data sends immediately
                ob_implicit_flush(true);

                $body = $response->getBody();
                while (!$body->eof()) {
                    echo $body->read(1024 * 256); // Stream in 256kb chunks for high-res video
                    if (ob_get_length()) {
                        ob_flush();
                    }
                    flush();
                }
            }, $response->getStatusCode(), $responseHeaders);
        } catch (\Exception $e) {
            return abort(500, 'Proxy error: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a remote m3u8 playlist, strip ad segments via AdBlockService,
     * and return the cleaned playlist to the client.
     *
     * GET /clean-playlist?url=<encoded_m3u8_url>[&referer=<encoded_referer>]
     */
    public function cleanPlaylist(Request $request)
    {
        $url = $request->query('url');
        if (!$url) return abort(400, 'Missing url parameter');

        // Build forwarding headers
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121 Safari/537.36',
        ];

        $referer = $request->query('referer');
        if ($referer) {
            $headers['Referer'] = $referer;
            $parsed = parse_url($referer);
            $headers['Origin'] = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
        } else {
            $parsed = parse_url($url);
            $host   = $parsed['host'] ?? '';
            $headers['Referer'] = 'https://' . $host . '/';
            $headers['Origin']  = 'https://' . $host;
        }

        try {
            $cleanedPlaylist = AdBlockService::fetchAndClean($url, $headers);

            return response($cleanedPlaylist, 200, [
                'Content-Type'                => 'application/vnd.apple.mpegurl',
                'Access-Control-Allow-Origin' => '*',
                'Cache-Control'               => 'no-store',
            ]);
        } catch (\Exception $e) {
            return abort(500, 'Ad-block proxy error: ' . $e->getMessage());
        }
    }
}
