<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

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

Route::post('/login', [AuthenticatedSessionController::class, 'store']);


Route::apiResource('/collections', CollectionController::class);
Route::apiResource('/items', ItemController::class);

Route::get('/collections/{collection}/items', [ItemController::class, 'getByCollection']);
