<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\UserSettingsController;

require __DIR__ . '/auth.php';

Route::middleware('auth:sanctum')->group(function () {
    // API resources for CRUD
    Route::apiResource('collections', CollectionController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('order-items', OrderItemController::class);
    Route::apiResource('customers', CustomerController::class);


    // Custom routes
    Route::get('/collections/{collection}/items', [ItemController::class, 'getByCollection']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);
    Route::post('/orders/{order}/payment', [PaymentController::class, 'storePayment']);
    Route::get('/dashboard-summary', [DashboardController::class, 'summary']);

    // User settings
    Route::get('/user/settings', [UserSettingsController::class, 'show']);
    Route::put('/user/settings', [UserSettingsController::class, 'updateProfile']);
    Route::put('/user/settings/password', [UserSettingsController::class, 'updatePassword']);
});
