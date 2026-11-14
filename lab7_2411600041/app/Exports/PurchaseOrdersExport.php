<?php

namespace App\Exports;

use App\Services\SearchService;
use Illuminate\Http\Request;

class PurchaseOrdersExport implements ReportExport
{
    public function __construct(private Request $request)
    {
    }

    public function title(): string
    {
        return 'Purchase Orders';
    }

    public function filename(): string
    {
        return 'purchase-orders-' . now()->format('Ymd-His');
    }

    public function headings(): array
    {
        return ['Reference', 'Order Date', 'Expected Delivery', 'Supplier', 'Status', 'Items', 'Subtotal', 'Tax', 'Total', 'Created By'];
    }

    public function rows(): iterable
    {
        $orders = (new SearchService)->purchaseOrders($this->request)
            ->with(['supplier', 'createdBy'])
            ->withCount('items')
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->get();

        foreach ($orders as $order) {
            yield [
                $order->reference_number,
                $order->order_date->format('Y-m-d'),
                $order->expected_delivery_date?->format('Y-m-d') ?? '',
                $order->supplier->name ?? '',
                ucfirst($order->status),
                $order->items_count,
                number_format((float) $order->subtotal, 2, '.', ''),
                number_format((float) $order->tax, 2, '.', ''),
                number_format((float) $order->total, 2, '.', ''),
                $order->createdBy->name ?? '',
            ];
        }
    }
}
