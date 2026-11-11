<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\AuditLog;
use App\Notifications\TwoFactorCodeNotification;
use App\Rules\StrongPassword;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // * Set execution time limit for database operations
        set_time_limit(30); // 30 seconds for login
        
        try {
            // * Deactivated accounts must not be able to sign in
            if (User::where('email', $credentials['email'])->where('is_active', false)->exists()) {
                return back()->withErrors([
                    'email' => 'This account has been deactivated. Please contact your administrator.',
                ])->onlyInput('email');
            }

            if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Issue 2FA code via email for all users
            $codeLength = (int) config('app.two_factor.code_length', 6);
            $expiresMinutes = (int) config('app.two_factor.expires_minutes', 2);

            $min = (int) pow(10, max(1, $codeLength) - 1);
            $max = (int) pow(10, max(1, $codeLength)) - 1;
            $code = (string) random_int($min, $max);

            // * Log 2FA code for debugging (the code itself only on local machines,
            // * since the log can be read through public/view-logs.php)
            Log::info('2FA Code Generated', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'code' => app()->isLocal() ? $code : '[hidden]',
                'expires_at' => now()->addMinutes($expiresMinutes)->format('Y-m-d H:i:s'),
                'ip_address' => $request->ip(),
            ]);

            // * Console output for immediate debugging
            error_log("🔐 2FA CODE GENERATED:");
            error_log("   User: {$user->email}");
            if (app()->isLocal()) {
                error_log("   Code: {$code}");
            }
            error_log("   Expires: " . now()->addMinutes($expiresMinutes)->format('Y-m-d H:i:s'));
            error_log("   IP: {$request->ip()}");

            $user->two_factor_code_hash = Hash::make($code);
            $user->two_factor_expires_at = now()->addMinutes($expiresMinutes);
            $user->save();

            try {
                $user->notify(new TwoFactorCodeNotification($code, $expiresMinutes));
            } catch (\Throwable $e) {
                Log::error('Failed to send 2FA email', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $request->session()->put('2fa_user_id', $user->id);
            $request->session()->put('2fa_passed', false);

            return redirect()->route('2fa.challenge');
            }
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database connection error during login', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);
            return back()->withErrors([
                'email' => 'Database connection error. Please try again or contact support.',
            ])->onlyInput('email');
        } catch (\Exception $e) {
            Log::error('Unexpected error during login', [
                'error' => $e->getMessage(),
                'email' => $request->email,
            ]);
            return back()->withErrors([
                'email' => 'An error occurred. Please try again.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|string|unique:users',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
            'role' => 'required|in:superadmin,admin,employee',
            'branch' => 'required|in:las_pinas,cebu,davao',
            'position' => 'nullable|string|max:255',
            'phone' => 'nullable|regex:/^(\+63)?[0-9]{11}$/',
        ]);

        $user = User::create([
            'employee_id' => $validated['employee_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'branch' => $validated['branch'],
            'position' => $validated['position'],
            'phone' => $validated['phone'],
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }

    public function profile()
    {
        $user = auth()->user();
        return view('auth.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|regex:/^(\+63)?[0-9]{11}$/',
        ]);

        $oldValues = $user->only(['first_name', 'last_name', 'email', 'phone']);

        $user->update($validated);

        AuditLog::log(
            'update',
            "Updated profile information",
            $user,
            $oldValues,
            $validated
        );

        return redirect()->route('profile')->with('success', 'Profile updated successfully');
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        AuditLog::log(
            'update',
            "Changed password",
            $user
        );

        return redirect()->route('profile')->with('success', 'Password updated successfully');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        // Redirect if not logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // If already passed 2FA, go intended
        if ($request->session()->get('2fa_passed') === true) {
            return redirect()->intended('/dashboard');
        }

        // * Pass expiration minutes to view
        $expiresMinutes = (int) config('app.two_factor.expires_minutes', 2);

        return view('auth.two-factor-challenge', compact('expiresMinutes'));
    }

    public function verifyTwoFactor(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'digits_between:4,10'],
        ]);

        $user = Auth::user();

        $attemptsKey = '2fa:attempts:' . $user->id . ':' . $request->ip();
        $maxAttempts = (int) (env('TWO_FACTOR_MAX_ATTEMPTS', 5));
        $decaySeconds = 60 * (int) (env('TWO_FACTOR_RESEND_SECONDS', 60));

        if (RateLimiter::tooManyAttempts($attemptsKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($attemptsKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $code = (string) $request->input('code');
        $expiresAt = $user->two_factor_expires_at;
        $hash = $user->two_factor_code_hash;

        if ($hash === null || $expiresAt === null || now()->greaterThan($expiresAt)) {
            RateLimiter::hit($attemptsKey, $decaySeconds);
            return back()->withErrors(['code' => 'The code is invalid or has expired.']);
        }

        if (!Hash::check($code, $hash)) {
            RateLimiter::hit($attemptsKey, $decaySeconds);
            return back()->withErrors(['code' => 'The code is incorrect.']);
        }

        // * Log successful 2FA verification
        Log::info('2FA Verification Successful', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip_address' => $request->ip(),
            'verified_at' => now()->format('Y-m-d H:i:s'),
        ]);

        // * Console output for successful verification
        error_log("✅ 2FA VERIFICATION SUCCESSFUL:");
        error_log("   User: {$user->email}");
        error_log("   Code: {$code}");
        error_log("   Verified: " . now()->format('Y-m-d H:i:s'));
        error_log("   IP: {$request->ip()}");

        // Success: clear and mark session
        $user->two_factor_code_hash = null;
        $user->two_factor_expires_at = null;
        $user->two_factor_confirmed_at = now();
        // * Update last_login_at when user successfully completes 2FA and logs in
        $user->last_login_at = now();
        $user->save();

        $request->session()->put('2fa_passed', true);
        RateLimiter::clear($attemptsKey);

        return redirect()->intended('/dashboard');
    }

    public function resendTwoFactor(Request $request)
    {
        // * Debug: Method called
        error_log("2FA Resend method called");
        error_log("🔍 RESEND METHOD CALLED");
        
        if (!Auth::check()) {
            error_log("❌ User not authenticated");
            return redirect()->route('login');
        }

        $user = Auth::user();
        error_log("✅ User authenticated: {$user->email}");

        $resendKey = '2fa:resend:' . $user->id . ':' . $request->ip();
        $resendCooldown = (int) (env('TWO_FACTOR_RESEND_SECONDS', 60));
        
        error_log("🔑 Resend Key: {$resendKey}");
        error_log("⏰ Cooldown: {$resendCooldown} seconds");

        if (RateLimiter::tooManyAttempts($resendKey, 1)) {
            $seconds = RateLimiter::availableIn($resendKey);
            error_log("❌ Rate limited. Wait {$seconds} seconds");
            return back()->withErrors(['code' => "Please wait {$seconds} seconds before requesting a new code."]);
        }
        
        error_log("✅ Rate limit check passed");

        $codeLength = (int) config('app.two_factor.code_length', 6);
        $expiresMinutes = (int) config('app.two_factor.expires_minutes', 2);
        
        error_log("🔢 Code Length: {$codeLength}");
        error_log("⏰ Expires Minutes: {$expiresMinutes}");

        $min = (int) pow(10, max(1, $codeLength) - 1);
        $max = (int) pow(10, max(1, $codeLength)) - 1;
        $code = (string) random_int($min, $max);
        
        if (app()->isLocal()) {
            error_log("🎲 Generated Code: {$code}");
        }

        // * Log 2FA code for debugging (resend)
        Log::info('2FA Code Resent', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'code' => app()->isLocal() ? $code : '[hidden]',
            'expires_at' => now()->addMinutes($expiresMinutes)->format('Y-m-d H:i:s'),
            'ip_address' => $request->ip(),
        ]);

        // * Console output for immediate debugging (resend)
        error_log("🔄 2FA CODE RESENT:");
        error_log("   User: {$user->email}");
        if (app()->isLocal()) {
            error_log("   Code: {$code}");
        }
        error_log("   Expires: " . now()->addMinutes($expiresMinutes)->format('Y-m-d H:i:s'));
        error_log("   IP: {$request->ip()}");

        $user->two_factor_code_hash = Hash::make($code);
        $user->two_factor_expires_at = now()->addMinutes($expiresMinutes);
        $user->save();

        try {
            $user->notify(new TwoFactorCodeNotification($code, $expiresMinutes));
        } catch (\Throwable $e) {
            Log::error('Failed to send 2FA email (resend)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Set cooldown for resend
        RateLimiter::hit($resendKey, $resendCooldown);

        return back()->with('status', 'A new code has been sent to your email.');
    }
}
