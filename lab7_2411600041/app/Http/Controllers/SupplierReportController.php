<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\SupplierPerformanceService;

class SupplierReportController extends Controller
{
    public function index(SupplierPerformanceService $service)
    {
        // Performance ng bawat supplier (soft-deleted ay hindi kasama)
        $rows = Supplier::orderBy('company_name')->get()
            ->map(fn ($supplier) => $service->calculatePerformance($supplier));

        // Top at bottom: yung mga may natanggap na order lang ang may on_time_rate
        $ranked = $rows->filter(fn ($row) => $row['on_time_rate'] !== null);
        $top = $ranked->where('on_time_rate', '>=', 50)->sortByDesc('on_time_rate')->take(3)->values();
        $bottom = $ranked->where('on_time_rate', '<', 50)->sortBy('on_time_rate')->take(3)->values();

        // Chart 1: average actual vs quoted days kada supplier
        $daysChart = $ranked->values()->map(fn ($row) => [
            'name' => $row['supplier_name'],
            'actual' => $row['avg_actual_days'],
            'quoted' => $row['avg_quoted_days'],
        ]);

        // Chart 2: delivery trend kada buwan (on time vs late)
        $trend = PurchaseOrder::where('status', 'received')
            ->whereNotNull('received_at')
            ->whereNotNull('expected_delivery_date')
            ->get()
            ->groupBy(fn ($po) => $po->received_at->format('Y-m'))
            ->map(function ($group, $month) {
                $onTime = $group->filter(fn ($po) => $po->received_at->copy()->startOfDay()
                    ->lte($po->expected_delivery_date->copy()->startOfDay()))->count();

                return [
                    'month' => $month,
                    'on_time' => $onTime,
                    'late' => $group->count() - $onTime,
                ];
            })
            ->sortKeys()
            ->values();

        return view('reports.suppliers', compact('rows', 'top', 'bottom', 'daysChart', 'trend'));
    }
}