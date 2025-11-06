<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class UserSettingsController extends Controller
{
    /**
     * Show the authenticated user's settings, cached for 5 minutes.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('Unauthenticated access attempt to user settings.');
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $cacheKey = "user_settings_{$user->id}";
            $ttl = 300; // 5 minutes

            $cachedSettings = Cache::remember($cacheKey, $ttl, function () use ($user) {
                Log::info('Caching user settings', ['user_id' => $user->id]);
                return [
                    'business_name' => $user->business_name ?? '',
                    'email' => $user->email ?? '',
                ];
            });

            return response()->json($cachedSettings, 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching user settings', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to load user settings.'], 500);
        }
    }

    /**
     * Update user's business name and email.
     * Clears and refreshes the cache after update.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        try {
            $user->update([
                'business_name' => $request->business_name,
                'email' => $request->email,
            ]);

            $cacheKey = "user_settings_{$user->id}";
            $ttl = 300;

            // Clear and refresh cache
            Cache::forget($cacheKey);
            Cache::put($cacheKey, [
                'business_name' => $user->business_name,
                'email' => $user->email,
            ], $ttl);

            Log::info('User profile updated & cache refreshed', ['user_id' => $user->id]);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => [
                    'business_name' => $user->business_name,
                    'email' => $user->email,
                ],
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error updating profile', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update profile.'], 500);
        }
    }

    /**
     * Update user's password.
     * Clears settings cache to maintain data integrity.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            Cache::forget("user_settings_{$user->id}");

            Log::info('Password updated & cache cleared', ['user_id' => $user->id]);

            return response()->json(['message' => 'Password updated successfully'], 200);
        } catch (\Throwable $e) {
            Log::error('Error updating password', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update password.'], 500);
        }
    }
}
