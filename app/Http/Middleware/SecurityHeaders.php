<?php

namespace App\Http\Middleware;

use Closure;
use Elibrary\Lms\Support\VideoEmbed;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser security headers on every web response.
 *
 * The Content-Security-Policy is sent in report-only mode by default:
 * browsers report anything it *would* block to /csp-report (logged to
 * storage/logs/csp.log) without breaking the page. Once the log stays
 * quiet under real traffic, set CSP_ENFORCE=true to enforce it.
 * frame-ancestors is always enforced (clickjacking protection).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => self::permissions($request),
        ];

        if (config('security.csp.enforce')) {
            $headers['Content-Security-Policy'] = self::policy();
        } else {
            $headers['Content-Security-Policy'] = "frame-ancestors 'self'";
            $headers['Content-Security-Policy-Report-Only'] = self::policy();
        }

        // Only promise HTTPS to browsers that already reached us over HTTPS,
        // so a site still on plain HTTP doesn't lock itself out.
        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }

    /** Pages that may use the camera: the webcam check before a proctored exam, and the exam itself. */
    private const CAMERA_ROUTES = ['cbt.exams.show', 'cbt.attempts.take', 'cbt.attempts.questions.show'];

    private static function permissions(Request $request): string
    {
        $camera = $request->routeIs(...self::CAMERA_ROUTES) ? 'camera=(self)' : 'camera=()';

        return "{$camera}, microphone=(), geolocation=(), payment=(self)";
    }

    public static function policy(): string
    {
        $cdn = 'https://cdn.jsdelivr.net';
        $videoFrames = implode(' ', VideoEmbed::FRAME_HOSTS);
        $videoScripts = implode(' ', VideoEmbed::SCRIPT_HOSTS);
        // Cloudflare Turnstile, only when the contact form uses it.
        $turnstile = config('services.turnstile.site_key') ? ' https://challenges.cloudflare.com' : '';

        return implode('; ', [
            "default-src 'self'",
            // Tailwind's browser build, Trix, pdf.js, epub.js and the QR code library come from jsDelivr;
            // small inline scripts remain in a few views (theme guard, exam timer, integrity monitor).
            "script-src 'self' 'unsafe-inline' {$cdn} {$videoScripts}{$turnstile}",
            "style-src 'self' 'unsafe-inline' {$cdn} https://fonts.bunny.net",
            // Bunny Fonts serves the font files themselves from jsDelivr.
            "font-src 'self' data: https://fonts.bunny.net {$cdn}",
            "img-src 'self' data: blob: https:",
            "media-src 'self' blob:",
            "connect-src 'self' {$cdn}",
            // pdf.js wraps its CDN worker in a blob:, epub.js renders chapters in blob: iframes.
            "worker-src 'self' blob:",
            // Lesson videos: only the YouTube, Vimeo and Bunny Stream players.
            "frame-src 'self' blob: {$videoFrames}{$turnstile}",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            'report-uri '.route('csp.report', [], false),
        ]);
    }
}
