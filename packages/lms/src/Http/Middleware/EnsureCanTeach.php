<?php

namespace Elibrary\Lms\Http\Middleware;

use Closure;
use Elibrary\Lms\Support\Teaching;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Owners, or members assigned to teach at least one course. */
class EnsureCanTeach
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Teaching::canTeach($request->user()), 403, 'Only workspace owners and instructors can manage courses.');

        return $next($request);
    }
}
