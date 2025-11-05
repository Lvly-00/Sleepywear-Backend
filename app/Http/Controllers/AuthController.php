<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;


class AuthController extends Controller
{

    public function login(Request $request)
    {
        $this->checkTooManyFailedAttempts($request);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($this->throttleKey($request), 60); // lock for 60s

            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        RateLimiter::clear($this->throttleKey($request));

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    protected function throttleKey(Request $request)
    {
        return Str::lower($request->input('email')).'|'.$request->ip();
    }

    protected function checkTooManyFailedAttempts(Request $request)
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));
            abort(429, "Too many login attempts. Try again in {$seconds} seconds.");
        }
    }


    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        Cache::flush();

        return response()->json(['message' => 'Logged out successfully']);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => 'We can\'t find a user with that email.'], 404);
        }

        $token = Password::createToken($user);
        $token = Password::createToken($user);
        $resetUrl = env('APP_FRONTEND_URL')."/reset-password?token={$token}&email=".urlencode($user->email);
        $sent = BrevoMailer::sendResetLink($user->email, $resetUrl);

        if (! $sent) {
            return response()->json(['message' => 'Failed to send reset email. Please try again later.'], 500);
        }

        return response()->json(['message' => 'Password reset link sent! Check your email.']);
    }


  public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'token' => 'required',
        'password' => [
            'required',
            'string',
            'min:8',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[@$!%*#?&]/',
            'confirmed',
        ],
    ], [
        'email.required' => 'Email is required.',
        'email.email' => 'Please provide a valid email address.',

        'token.required' => 'Reset token is required.',

        'password.required' => 'Password is required.',
        'password.string' => 'Password must be a valid string.',
        'password.min' => 'Password must be at least 8 characters long.',
        'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.',
        'password.confirmed' => 'Password confirmation does not match.',
    ]);

    $user = User::where('email', $request->email)->first();

    if (! $user) {
        return response()->json(['message' => 'Invalid email address.'], 404);
    }

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();

            $user->tokens()->delete();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return response()->json(['message' => 'Password reset successful! Redirecting to login...']);
    }

    return response()->json(['message' => 'Invalid or expired token. Please request a new password reset link.'], 400);
}

}
