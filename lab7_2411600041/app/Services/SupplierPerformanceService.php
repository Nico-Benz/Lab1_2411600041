<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierPerformanceService
{
    public function calculatePerformance(Supplier $supplier): array
    {
        // "Placed" = naipadala na sa instructor (sent) o natanggap na (received)
        $orders = $supplier->purchaseOrders()
            ->with('items')
            ->whereIn('status', ['sent', 'received'])
            ->get();

        $delivered = $orders->where('status', 'received')->whereNotNull('received_at');

        // On time: natanggap hindi lampas sa expected date
        $onTime = $delivered->filter(fn ($po) => $po->expected_delivery_date
            && $po->received_at->copy()->startOfDay()->lte($po->expected_delivery_date->copy()->startOfDay()));

        // Average delivery time: actual vs quoted (sa araw)
        $actualDays = $delivered->map(fn ($po) => max(0, (int) $po->order_date->copy()->startOfDay()
            ->diffInDays($po->received_at->copy()->startOfDay(), false)));

        $quotedDays = $delivered->filter(fn ($po) => $po->expected_delivery_date)
            ->map(fn ($po) => max(0, (int) $po->order_date->copy()->startOfDay()
                ->diffInDays($po->expected_delivery_date->copy()->startOfDay(), false)));

        // Order accuracy: natanggap na points / inorder na points
        $items = $delivered->flatMap->items;
        $ordered = (float) $items->sum('quantity_ordered');
        $accepted = (float) $items->sum(fn ($i) => min((float) $i->quantity_received, (float) $i->quantity_ordered));

        return [
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->company_name,
            'total_orders' => $orders->count(),
            'delivered_orders' => $delivered->count(),
            'on_time_orders' => $onTime->count(),
            'on_time_rate' => $delivered->count() > 0
                ? round($onTime->count() / $delivered->count() * 100, 1) : null,
            'avg_actual_days' => $actualDays->isNotEmpty() ? round($actualDays->avg(), 1) : null,
            'avg_quoted_days' => $quotedDays->isNotEmpty() ? round($quotedDays->avg(), 1) : null,
            'accuracy_rate' => $ordered > 0 ? round($accepted / $ordered * 100, 1) : null,
            'total_value' => round((float) $orders->sum('total'), 2),
        ];
    }
}