<?php

namespace App\Providers;

use App\View\Composers\ProcurementWidgets;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * View Composers: every time a widget view is shown, its data is bound to it automatically,
 * so the controller does not need to load anything for the widgets.
 */
class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('widgets.pending-orders', fn ($view) => $view->with(app(ProcurementWidgets::class)->pendingOrders()));
        View::composer('widgets.recent-orders', fn ($view) => $view->with(app(ProcurementWidgets::class)->recentOrders()));
        View::composer('widgets.supplier-performance', fn ($view) => $view->with(app(ProcurementWidgets::class)->supplierPerformance()));
        View::composer('widgets.reorder-alerts', fn ($view) => $view->with(app(ProcurementWidgets::class)->reorderAlerts()));
    }
}
