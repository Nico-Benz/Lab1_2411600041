<?php

namespace App\Exports;

use App\Services\SupplierPerformanceService;

class SupplierPerformanceExport implements ReportExport
{
    public function title(): string
    {
        return 'Supplier Performance Report';
    }

    public function filename(): string
    {
        return 'supplier-performance-' . now()->format('Ymd-His');
    }

    public function headings(): array
    {
        return ['Supplier', 'Orders Placed', 'Delivered', 'On Time', 'On-Time %', 'Avg Actual Days', 'Avg Quoted Days', 'Accuracy %', 'Total Value'];
    }

    public function rows(): iterable
    {
        foreach (app(SupplierPerformanceService::class)->all() as $row) {
            yield [
                $row['supplier']->name,
                $row['total_orders'],
                $row['delivered_orders'],
                $row['on_time_orders'],
                $row['on_time_rate'] ?? '',
                $row['avg_actual_days'] ?? '',
                $row['avg_quoted_days'] ?? '',
                $row['accuracy_rate'] ?? '',
                number_format($row['total_value'], 2, '.', ''),
            ];
        }
    }
}
