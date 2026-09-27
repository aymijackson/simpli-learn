<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language: the person's own choice, else one picked on
 * the sign-in page this session (?lang=), else the workspace default, else
 * the platform default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $available = config('languages.available');

        if ($request->has('lang') && array_key_exists($request->query('lang'), $available)) {
            $request->session()->put('locale', $request->query('lang'));
        }

        $locale = collect([
            $request->user()?->locale,
            $request->session()->get('locale'),
            app(Tenancy::class)->current()?->locale,
        ])->first(fn ($code) => $code && array_key_exists($code, $available)) ?? config('app.locale');

        app()->setLocale($locale);
        // Dates ("2 days ago") follow where the date library has the language.
        Carbon::setLocale($locale);

        return $next($request);
    }
}
