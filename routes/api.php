<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

require __DIR__ . '/auth.php';

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->get('/dashboard', function (Request $request) {
    return response()->json(['message' => 'Welcome to the dashboard!']);
});

Route::get('/users', function () {
    // Return all users as JSON
    return response()->json([
        'items' => User::all()
    ]);
});
