<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserSettingsController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        return response()->json([
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Profile updated successfully'], 200);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        if (! $user) {
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

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        try {
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Only logout if session exists (avoid 500 error in API auth)
            if ($request->hasSession() && $request->session()->isStarted()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json(['message' => 'Password updated successfully'], 200);
        } catch (\Throwable $e) {
            Log::error('Error updating password', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to update password.'], 500);
        }
    }
}
