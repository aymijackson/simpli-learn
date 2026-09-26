<?php

namespace App\Http\Middleware;

use Closure;
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
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(self)',
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

    public static function policy(): string
    {
        $cdn = 'https://cdn.jsdelivr.net';

        return implode('; ', [
            "default-src 'self'",
            // Tailwind's browser build, Trix, pdf.js, epub.js and the QR code library come from jsDelivr;
            // small inline scripts remain in a few views (theme guard, exam timer, integrity monitor).
            "script-src 'self' 'unsafe-inline' {$cdn}",
            "style-src 'self' 'unsafe-inline' {$cdn} https://fonts.bunny.net",
            "font-src 'self' data: https://fonts.bunny.net",
            "img-src 'self' data: blob: https:",
            "media-src 'self' blob:",
            "connect-src 'self' {$cdn}",
            // pdf.js wraps its CDN worker in a blob:, epub.js renders chapters in blob: iframes.
            "worker-src 'self' blob:",
            "frame-src 'self' blob:",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            'report-uri '.route('csp.report', [], false),
        ]);
    }
}
