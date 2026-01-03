<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\NotificationController;

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()->toIso8601String(),
        'version' => 'v1'
    ]);
});
// API Version 1
Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1'); // 5 requests per minute
        Route::post('/password/forgot', [AuthController::class, 'forgotPassword']);
        Route::post('/password/reset', [AuthController::class, 'resetPassword']);
    });

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', 'log.activity'])->group(function () {
                                                      
        // Authentication
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/refresh', [AuthController::class, 'refresh']);
            Route::get('/user', [AuthController::class, 'user']);
        });

        // Dashboard Analytics
        Route::prefix('dashboard')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            Route::get('/sales-performance', [DashboardController::class, 'salesPerformance']);
            Route::get('/inventory-status', [DashboardController::class, 'inventoryStatus']);
            Route::get('/top-products', [DashboardController::class, 'topProducts']);
        });

        // Products / Inventory Management
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::get('/low-stock', [ProductController::class, 'lowStock']);
            Route::get('/{id}', [ProductController::class, 'show']);
            Route::get('/{id}/stock', [ProductController::class, 'stock']);
            
        //     // Admin only routes
            Route::middleware('can:manage_inventory')->group(function () {
                Route::post('/', [ProductController::class, 'store']);
                Route::put('/{id}', [ProductController::class, 'update']);
                Route::delete('/{id}', [ProductController::class, 'destroy']);
            });
            
        //     // Stock operations
            Route::post('/{id}/reserve', [ProductController::class, 'reserveStock']);
            Route::post('/{id}/release', [ProductController::class, 'releaseStock']);
        });

        // Customers
        Route::prefix('customers')->group(function () {
            Route::get('/', [CustomerController::class, 'index']);
            Route::get('/map-data', [CustomerController::class, 'mapData']);
            Route::get('/{id}', [CustomerController::class, 'show']);
            Route::get('/{id}/orders', [CustomerController::class, 'orders']);
            Route::get('/{id}/credit-status', [CustomerController::class, 'creditStatus']);
            Route::post('/', [CustomerController::class, 'store']);
            Route::put('/{id}', [CustomerController::class, 'update']);
            Route::delete('/{id}', [CustomerController::class, 'destroy']);
        });

        // Orders
        Route::prefix('orders')->group(function () {
            Route::get('/', [OrderController::class, 'index']);
            Route::get('/{id}', [OrderController::class, 'show']);
            Route::get('/{id}/invoice', [OrderController::class, 'invoice']);
            Route::post('/calculate-total', [OrderController::class, 'calculateTotal']);
            Route::post('/', [OrderController::class, 'store'])
                ->middleware('check.credit');
            Route::put('/{id}/status', [OrderController::class, 'updateStatus']);
        });

        // Warehouses
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [WarehouseController::class, 'index']);
            Route::get('/{id}/inventory', [WarehouseController::class, 'inventory']);
        });

        // Stock Transfers
        Route::prefix('stock-transfers')->group(function () {
            Route::get('/', [WarehouseController::class, 'transferHistory']);
            Route::post('/', [WarehouseController::class, 'transfer']);
        });

        // Notifications
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
            Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
        });
    });
});

