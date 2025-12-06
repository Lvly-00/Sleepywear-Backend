<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class UserSettingsController extends Controller
{
    // GET PROFILE
    public function show(Request $request)
    {
        $user = $request->user();

        // 1. Check if user exists (Safety check)
        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // 2. Wrap in try-catch to catch database column errors
        try {
            return response()->json([
                'name'  => $user->name,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            Log::error('Profile Fetch Error: ' . $e->getMessage());
            return response()->json(['message' => 'Server Error: Could not fetch profile data.'], 500);
        }
    }

    // UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
        ], [
            'name.required'     => 'Business name is required.',
            'email.required'    => 'Email is required.',
            'email.email'       => 'Invalid email format.',
            'email.unique'      => 'This email is already taken.',
        ]);

        $user->update([
            'name'  => $request->name,
            'email' => $request->email,
        ]);

        return response()->json(['message' => 'Profile updated successfully']);
    }

    // UPDATE PASSWORD
    public function updatePassword(Request $request)
{
    $user = $request->user();

    // 1. Initialize an empty error array
    $errors = [];

    // 2. Validate New Password (Format & Match)
    $validator = Validator::make($request->all(), [
        'new_password' => [
            'required',
            'confirmed',
            'min:8',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[@$!%*?&#^()_+\-={}[\]|\\:;"\'<>,.\/~`]/',
        ]
    ], [
        'new_password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors()->toArray();
    }

    // 3. Validate Current Password (CHECK THIS MANUALLY AND SEPARATELY)
    if (!Hash::check($request->current_password, $user->password)) {
        // Add this to the errors array manually
        $errors['current_password'] = ['Current password is incorrect.'];
    }

    // 4. If there are ANY errors (from Step 2 OR Step 3), return 422
    if (!empty($errors)) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    // 5. Success - Update Password
    try {
        $user->password = Hash::make($request->new_password);
        $user->save();

        // Logout logic...
        if ($request->hasSession() && $request->session()->isStarted()) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } elseif ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Password updated successfully'], 200);

    } catch (\Exception $e) {
        return response()->json(['message' => 'Server error'], 500);
    }

}
}
