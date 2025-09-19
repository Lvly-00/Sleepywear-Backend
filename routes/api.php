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

    // Orders & Invoices
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']); // used by add-order flow
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}', [OrderController::class, 'update']);
    Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

    // Order items listing
    Route::get('/order-items', [OrderItemController::class, 'index']); // list all order items
    Route::get('/order-items/{item}', [OrderItemController::class, 'show']);
    Route::get('/order-items/{item}/customers', [OrderItemController::class, 'customers']);

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']); // create from add-order flow
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy']);
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']); // pdf
});
