<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Business logic for automatic replenishment.
 *
 * Reorder Point = (Average Daily Usage x Lead Time) + Safety Stock
 *   - Average Daily Usage = total "stock out" of the last 30 days / 30
 *   - Lead Time           = the supplier's lead_time_days
 *   - Safety Stock        = the product's reorder_level (the minimum we never want to go below)
 */
class ReorderService
{
    /** product_id => total stock out in the usage window (loaded once, with ONE query) */
    private ?Collection $usage = null;

    protected function usageTotals(): Collection
    {
        return $this->usage ??= InventoryTransaction::query()
            ->where('transaction_type', 'out')
            ->where('created_at', '>=', now()->subDays($this->usageDays()))
            ->selectRaw('product_id, SUM(quantity) as total_out')
            ->groupBy('product_id')
            ->pluck('total_out', 'product_id');
    }

    protected function usageDays(): int
    {
        return max(1, (int) config('procurement.usage_days', 30));
    }

    public function averageDailyUsage(Product $product): float
    {
        $total = (int) ($this->usageTotals()->get($product->id) ?? 0);

        return round($total / $this->usageDays(), 4);
    }

    public function leadTimeDays(Product $product): int
    {
        return (int) ($product->supplier?->lead_time_days ?? config('procurement.default_lead_time', 7));
    }

    /** (average daily usage x lead time) + safety stock, rounded up */
    public function calculateReorderPoint(Product $product): int
    {
        $usageDuringLeadTime = $this->averageDailyUsage($product) * $this->leadTimeDays($product);

        return (int) ceil($usageDuringLeadTime + (int) $product->reorder_level);
    }

    /**
     * Simplified EOQ: order enough to cover lead time + cover days (+ safety stock),
     * never less than the supplier minimum, always in whole packages.
     */
    public function calculateOptimalOrderQuantity(Product $product): int
    {
        $usage = $this->averageDailyUsage($product);
        $safety = (int) $product->reorder_level;
        $coverDays = (int) config('procurement.cover_days', 30);

        $target = (int) ceil($usage * ($this->leadTimeDays($product) + $coverDays)) + $safety;
        $target = max($target, $safety * 2);                       // even with no usage, keep a healthy level

        $needed = max(0, $target - (int) $product->quantity);
        $quantity = max($needed, max(1, (int) $product->min_order_quantity));
        $package = max(1, (int) $product->package_size);

        return (int) (ceil($quantity / $package) * $package);
    }

    /** Every product whose stock is at or below its reorder point */
    public function getProductsNeedingReorder(): Collection
    {
        return Product::with('supplier')->orderBy('name')->get()
            ->filter(fn (Product $product) => (int) $product->quantity <= $this->calculateReorderPoint($product))
            ->values();
    }

    /** supplier_id => collection of products (products without a supplier are left out) */
    public function groupProductsBySupplier(Collection $products): Collection
    {
        return $products->filter(fn (Product $product) => $product->supplier_id)->groupBy('supplier_id');
    }

    /** True when the product is already on a draft / pending / approved / sent order */
    public function hasOpenOrder(Product $product): bool
    {
        return PurchaseOrderItem::where('product_id', $product->id)
            ->whereHas('purchaseOrder', fn ($query) => $query->open())
            ->exists();
    }

    /**
     * Create ONE draft purchase order per supplier for all products that need a reorder.
     *
     * @return array{orders: Collection, skipped: Collection, needing: int}
     */
    public function generatePurchaseOrders(?User $createdBy = null): array
    {
        $createdBy ??= User::where('role', 'admin')->orderBy('id')->first();

        if (! $createdBy) {
            throw new RuntimeException('No admin user found. Create an admin first so the draft orders have an owner.');
        }

        $needing = $this->getProductsNeedingReorder();
        $skipped = collect();
        $eligible = collect();

        foreach ($needing as $product) {
            $supplier = $product->supplier;

            if (! $supplier || $supplier->trashed() || ! $supplier->is_active) {
                $skipped->push(['sku' => $product->sku, 'name' => $product->name, 'reason' => 'No active supplier']);
                continue;
            }

            if ($this->hasOpenOrder($product)) {
                $skipped->push(['sku' => $product->sku, 'name' => $product->name, 'reason' => 'Open purchase order already exists']);
                continue;
            }

            $eligible->push($product);
        }

        $orders = collect();

        foreach ($this->groupProductsBySupplier($eligible) as $products) {
            $orders->push($this->createDraftOrder($products->first()->supplier, $products, $createdBy));
        }

        return ['orders' => $orders, 'skipped' => $skipped, 'needing' => $needing->count()];
    }

    protected function createDraftOrder(Supplier $supplier, Collection $products, User $createdBy): PurchaseOrder
    {
        return DB::transaction(function () use ($supplier, $products, $createdBy) {
            $notes = 'Auto-generated by inventory:check-reorder.';

            $order = PurchaseOrder::create([
                'reference_number' => PurchaseOrder::generateReferenceNumber(),
                'supplier_id' => $supplier->id,
                'status' => 'draft',
                'order_date' => today(),
                'expected_delivery_date' => today()->addDays((int) $supplier->lead_time_days),
                'notes' => $notes,
                'terms' => config('procurement.default_terms'),
                'is_auto_generated' => true,
                'created_by' => $createdBy->id,
            ]);

            foreach ($products as $product) {
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $this->calculateOptimalOrderQuantity($product),
                    'unit_price' => $product->costFor($supplier->id),   // price saved at the time of the order
                ]);
            }

            $order->calculateTotal();

            if ((float) $order->subtotal < (float) $supplier->minimum_order_amount) {
                $order->notes = $notes . ' Below the supplier minimum order amount of PHP '
                    . number_format((float) $supplier->minimum_order_amount, 2) . '.';
                $order->saveQuietly();
            }

            return $order->load(['supplier', 'items.product']);
        });
    }
}
