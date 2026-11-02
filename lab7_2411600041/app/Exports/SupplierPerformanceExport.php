<?php

namespace App\Exports;

use App\Models\Supplier;
use App\Services\SupplierPerformanceService;
use Illuminate\Support\Collection;

class SupplierPerformanceExport
{
    // Walang filter ang report na ito. Tumatanggap pa rin ng $filters
    // para pareho ang hugis ng apat na export classes.
    public function __construct(protected array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Supplier Performance';
    }

    public function headings(): array
    {
        return [
            'Supplier (Instructor)', 'Total Orders', 'Delivered', 'On-Time Orders',
            'On-Time Rate (%)', 'Avg Actual Days', 'Avg Quoted Days',
            'Accuracy (%)', 'Total Value',
        ];
    }

    public function rows(): Collection
    {
        $service = app(SupplierPerformanceService::class);

        return Supplier::orderBy('company_name')
            ->get()
            ->map(function (Supplier $supplier) use ($service) {
                $p = $service->calculatePerformance($supplier);

                return [
                    $p['supplier_name'],
                    $p['total_orders'],
                    $p['delivered_orders'],
                    $p['on_time_orders'],
                    $p['on_time_rate'] ?? 'N/A',
                    $p['avg_actual_days'] ?? 'N/A',
                    $p['avg_quoted_days'] ?? 'N/A',
                    $p['accuracy_rate'] ?? 'N/A',
                    number_format($p['total_value'], 2, '.', ''),
                ];
            });
    }
}