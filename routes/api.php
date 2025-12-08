<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UserSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

// Authentication as a "sessions" resource
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Passwords as a "passwords" resource
Route::post('/passwords/forgot', [AuthController::class, 'forgotPassword']);
Route::post('/passwords/reset', [AuthController::class, 'resetPassword']);

// Test email (optional)
Route::get('/test-send-email', [AuthController::class, 'testSendEmail']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Bearer Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Collections & Items (nested resource)
    Route::apiResource('collections', CollectionController::class);
    Route::apiResource('collections.items', ItemController::class)->shallow();
    Route::apiResource('items', ItemController::class); // optional standalone

    // Orders & Payments (nested)
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('orders.payments', PaymentController::class)->shallow();
    Route::apiResource('order-items', OrderItemController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/file', [InvoiceController::class, 'download']); // RESTful download

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Dashboard Summary
    Route::get('/dashboard', [DashboardController::class, 'summary']);

    // User Settings
    Route::get('/user/settings', [UserSettingsController::class, 'show']);
    Route::put('/user/settings', [UserSettingsController::class, 'updateProfile']);
    Route::put('/user/settings/password', [UserSettingsController::class, 'updatePassword']);
});
