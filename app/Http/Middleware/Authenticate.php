<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            // Clear any existing session data
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            
            return redirect('/login')->with('error', 'Please login to access this page.');
        }

        // Check if user account is still active
        $user = Auth::user();
        if (!$user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect('/login')->with('error', 'Your account has been deactivated. Please contact your administrator.');
        }

        return $next($request);
    }
}
