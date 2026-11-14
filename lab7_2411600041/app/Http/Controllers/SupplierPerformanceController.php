<?php

namespace App\Http\Controllers;

use App\Services\SupplierPerformanceService;

class SupplierPerformanceController extends Controller
{
    public function index(SupplierPerformanceService $service)
    {
        $rows = $service->all();

        // Rank only suppliers that already delivered something: on-time % first, then accuracy %
        $ranked = $rows->filter(fn (array $row) => $row['delivered_orders'] > 0)
            ->sortByDesc(fn (array $row) => ($row['on_time_rate'] ?? 0) * 1000 + ($row['accuracy_rate'] ?? 0))
            ->values();

        $top = $ranked->take(3);
        $bottom = $ranked->count() > 3 ? $ranked->reverse()->take(3)->values() : collect();
        $trend = $service->monthlyTrend(6);

        return view('reports.supplier-performance', compact('rows', 'top', 'bottom', 'trend'));
    }
}
