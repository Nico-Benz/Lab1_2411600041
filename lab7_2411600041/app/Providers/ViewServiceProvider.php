<?php

namespace App\Providers;

use App\Support\ProcurementWidgets;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('widgets.pending-orders', function ($view) {
            $view->with('pendingCount', ProcurementWidgets::pendingCount());
        });

        View::composer('widgets.recent-orders', function ($view) {
            $view->with('recentOrders', ProcurementWidgets::recentOrders());
        });

        View::composer('widgets.supplier-performance', function ($view) {
            $view->with('topSuppliers', ProcurementWidgets::topSuppliers());
        });

        View::composer('widgets.reorder-alerts', function ($view) {
            $view->with('reorderAlerts', ProcurementWidgets::reorderAlerts());
        });
    }
}
