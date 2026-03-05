<?php

namespace App\Services;

class YouTubeService
{
    /**
     * Extract the YouTube video ID from any YouTube URL format.
     * Supports: youtube.com/watch?v=, youtu.be/, youtube.com/embed/, youtube.com/shorts/
     *
     * @param string $url
     * @return string|null
     */
    public static function extractVideoId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    /**
     * Check if the given URL is a YouTube URL.
     *
     * @param string $url
     * @return bool
     */
    public static function isYouTubeUrl(string $url): bool
    {
        return (bool) preg_match('/(?:youtube\.com|youtu\.be)/i', $url);
    }

    /**
     * Build an embeddable YouTube URL from a video ID.
     *
     * @param string $videoId
     * @param array $params  Optional query parameters (e.g. ['autoplay' => 1])
     * @return string
     */
    public static function buildEmbedUrl(string $videoId, array $params = []): string
    {
        $base = "https://www.youtube.com/embed/{$videoId}";
        if (!empty($params)) {
            $base .= '?' . http_build_query($params);
        }
        return $base;
    }

    /**
     * Get the thumbnail URL for a given YouTube video ID.
     *
     * @param string $videoId
     * @param string $quality  'default', 'hqdefault', 'mqdefault', 'sddefault', 'maxresdefault'
     * @return string
     */
    public static function getThumbnailUrl(string $videoId, string $quality = 'hqdefault'): string
    {
        return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
    }
}
