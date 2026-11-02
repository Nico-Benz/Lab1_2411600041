<?php

namespace App\Exports;

use App\Models\PurchaseOrder;
use Illuminate\Support\Collection;

class PurchaseOrdersExport
{
    public function __construct(protected array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function headings(): array
    {
        return [
            'Reference', 'Supplier (Instructor)', 'Order Date', 'Expected Delivery',
            'Status', 'Subtotal', 'Tax', 'Total', 'Created By',
        ];
    }

    public function rows(): Collection
    {
        return PurchaseOrder::with(['supplier', 'createdBy'])
            ->filter($this->filters)
            ->latest()
            ->latest('id')
            ->get()
            ->map(fn (PurchaseOrder $po) => [
                $po->reference_number,
                $po->supplier->company_name ?? '',
                $po->order_date?->format('Y-m-d'),
                $po->expected_delivery_date?->format('Y-m-d'),
                ucfirst($po->status),
                number_format($po->subtotal, 2, '.', ''),
                number_format($po->tax, 2, '.', ''),
                number_format($po->total, 2, '.', ''),
                $po->createdBy->name ?? '',
            ]);
    }
}