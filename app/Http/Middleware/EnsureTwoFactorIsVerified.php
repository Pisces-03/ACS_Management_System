<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTwoFactorIsVerified
{
    /**
     * * Ensures that an authenticated user has completed the 2FA challenge in this session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // * Allow 2FA routes themselves to pass through to avoid loops
            $currentRouteName = $request->route() ? $request->route()->getName() : null;
            $twoFaRoutes = ['2fa.challenge', '2fa.verify', '2fa.resend', 'logout'];

            if (!in_array($currentRouteName, $twoFaRoutes, true)) {
                if (!$request->session()->has('2fa_passed') || $request->session()->get('2fa_passed') !== true) {
                    return redirect()->route('2fa.challenge');
                }
            }
        }

        return $next($request);
    }
}
























