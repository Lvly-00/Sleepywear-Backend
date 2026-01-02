<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const MaX_LOGIN_ATTEMPTS = 3;

    private const DECAY_MINUTES = 1;

    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->throttleKey($request);

        // Check rate limit BEFORE revealing user existence
        if (RateLimiter::tooManyAttempts($throttleKey, self::MaX_LOGIN_ATTEMPTS)) {
            return $this->tooManyAttemptsResponse($throttleKey, $request);
        }

        $user = User::where('email', $request->email)->first();

        // if (! $user || ! Hash::check($request->password, $user->password)) {
        //     RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

        //     return response()->json([
        //         'error' => 'Invalid credentials.',
        //     ], 401);
        // }

        // If user does not exist
        if (! $user) {
            RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

            return response()->json([
                'message' => 'No account found with this email.',
            ], 401);
        }

        // If password is wrong
        if (! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

            return response()->json([
                'message' => 'Your password is incorrect.',
            ], 401);
        }

        // Success: clear attempts and generate token
        RateLimiter::clear($throttleKey);
        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Log out authenticated user
     */
    public function logout(): JsonResponse
    {
        $request = request();

        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Send password reset email
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'message' => "We can't find a user with that email.",
            ], 200);
        }

        $token = Password::createToken($user);
        $resetUrl = env('APP_FRONTEND_URL').
           "/passwords/reset?token={$token}&email=".urlencode($user->email);

        $sent = BrevoMailer::sendResetLink($user->email, $resetUrl);

        if (! $sent) {
            return response()->json([
                'message' => 'Password reset link sent! Check your email.',
            ], 500);
        }

        return response()->json(['message' => 'Password reset link sent if email exists.']);
    }

    /**
     * Reset user password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {


        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Revoke all tokens for security
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

    /**
     * Generate secure throttle key (email + IP)
     */
    private function throttleKey($request): string
    {
        return Str::lower($request->email ?? '').'|'.$request->ip();
    }

    /**
     * Return rate limit exceeded response
     */
    private function tooManyAttemptsResponse(string $throttleKey, $request): JsonResponse
    {
        $seconds = RateLimiter::availableIn($throttleKey);

        return response()->json([
            'error' => 'Too many login attempts. Try again in '.$seconds.' seconds.',
        ], 429);
    }
}
