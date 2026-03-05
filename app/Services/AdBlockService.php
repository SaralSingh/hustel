<?php

namespace App\Services;

class AdBlockService
{
    /**
     * Known ad-serving CDN domains / URL patterns found in m3u8 playlists.
     * Segments whose URL matches any of these are treated as ads and removed.
     */
    private static array $adDomains = [
        'doubleclick.net',
        'googlesyndication.com',
        'googleadservices.com',
        'ads.youtube.com',
        'imasdk.googleapis.com',
        'adnxs.com',
        'adobedtm.com',
        'moatads.com',
        'scorecardresearch.com',
        'advertising.com',
        'adsystem.amazon.com',
        'ad.doubleclick.net',
        'securepubads.g.doubleclick.net',
        'pubads.g.doubleclick.net',
        '2mdn.net',
        'cdn.springserve.com',
        'ads.spotxchange.com',
        'spotx.tv',
        'freewheel.tv',
        'adswizz.com',
        'jwpltx.com',         // JW Player ad tracking
        'yieldex.com',
        'imrworldwide.com',   // Nielsen
        'adsafeprotected.com',
        'amazon-adsystem.com',
        'media.net',
    ];

    /**
     * SCTE-35 / HLS ad-marker tags. Segments bracketed by these are ads.
     */
    private static array $adMarkerTags = [
        '#EXT-X-CUE-OUT',
        '#EXT-X-CUE-IN',
        '#EXT-X-AD-CUE',
        '#EXT-X-DATERANGE',         // Sometimes used for ad cues
        '#EXT-OATCLS-SCTE35',
        '#EXT-X-SCTE35',
        '#EXT-X-ASSET',
        '#EXT-X-CUE',
    ];

    /**
     * Clean an m3u8 playlist string by removing detected ad segments.
     *
     * Strategy:
     *  1. Remove segments between SCTE-35 / CUE-OUT … CUE-IN markers.
     *  2. Remove individual #EXTINF + URI lines whose URI matches a known ad domain.
     *  3. Remove stray #EXT-X-DISCONTINUITY tags that now border gaps we created.
     *
     * @param string $playlist  Raw m3u8 text
     * @param string $baseUrl   Base URL of the playlist file (to resolve relative URIs)
     * @return string           Cleaned m3u8 text
     */
    public static function cleanPlaylist(string $playlist, string $baseUrl = ''): string
    {
        $lines = explode("\n", str_replace("\r\n", "\n", $playlist));
        $output        = [];
        $skipSegment   = false;    // Inside a CUE-OUT … CUE-IN block
        $pendingExtinf = null;     // Buffered #EXTINF line waiting for its URI

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // ── SCTE-35 / CUE-OUT: start skipping ──────────────────────────
            if (self::startsWithAny($trimmed, ['#EXT-X-CUE-OUT', '#EXT-OATCLS-SCTE35', '#EXT-X-SCTE35', '#EXT-X-AD-CUE'])) {
                $skipSegment = true;
                continue;
            }

            // ── CUE-IN: stop skipping ───────────────────────────────────────
            if (str_starts_with($trimmed, '#EXT-X-CUE-IN')) {
                $skipSegment = false;
                continue;
            }

            // ── Skip everything inside a CUE-OUT block ──────────────────────
            if ($skipSegment) {
                continue;
            }

            // ── Buffer the #EXTINF so we can inspect the next URI ───────────
            if (str_starts_with($trimmed, '#EXTINF:')) {
                $pendingExtinf = $line;
                continue;
            }

            // ── Process URI lines (non-comment, non-empty) ──────────────────
            if ($pendingExtinf !== null && $trimmed !== '' && !str_starts_with($trimmed, '#')) {
                // Resolve relative URIs for domain checking
                $fullUri = self::resolveUri($trimmed, $baseUrl);

                if (self::isAdUrl($fullUri)) {
                    // Drop both the buffered #EXTINF and this URI
                    $pendingExtinf = null;
                    continue;
                }

                // Not an ad — keep both lines
                $output[] = $pendingExtinf;
                $pendingExtinf = null;
                $output[] = $line;
                continue;
            }

            // ── Flush pending extinf if we hit another tag (not a URI) ───────
            if ($pendingExtinf !== null && str_starts_with($trimmed, '#')) {
                $output[] = $pendingExtinf;
                $pendingExtinf = null;
            }

            // ── Regular tag / comment / blank line ─────────────────────────
            $output[] = $line;
        }

        // Clean up double #EXT-X-DISCONTINUITY tags (artefacts of ad removal)
        $output = self::removeDoubleDiscontinuity($output);

        return implode("\n", $output);
    }

    /**
     * Fetch and clean a remote m3u8 playlist, rewriting relative segment
     * URLs to absolute so the frontend can load them via the proxy.
     *
     * @param string $url      Full URL of the m3u8 file
     * @param array  $headers  Extra HTTP headers to pass (Referer, Origin, etc.)
     * @return string          Cleaned m3u8 with absolute segment URLs
     */
    public static function fetchAndClean(string $url, array $headers = []): string
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url, [
            'headers'     => array_merge([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/121 Safari/537.36',
            ], $headers),
            'verify'      => false,
            'http_errors' => false,
            'timeout'     => 15,
        ]);

        $body = (string) $response->getBody();

        // Rewrite relative segment URIs to absolute before cleaning
        $base = self::getBaseUrl($url);
        $body = self::rewriteRelativeUris($body, $base);

        return self::cleanPlaylist($body, $url);
    }

    // ── Private Helpers ───────────────────────────────────────────────────────

    private static function isAdUrl(string $url): bool
    {
        $urlLower = strtolower($url);
        foreach (self::$adDomains as $domain) {
            if (str_contains($urlLower, $domain)) {
                return true;
            }
        }
        // Heuristic: segment URI contains "ad", "preroll", "midroll", "postroll"
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        if (preg_match('/\/(preroll|midroll|postroll|linear-ad|ad-segment|adbreak)\//i', $path)) {
            return true;
        }
        return false;
    }

    private static function startsWithAny(string $str, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($str, $prefix)) return true;
        }
        return false;
    }

    private static function resolveUri(string $uri, string $baseUrl): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }
        if (str_starts_with($uri, '/')) {
            $parsed = parse_url($baseUrl);
            return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $uri;
        }
        return rtrim($baseUrl, '/') . '/' . $uri;
    }

    private static function getBaseUrl(string $url): string
    {
        $parts = explode('/', $url);
        array_pop($parts); // remove filename
        return implode('/', $parts);
    }

    /**
     * Rewrite relative segment/playlist URIs inside an m3u8 to absolute.
     */
    private static function rewriteRelativeUris(string $body, string $baseUrl): string
    {
        $lines = explode("\n", $body);
        foreach ($lines as &$line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (!str_starts_with($trimmed, 'http://') && !str_starts_with($trimmed, 'https://')) {
                $line = self::resolveUri($trimmed, $baseUrl);
            }
        }
        return implode("\n", $lines);
    }

    /**
     * Remove consecutive #EXT-X-DISCONTINUITY tags left after ad removal.
     */
    private static function removeDoubleDiscontinuity(array $lines): array
    {
        $result = [];
        $lastWasDiscontinuity = false;
        foreach ($lines as $line) {
            $isDiscontinuity = trim($line) === '#EXT-X-DISCONTINUITY';
            if ($isDiscontinuity && $lastWasDiscontinuity) {
                continue; // drop duplicate
            }
            $result[]             = $line;
            $lastWasDiscontinuity = $isDiscontinuity;
        }
        return $result;
    }
}
