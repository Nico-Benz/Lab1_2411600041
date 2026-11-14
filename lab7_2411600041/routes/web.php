<?php

use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ProcurementApiController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPerformanceController;
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

        // Lab 7: suppliers (create / edit / delete / restore are admin only)
        Route::resource('suppliers', SupplierController::class)->except(['index', 'show']);
        Route::post('/suppliers/{id}/restore', [SupplierController::class, 'restore'])->name('suppliers.restore');

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
    Route::resource('suppliers', SupplierController::class)->only(['index', 'show']);

    Route::get('/transactions', [InventoryTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [InventoryTransactionController::class, 'show'])->name('transactions.show');

    // ---- Lab 7: purchase orders (the PurchaseOrderPolicy decides who may do what) ----
    Route::get('/purchase-orders/{purchase_order}/pdf', [PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
    Route::post('/purchase-orders/{purchase_order}/submit', [PurchaseOrderController::class, 'submit'])->name('purchase-orders.submit');
    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('/purchase-orders/{purchase_order}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::resource('purchase-orders', PurchaseOrderController::class);

    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/{type}', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/supplier-performance', [SupplierPerformanceController::class, 'index'])->name('reports.supplier-performance');

    // CSV and PDF exports: products, purchase-orders, transactions, supplier-performance
    Route::get('/exports/{report}/{format}', [ExportController::class, 'download'])->name('exports.download');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // ---- JSON endpoints used by the dashboard (AJAX polling) ----
    Route::prefix('api')->name('api.')->group(function () {
        Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('stats');
        Route::get('/products/low-stock', [DashboardApiController::class, 'lowStock'])->name('lowStock');
        Route::get('/products/{product}/stock', [DashboardApiController::class, 'productStock'])->name('productStock');
        Route::get('/transactions/recent', [DashboardApiController::class, 'recentTransactions'])->name('recent');

        // Lab 7: procurement widgets
        Route::get('/dashboard/pending-orders', [ProcurementApiController::class, 'pendingOrders'])->name('pendingOrders');
        Route::get('/dashboard/recent-orders', [ProcurementApiController::class, 'recentOrders'])->name('recentOrders');
        Route::get('/dashboard/supplier-performance', [ProcurementApiController::class, 'supplierPerformance'])->name('supplierPerformance');
        Route::get('/dashboard/reorder-alerts', [ProcurementApiController::class, 'reorderAlerts'])->name('reorderAlerts');
    });
});

require __DIR__.'/auth.php';
