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

    // Authentication
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Public logout route (for now we’ll protect it below)
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::get('/dashboard-summary', [DashboardController::class, 'summary']);
    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Require Bearer Token)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // Collections & Items
        Route::apiResource('collections', CollectionController::class);
        Route::apiResource('items', ItemController::class);
        Route::get('/collections/{collection}/items', [ItemController::class, 'getByCollection']);

        // Orders, Payments & Invoices
        Route::apiResource('orders', OrderController::class);
        Route::apiResource('order-items', OrderItemController::class);
        Route::post('/orders/{order}/payment', [PaymentController::class, 'storePayment']);
        Route::apiResource('invoices', InvoiceController::class);
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download']);

        // Customers
        Route::apiResource('customers', CustomerController::class);

        // Dashboard Summary

        // User Settings
        Route::get('/user/settings', [UserSettingsController::class, 'show']);
        Route::put('/user/settings', [UserSettingsController::class, 'updateProfile']);
        Route::put('/user/settings/password', [UserSettingsController::class, 'updatePassword']);
    });
