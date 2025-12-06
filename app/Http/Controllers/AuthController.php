<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Login
     */
    public function login(Request $request)
    {
        $this->checkTooManyAttempts($request);

        // Validate only format, not existence
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format. Please use: username@example.com.',
            'password.required' => 'Password is required.',
        ]);

        $user = User::where('email', $request->email)->first();

        // If user does not exist
        if (! $user) {
            RateLimiter::hit($this->throttleKey($request), 60);

            return response()->json([
                'message' => 'No account found with this email.',
            ], 401);
        }

        // If password is wrong
        if (! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request), 60);

            return response()->json([
                'message' => 'Your password is incorrect.',
            ], 401);
        }

        // Successful login clears attempts
        RateLimiter::clear($this->throttleKey($request));

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->safeUser($user),
        ]);
    }

    /**
     * Generates a secure user output
     */
    protected function safeUser(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    /**
     * Throttle key: email + IP
     */
    protected function throttleKey(Request $request)
    {
        return Str::lower($request->email).'|'.$request->ip();
    }

    /**
     * Blocking brute force attempts
     */
    protected function checkTooManyAttempts(Request $request)
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $message = "Too many login attempts. Try again in {$seconds} seconds. ";
            $message .= "Forgot your password? You can <a href='/password/reset'>reset it here</a>.";

            // Use response()->json for API or abort with HTML for web
            if ($request->wantsJson()) {
                return response()->json(['error' => $message], 429);
            }

            abort(429, $message);
        }
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Forgot Password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => "We can't find a user with that email.",
            ], 404);
        }

        $token = Password::createToken($user);

        $resetUrl = env('APP_FRONTEND_URL').
            "/reset-password?token={$token}&email=".urlencode($user->email);

        $sent = BrevoMailer::sendResetLink($user->email, $resetUrl);

        if (! $sent) {
            return response()->json([
                'message' => 'Failed to send reset email. Please try again later.',
            ], 500);
        }

        return response()->json([
            'message' => 'Password reset link sent! Check your email.',
        ]);
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => [
                'required', 'string', 'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
                'confirmed',
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Invalid email address.',
            ], 404);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->update([
                    'password' => Hash::make($password),
                ]);

                // Remove all active tokens for security
                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Password reset successful! You may now log in.',
            ]);
        }

        return response()->json([
            'message' => 'Invalid or expired token.',
        ], 400);
    }
}
