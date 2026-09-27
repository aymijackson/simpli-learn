<?php

namespace App\Http\Controllers;

use App\Support\Monitoring\HealthCheck;
use Illuminate\Http\JsonResponse;

/**
 * For uptime monitors: 200 when everything the platform needs is working,
 * 503 (with which check failed) when not.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = HealthCheck::run();
        $healthy = HealthCheck::healthy($checks);

        return response()->json([
            'status' => $healthy ? 'ok' : 'failing',
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503)->header('Cache-Control', 'no-store');
    }
}
