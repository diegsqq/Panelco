<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordSessionIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->getAuthPassword()) {
            return $next($request);
        }

        $sessionKey = 'password_fingerprint_'.$user->getAuthIdentifier();
        $fingerprint = hash_hmac(
            'sha256',
            $user->getAuthPassword(),
            (string) config('app.key')
        );
        $storedFingerprint = $request->session()->get($sessionKey);

        if ($storedFingerprint !== null && ! hash_equals($fingerprint, $storedFingerprint)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Your password has changed. Sign in again.']);
        }

        if ($storedFingerprint === null) {
            $request->session()->put($sessionKey, $fingerprint);
        }

        return $next($request);
    }
}
