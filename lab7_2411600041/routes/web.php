<?php

use App\Http\Controllers\GradeTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardApiController;
use App\Http\Controllers\GradeAdjustmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GradeItemController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('grade-items', GradeItemController::class)->except(['index', 'show'])->middleware('can:admin');
    Route::resource('grade-items', GradeItemController::class)->only(['index', 'show']);
    
    Route::resource('grade-transactions', GradeTransactionController::class)
        ->only(['create', 'store', 'destroy'])->middleware('can:admin');
    Route::resource('grade-transactions', GradeTransactionController::class)
        ->only(['index', 'show']);
    
    Route::get('/grade-adjustments/create', [GradeAdjustmentController::class, 'create'])->middleware('can:admin')->name('grade-adjustments.create');
    Route::post('/grade-adjustments', [GradeAdjustmentController::class, 'store'])->middleware('can:admin')->name('grade-adjustments.store');   
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::resource('suppliers', SupplierController::class)->except(['index', 'show'])->middleware('can:admin');
    Route::resource('suppliers', SupplierController::class)->only(['index', 'show']);

    // Purchase Orders (ang Policy ang magpapasya kung sino ang pwede, hindi middleware)
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('/purchase-orders/{purchase_order}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchase_order}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchase_order}/send', [PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('/purchase-orders/{purchase_order}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::prefix('api')->name('api.')->group(function () {
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('dashboard.stats');
    Route::get('/grade-items/at-risk', [DashboardApiController::class, 'atRisk'])->name('grade-items.at-risk');
    Route::get('/transactions/recent', [DashboardApiController::class, 'recentTransactions'])->name('transactions.recent');

    Route::get('/dashboard/pending-orders', [DashboardApiController::class, 'pendingOrders'])->name('dashboard.pending-orders');
    Route::get('/dashboard/recent-orders', [DashboardApiController::class, 'recentOrders'])->name('dashboard.recent-orders');
    Route::get('/dashboard/supplier-performance', [DashboardApiController::class, 'supplierPerformance'])->name('dashboard.supplier-performance');
    Route::get('/dashboard/reorder-alerts', [DashboardApiController::class, 'reorderAlerts'])->name('dashboard.reorder-alerts');
    });
});

require __DIR__.'/auth.php';