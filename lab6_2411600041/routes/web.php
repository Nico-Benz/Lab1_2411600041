<?php

use App\Http\Controllers\GradeTransactionController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DashboardApiController;
use App\Http\Controllers\GradeAdjustmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GradeItemController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('grade-items', GradeItemController::class);
    
    Route::resource('grade-transactions', GradeTransactionController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    
    Route::get('/grade-adjustments/create', [GradeAdjustmentController::class, 'create'])->name('grade-adjustments.create');
    Route::post('/grade-adjustments', [GradeAdjustmentController::class, 'store'])->name('grade-adjustments.store');    
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::prefix('api')->name('api.')->group(function () {
    Route::get('/dashboard/stats', [DashboardApiController::class, 'stats'])->name('dashboard.stats');
    Route::get('/grade-items/at-risk', [DashboardApiController::class, 'atRisk'])->name('grade-items.at-risk');
    Route::get('/transactions/recent', [DashboardApiController::class, 'recentTransactions'])->name('transactions.recent');
    });
});

require __DIR__.'/auth.php';