<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLog;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    /**
     * Sample purchase orders: old received ones (so the supplier performance report has data)
     * and a few current ones in every status.
     * Events are off, so no emails are queued and no stock is added for these history rows.
     */
    public function run(): void
    {
        if (PurchaseOrder::withTrashed()->exists()) {
            return; // already seeded
        }

        $admin = User::where('role', 'admin')->orderBy('id')->first();

        if (! $admin) {
            return;
        }

        $people = [
            'admin' => $admin,
            'staff' => User::where('role', 'staff')->first() ?? $admin,
            'manager' => User::where('role', 'manager')->first() ?? $admin,
        ];

        // [supplier, status, days ago, lines [sku, qty], delivery delta in days (+ late, - early), short delivery?, created by]
        $orders = [
            // ---- old, received (history for the performance report) ----
            ['Manila Paper Mart', 'received', 90, [['PP-001', 100], ['PP-002', 50]], 0, false, 'staff'],
            ['Manila Paper Mart', 'received', 60, [['PP-003', 30]], -1, false, 'admin'],
            ['Manila Paper Mart', 'received', 35, [['FO-001', 60], ['FO-003', 20]], 3, true, 'staff'],
            ['ScholarPoint Trading', 'received', 80, [['WI-002', 100], ['WI-003', 60]], 0, false, 'staff'],
            ['ScholarPoint Trading', 'received', 50, [['WI-001', 20], ['DA-001', 15]], 2, false, 'admin'],
            ['ScholarPoint Trading', 'received', 25, [['DA-002', 80]], 0, false, 'staff'],
            ['Bright Craft Supply', 'received', 70, [['AC-001', 30], ['AC-003', 15]], 1, false, 'admin'],
            ['Bright Craft Supply', 'received', 40, [['AC-001', 20]], -1, false, 'staff'],
            ['TechMart Supplies', 'received', 65, [['MM-001', 6], ['CP-002', 20]], 0, false, 'admin'],
            ['TechMart Supplies', 'received', 30, [['CP-002', 10], ['CP-001', 12]], 0, true, 'staff'],
            ['Campus Bags Wholesale', 'received', 75, [['SB-001', 10]], 4, false, 'admin'],

            // ---- current orders, one for each status ----
            ['ScholarPoint Trading', 'sent', 6, [['WI-004', 30], ['MM-002', 40]], null, false, 'staff'],       // ready to receive
            ['Campus Bags Wholesale', 'approved', 3, [['SB-001', 10]], null, false, 'staff'],                  // ready to mark as sent
            ['TechMart Supplies', 'pending', 2, [['MM-001', 8]], null, false, 'staff'],                        // waiting for approval
            ['Manila Paper Mart', 'draft', 1, [['PP-003', 20]], null, false, 'admin'],
            ['ScholarPoint Trading', 'cancelled', 12, [['DA-004', 10]], null, false, 'admin'],
            ['Bright Craft Supply', 'rejected', 15, [['AC-003', 40]], null, false, 'staff'],
        ];

        PurchaseOrder::withoutEvents(function () use ($orders, $people) {
            foreach ($orders as $spec) {
                $this->makeOrder($spec, $people);
            }
        });
    }

    private function makeOrder(array $spec, array $people): void
    {
        [$supplierName, $status, $daysAgo, $lines, $delta, $short, $by] = $spec;

        $supplier = Supplier::where('name', $supplierName)->firstOrFail();
        $orderDate = now()->subDays($daysAgo)->startOfDay();
        $expected = $orderDate->copy()->addDays((int) $supplier->lead_time_days);
        $receivedAt = $status === 'received' ? $expected->copy()->addDays((int) $delta)->setTime(14, 0) : null;

        $order = PurchaseOrder::create([
            'reference_number' => PurchaseOrder::generateReferenceNumber($orderDate),
            'supplier_id' => $supplier->id,
            'status' => $status,
            'order_date' => $orderDate,
            'expected_delivery_date' => $expected,
            'notes' => $status === 'rejected' ? null : 'Sample data for Lab 7.',
            'terms' => config('procurement.default_terms'),
            'rejection_reason' => $status === 'rejected' ? 'Quantity is too high for the current budget. Please order less.' : null,
            'is_auto_generated' => false,
            'created_by' => $people[$by]->id,
            'approved_by' => in_array($status, ['approved', 'sent', 'received'], true) ? $people['manager']->id : null,
            'approved_at' => in_array($status, ['approved', 'sent', 'received'], true) ? $orderDate->copy()->addDay()->setTime(10, 0) : null,
            'sent_at' => in_array($status, ['sent', 'received'], true) ? $orderDate->copy()->addDays(2)->setTime(9, 0) : null,
            'received_at' => $receivedAt,
        ]);

        foreach ($lines as $index => [$sku, $quantity]) {
            $product = Product::where('sku', $sku)->firstOrFail();

            $order->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->costFor($supplier->id),
                'received_quantity' => $status === 'received' ? ($short && $index === 0 ? $quantity - 3 : $quantity) : 0,
            ]);
        }

        $order->calculateTotal();

        $this->seedLogs($order, $status, $orderDate, $receivedAt, $people);
    }

    /** Approval history rows for the sample orders */
    private function seedLogs(PurchaseOrder $order, string $status, $orderDate, $receivedAt, array $people): void
    {
        $flow = [
            'draft' => [],
            'pending' => ['pending'],
            'approved' => ['pending', 'approved'],
            'rejected' => ['pending', 'rejected'],
            'sent' => ['pending', 'approved', 'sent'],
            'received' => ['pending', 'approved', 'sent', 'received'],
            'cancelled' => ['cancelled'],
        ];

        $dayOffsets = ['pending' => 0, 'approved' => 1, 'rejected' => 1, 'sent' => 2, 'cancelled' => 1];

        $this->log($order, 'created', null, 'draft', $orderDate->copy()->setTime(8, 0), $order->created_by);

        $from = 'draft';

        foreach ($flow[$status] as $step) {
            $when = $step === 'received' ? $receivedAt : $orderDate->copy()->addDays($dayOffsets[$step])->setTime(10, 0);
            $actor = match ($step) {
                'pending' => $order->created_by,
                'approved', 'rejected', 'sent' => $people['manager']->id,
                'received' => $people['staff']->id,
                default => $order->created_by,
            };

            $this->log($order, $step, $from, $step, $when, $actor, $step === 'rejected' ? $order->rejection_reason : null);
            $from = $step;
        }
    }

    private function log(PurchaseOrder $order, string $action, ?string $from, string $to, $when, ?int $userId, ?string $comment = null): void
    {
        $log = new PurchaseOrderLog([
            'purchase_order_id' => $order->id,
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
        ]);

        $log->created_at = $when;
        $log->updated_at = $when;
        $log->save();
    }
}
