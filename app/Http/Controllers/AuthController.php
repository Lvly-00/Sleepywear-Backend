<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\BrevoMailer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_MINUTES * 60);

            return response()->json([
                'error' => 'Invalid credentials.',
            ], 401);
        }

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
        if (!$user) {
            return response()->json(['message' => "We can't find a user with that email."], 404);
        }

        $otp = (string) rand(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($otp), 'created_at' => now()]
        );

        // Default purpose is 'reset'
        BrevoMailer::sendOtpEmail($user->email, $otp, 'reset');

        return response()->json(['message' => 'Verification code sent! Check your email.']);
    }


    /**
     * Verify if the 6-digit OTP is correct
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // 1. Check if record exists
        if (! $record) {
            return response()->json(['message' => 'No code found for this email.'], 400);
        }

        // 2. Check if code matches (using Hash::check because you hashed it in forgotPassword)
        if (! Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'The code is incorrect.'], 400);
        }

        // 3. Check if expired
        if (now()->parse($record->created_at)->addMinutes(5)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json(['message' => 'The code has expired.'], 400);
        }

        return response()->json(['message' => 'Code verified successfully.']);
    }

    /**
     * Request OTP for Biometric Registration (Unauthenticated)
     */
    public function requestBiometricOtp(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));
        $user = \App\Models\User::where('email', $email)->first();

        if (! $user) {
            return response()->json(['message' => 'No account found with this email.'], 404);
        }

        $otp = (string) rand(100000, 999999);

        // ALWAYS Hash the token before saving
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // Pass 'biometric' as the purpose
        try {
            BrevoMailer::sendOtpEmail($user->email, $otp, 'biometric');

            return response()->json(['message' => 'Verification code sent!']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Mail service error.'], 500);
        }
    }

    /**
     * Verify Biometric OTP
     */
    public function verifyBiometricOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'The code is incorrect.'], 400);
        }

        return response()->json(['message' => 'Biometrics authorized.']);
    }

    /**
     * Reset user password
     */
    // public function resetPassword(ResetPasswordRequest $request): JsonResponse
    // {

    //     $status = Password::reset(
    //         $request->only('email', 'password', 'password_confirmation', 'token'),
    //         function (User $user, string $password) {
    //             $user->forceFill([
    //                 'password' => Hash::make($password),
    //             ])->save();

    //             $user->tokens()->delete();
    //         }
    //     );

    //     if ($status === Password::PASSWORD_RESET) {
    //         return response()->json([
    //             'message' => 'Password reset successful! You may now log in.',
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Invalid or expired token.',
    //     ], 400);
    // }

    /**
     * Reset user password using OTP
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // We validate the OTP again to ensure the request is authorized
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $record || ! Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Authorization failed. Please request a new code.'], 400);
        }

        // Find the user
        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Update the password
        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Revoke all tokens for security
        $user->tokens()->delete();

        // Delete the used OTP
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password reset successful! You may now log in.',
        ]);
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
