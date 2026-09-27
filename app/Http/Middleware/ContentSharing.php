<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Turns visibility of content shared from other workspaces off ("strict", for
 * the back office, so owners only see and edit their own courses and exams)
 * or back on ("shared", for the few back-office pages that only read a shared
 * course, like reports and marking). Runs before route model binding.
 */
class ContentSharing
{
    public function handle(Request $request, Closure $next, string $mode = 'strict'): Response
    {
        app(Tenancy::class)->shareContent($mode === 'shared');

        return $next($request);
    }
}
