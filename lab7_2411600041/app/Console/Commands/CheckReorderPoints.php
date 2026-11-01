<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\PurchaseOrdersGenerated;
use App\Services\ReorderService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckReorderPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:check-reorder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check which subjects are below their reorder point and create draft purchase orders for them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking reorder points...');

        // 1. I-instantiate ang ReorderService
        $service = new ReorderService();

        // 2. Tawagin ang generatePurchaseOrders()
        $result = $service->generatePurchaseOrders();

        // 3. I-output ang resulta
        $created = $result['created'];

        if (count($created) === 0) {
            $this->warn('No new purchase orders were created.');
        } else {
            $rows = [];

            foreach ($created as $order) {
                $rows[] = [
                    $order->reference_number,
                    $order->supplier->company_name ?? '-',
                    $order->items()->count(),
                    number_format($order->total, 2),
                ];
            }

            $this->table(['Reference', 'Supplier', 'Items', 'Total (PHP)'], $rows);
            $this->info(count($created) . ' draft purchase order(s) created.');

            // 4. Mag-send ng notification sa administrators
            $admins = User::where('role', 'admin')->get();

            Notification::send($admins, new PurchaseOrdersGenerated(
                count($created),
                collect($created)->pluck('reference_number')->all()
            ));

            $this->info('Notified ' . $admins->count() . ' administrator(s).');
        }

        if (! empty($result['no_supplier'])) {
            $this->warn('Skipped (no supplier): ' . implode(', ', $result['no_supplier']));
        }

        if (! empty($result['already_open'])) {
            $this->warn('Skipped (already has an open PO): ' . implode(', ', $result['already_open']));
        }

        return Command::SUCCESS;
    }
}