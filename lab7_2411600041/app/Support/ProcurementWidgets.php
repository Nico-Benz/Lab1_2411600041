<?php

namespace App\Support;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\ReorderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProcurementWidgets
{
    // Gaano katagal itatago ang sagot (segundo)
    public const CACHE_SECONDS = 60;

    // Bilang ng PO na naghihintay ng approval
    public static function pendingCount(): int
    {
        return PurchaseOrder::pending()->count();
    }

    // Huling 5 PO (kasama ang supplier, para iwas N+1)
    public static function recentOrders(): Collection
    {
        return PurchaseOrder::with('supplier')->latest()->latest('id')->take(5)->get();
    }

    // Top 5 suppliers ayon sa on-time delivery %
    // Naka-cache: mabigat ang kalkulasyon ng bawat supplier
    public static function topSuppliers(): Collection
    {
        return Cache::remember('widgets.top_suppliers', self::CACHE_SECONDS, function () {
            return Supplier::active()->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->company_name,
                    'rating' => $s->performance_rating,
                ])
                ->filter(fn ($row) => $row['rating'] !== null)
                ->sortByDesc('rating')
                ->take(5)
                ->values();
        });
    }

    // Mga subject na mas mababa sa reorder point
    // Naka-cache: kinakalkula ang reorder point ng bawat subject
    public static function reorderAlerts(): Collection
    {
        return Cache::remember('widgets.reorder_alerts', self::CACHE_SECONDS, function () {
            $service = app(ReorderService::class);

            return $service->getProductsNeedingReorder()
                ->map(fn ($item) => [
                    'code' => $item->subject_code,
                    'name' => $item->subject_name,
                    'grade' => (float) $item->current_grade,
                    'reorder_point' => round($service->calculateReorderPoint($item), 1),
                    'supplier' => $item->supplier?->company_name ?? 'No supplier',
                ])
                ->take(5)
                ->values();
        });
    }
}