<?php

namespace App\View\Composers;

use App\Models\PurchaseOrder;
use App\Services\ReorderService;
use App\Services\SupplierPerformanceService;
use Illuminate\Support\Facades\Cache;

/**
 * The data of the four dashboard widgets.
 * ViewServiceProvider gives it to the widget views (View Composers),
 * and ProcurementApiController uses the same methods for the JSON endpoints.
 *
 * The heavier widgets (supplier performance, reorder alerts) are cached for 60 seconds.
 */
class ProcurementWidgets
{
    public function pendingOrders(): array
    {
        $pending = PurchaseOrder::pending();

        return [
            'pendingCount' => (clone $pending)->count(),
            'pendingValue' => round((float) (clone $pending)->sum('total'), 2),
            'pendingOrders' => (clone $pending)->with('supplier')->latest()->take(5)->get(),
        ];
    }

    public function recentOrders(): array
    {
        return [
            'recentOrders' => PurchaseOrder::with(['supplier', 'createdBy'])->latest()->latest('id')->take(6)->get(),
        ];
    }

    public function supplierPerformance(): array
    {
        $top = Cache::remember('widgets.supplier-performance', $this->ttl(), function () {
            return app(SupplierPerformanceService::class)->all()
                ->filter(fn (array $row) => $row['delivered_orders'] > 0)
                ->sortByDesc(fn (array $row) => ($row['on_time_rate'] ?? 0) * 1000 + ($row['accuracy_rate'] ?? 0))
                ->take(5)
                ->map(fn (array $row) => [
                    'id' => $row['supplier']->id,
                    'name' => $row['supplier']->name,
                    'on_time_rate' => $row['on_time_rate'],
                    'accuracy_rate' => $row['accuracy_rate'],
                    'delivered_orders' => $row['delivered_orders'],
                ])
                ->values()
                ->all();
        });

        return ['topSuppliers' => $top];
    }

    public function reorderAlerts(): array
    {
        $alerts = Cache::remember('widgets.reorder-alerts', $this->ttl(), function () {
            $service = new ReorderService();

            return $service->getProductsNeedingReorder()
                ->take(8)
                ->map(fn ($product) => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => (int) $product->quantity,
                    'reorder_point' => $service->calculateReorderPoint($product),
                    'suggested' => $service->calculateOptimalOrderQuantity($product),
                    'supplier' => $product->supplier->name ?? null,
                    'has_open_order' => $service->hasOpenOrder($product),
                ])
                ->values()
                ->all();
        });

        return ['reorderAlerts' => $alerts];
    }

    private function ttl(): int
    {
        return (int) config('procurement.widget_cache_seconds', 60);
    }
}
