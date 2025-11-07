<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserSettingsController extends Controller
{
    /**
     * Show the authenticated user's settings.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            Log::warning('Unauthenticated access attempt to user settings.');
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        try {
            $settings = [
                'business_name' => $user->business_name ?? '',
                'email' => $user->email ?? '',
            ];

            return response()->json($settings, 200);
        } catch (\Throwable $e) {
            Log::error('Error fetching user settings', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to load user settings.'], 500);
        }
    }

    /**
     * Update user's business name and email.
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

            Log::info('User profile updated', ['user_id' => $user->id]);

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
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*?&#^()_+\-={}[\]|\\:;"\'<>,.\/~`]/',
            ],
        ], [
            'new_password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
            'new_password.min' => 'Password must be at least 8 characters.',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Log out user to invalidate current session
            Auth::logout();

            // Invalidate session if session-based auth
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('Password updated and user logged out', ['user_id' => $user->id]);

            return response()->json(['message' => 'Password updated successfully, logged out'], 200);
        } catch (\Throwable $e) {
            Log::error('Error updating password', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to update password.'], 500);
        }
    }
}
