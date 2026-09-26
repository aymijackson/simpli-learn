<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Receives Content-Security-Policy violation reports from browsers and logs
 * a compact line per violation to storage/logs/csp.log.
 */
class CspReportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $report = $payload['csp-report'] ?? $payload[0]['body'] ?? $payload;

        if (is_array($report) && $report !== []) {
            Log::channel('csp')->warning('CSP violation', array_filter([
                'page' => $report['document-uri'] ?? $report['documentURL'] ?? null,
                'directive' => $report['violated-directive'] ?? $report['effectiveDirective'] ?? null,
                'blocked' => $report['blocked-uri'] ?? $report['blockedURL'] ?? null,
                'source' => $report['source-file'] ?? $report['sourceFile'] ?? null,
                'line' => $report['line-number'] ?? $report['lineNumber'] ?? null,
            ]));
        }

        return response()->noContent();
    }
}
