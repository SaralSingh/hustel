<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Room;

class RoomController extends Controller
{
    // Create room
    public function create(Request $request)
    {
        $request->validate(['m3u8_url' => 'required|string']);
        $room = Room::create(['m3u8_url' => $request->m3u8_url]);
        return response()->json([
            'room_id' => $room->id,
            'access_key' => $room->access_key,
            'url' => route('room.show', $room->id) // We'll make this route
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

        return response()->json(['m3u8_url' => $room->m3u8_url]);
    }
}
