<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserSettingsController extends Controller
{

    public function show(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        return response()->json([
            'business_name' => $user->business_name ?? '',
            'email' => $user->email ?? '',
        ]);
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $request->user()->id,
        ]);

        $user = $request->user();
        $user->business_name = $request->business_name;
        $user->email = $request->email;
        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function updatePassword(Request $request)
    {

        Log::info('Password request data:', $request->all());
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.']
            ]);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }
}
