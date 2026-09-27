<?php

namespace Elibrary\Lms\Support;

/**
 * Turns a pasted YouTube, Vimeo or Bunny Stream link into the player to embed.
 * Only these hosts are recognised, and only their players are allowed by the
 * site's security policy (see SecurityHeaders).
 */
class VideoEmbed
{
    public const WATCHED_TO_COMPLETE = 90;

    /** Hosts whose players may be framed, and whose player APIs may load. */
    public const FRAME_HOSTS = ['https://www.youtube-nocookie.com', 'https://www.youtube.com', 'https://player.vimeo.com', 'https://iframe.mediadelivery.net'];

    public const SCRIPT_HOSTS = ['https://www.youtube.com', 'https://player.vimeo.com', 'https://assets.mediadelivery.net'];

    /** @return array{provider: string, label: string, embed_url: string}|null */
    public static function parse(?string $url): ?array
    {
        if (! $url) {
            return null;
        }

        $parts = parse_url(trim($url));
        $host = strtolower($parts['host'] ?? '');
        $host = preg_replace('/^(www\.|m\.)/', '', $host);
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            $id = match (true) {
                $host === 'youtu.be' => trim($path, '/'),
                str_starts_with($path, '/embed/'), str_starts_with($path, '/shorts/'), str_starts_with($path, '/live/') => explode('/', trim($path, '/'))[1] ?? null,
                default => $query['v'] ?? null,
            };

            return self::valid($id, '/^[A-Za-z0-9_-]{11}$/') ? [
                'provider' => 'youtube',
                'label' => 'YouTube',
                'embed_url' => "https://www.youtube-nocookie.com/embed/{$id}?rel=0&modestbranding=1&enablejsapi=1&playsinline=1",
            ] : null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            // vimeo.com/123456789, vimeo.com/123456789/abcdef (unlisted), player.vimeo.com/video/123456789?h=abcdef
            $segments = array_values(array_filter(explode('/', $path)));
            if (($segments[0] ?? null) === 'video') {
                array_shift($segments);
            }
            $id = $segments[0] ?? null;
            $hash = $query['h'] ?? ($segments[1] ?? null);

            return self::valid($id, '/^\d{4,12}$/') ? [
                'provider' => 'vimeo',
                'label' => 'Vimeo',
                'embed_url' => "https://player.vimeo.com/video/{$id}?dnt=1".(self::valid($hash, '/^[A-Za-z0-9]{6,20}$/') ? "&h={$hash}" : ''),
            ] : null;
        }

        if (in_array($host, ['iframe.mediadelivery.net', 'video.bunnycdn.com'], true)) {
            // iframe.mediadelivery.net/embed/{library}/{video} or /play/{library}/{video}
            $segments = array_values(array_filter(explode('/', $path)));
            [$kind, $library, $video] = array_pad($segments, 3, null);

            return in_array($kind, ['embed', 'play'], true) && self::valid($library, '/^\d{1,10}$/') && self::valid($video, '/^[0-9a-f-]{36}$/i') ? [
                'provider' => 'bunny',
                'label' => 'Bunny Stream',
                'embed_url' => "https://iframe.mediadelivery.net/embed/{$library}/".strtolower($video).'?autoplay=false&preload=true&responsive=true',
            ] : null;
        }

        return null;
    }

    private static function valid(?string $value, string $pattern): bool
    {
        return is_string($value) && preg_match($pattern, $value) === 1;
    }
}
