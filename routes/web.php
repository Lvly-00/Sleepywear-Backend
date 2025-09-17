<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__ . '/auth.php';


Route::get('/debug-csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => Session::getId(),
        'cookies' => request()->cookies->all(),
    ]);
});


// const TEST_USER = [
//     'email' => 'test@example.com',
//     'password' => 'password123'
// ];

// Route::post('/login', function (Request $request) {
//     $request->validate([
//         'email' => 'required|email',
//         'password' => 'required'
//     ]);

//     if ($request->email === TEST_USER['email'] && $request->password === TEST_USER['password']) {
//         session(['user_email' => $request->email]);
//         return response()->json([
//             'message' => 'Login successful',
//             'user' => ['email' => $request->email]
//         ]);
//     }

//     return response()->json(['message' => 'Invalid credentials'], 401);
// });

// Route::post('/logout', function (Request $request) {
//     $request->session()->flush();
//     return response()->json(['message' => 'Logged out']);
// });

// Route::get('/dashboard', function (Request $request) {
//     if (!$request->session()->has('user_email')) {
//         return response()->json(['message' => 'Unauthorized'], 401);
//     }
//     return response()->json([
//         'message' => 'Welcome to the dashboard!',
//         'user' => ['email' => $request->session()->get('user_email')]
//     ]);
// });
