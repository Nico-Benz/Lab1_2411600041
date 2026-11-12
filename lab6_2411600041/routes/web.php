<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ---- Admin-only routes (registered first so /products/create is not read as /products/{product}) ----
    Route::middleware('can:admin')->group(function () {
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::resource('brands', BrandController::class)->except(['index', 'show']);
        Route::resource('categories', CategoryController::class)->except(['index', 'show']);
        Route::resource('users', UserController::class);

        Route::get('/stock-adjustments/create', [StockAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('/stock-adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
        Route::delete('/transactions/{transaction}', [InventoryTransactionController::class, 'destroy'])->name('transactions.destroy');
        Route::get('/transactions/create', [InventoryTransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [InventoryTransactionController::class, 'store'])->name('transactions.store');
        Route::post('/products/{product}/transactions', [InventoryTransactionController::class, 'store'])->name('products.transactions.store');
    });

    // ---- Everyone who is logged in ----
    Route::resource('products', ProductController::class)->only(['index', 'show']);
    Route::resource('brands', BrandController::class)->only(['index', 'show']);
    Route::resource('categories', CategoryController::class)->only(['index', 'show']);

    Route::get('/transactions', [InventoryTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('transactions.show');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // ---- JSON endpoints used by the dashboard (AJAX polling) ----
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('stats');
        Route::get('/products/low-stock', [DashboardApiController::class, 'lowStock'])->name('lowStock');
        Route::get('/products/{product}/stock', [DashboardApiController::class, 'productStock'])->name('productStock');
        Route::get('/transactions/recent', [DashboardApiController::class, 'recentTransactions'])->name('recent');
    });
});

require __DIR__.'/auth.php';