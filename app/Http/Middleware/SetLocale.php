<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applies the signed-in user's saved language (en / tl) per request. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;
        if ($locale && in_array($locale, ['en', 'tl'], true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
