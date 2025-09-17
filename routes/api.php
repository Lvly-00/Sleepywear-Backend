<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

require __DIR__ . '/auth.php';


// Route::post('/login', [AuthenticatedSessionController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('/collections', CollectionController::class);
    Route::apiResource('/items', ItemController::class);
    Route::get('/collections/{collection}/items', [ItemController::class, 'getByCollection']);

    // User settings
    Route::get('/user/settings', [UserSettingsController::class, 'show']);
    Route::put('/user/settings', [UserSettingsController::class, 'updateProfile']);
    Route::put('/user/settings/password', [UserSettingsController::class, 'updatePassword']);
});
