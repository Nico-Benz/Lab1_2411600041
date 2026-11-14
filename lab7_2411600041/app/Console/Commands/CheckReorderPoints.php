<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\ReorderPurchaseOrdersNotification;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckReorderPoints extends Command
{
    protected $signature = 'inventory:check-reorder';

    protected $description = 'Find products at or below their reorder point and create draft purchase orders (one per supplier)';

    public function handle(ReorderService $service): int
    {
        $this->info('Checking reorder points...');

        try {
            $result = $service->generatePurchaseOrders();
        } catch (Throwable $e) {
            $this->error('Reorder check failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $orders = $result['orders'];
        $skipped = $result['skipped'];

        $this->line("Products at or below their reorder point: {$result['needing']}");

        if ($orders->isEmpty()) {
            $this->info('No new purchase orders were needed.');
        } else {
            $this->table(
                ['Reference', 'Supplier', 'Items', 'Total (PHP)'],
                $orders->map(fn ($po) => [
                    $po->reference_number,
                    $po->supplier->name,
                    $po->items->count(),
                    number_format((float) $po->total, 2),
                ])->all()
            );
            $this->info($orders->count() . ' draft purchase order(s) created.');

            // Tell the administrators (shows in the bell)
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new ReorderPurchaseOrdersNotification($orders->pluck('reference_number')->all()));
        }

        if ($skipped->isNotEmpty()) {
            $this->warn('Skipped products:');
            $this->table(['SKU', 'Product', 'Reason'], $skipped->map(fn ($row) => [$row['sku'], $row['name'], $row['reason']])->all());
        }

        return self::SUCCESS;
    }
}
