<?php

namespace App\Observers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

class InventoryTransactionObserver
{
    /**
     * BEFORE the transaction row is saved: check the numbers.
     * Using "creating" (not "created") lets us reject a bad stock-out
     * before anything is written to the database.
     */
    public function creating(InventoryTransaction $transaction): void
    {
        $product = Product::lockForUpdate()->findOrFail($transaction->product_id);
        $before = (int) $product->quantity;
        $quantity = (int) $transaction->quantity;

        switch ($transaction->transaction_type) {
            case 'in':
                $after = $before + $quantity;
                break;

            case 'out':
                if ($quantity > $before) {
                    // Option A: throw an exception (the DB transaction rolls back)
                    throw new InvalidArgumentException(
                        "Not enough stock for {$product->name}. Only {$before} available."
                    );
                }
                $after = $before - $quantity;
                break;

            case 'adjustment':
                // For an adjustment, quantity_after is the physical count
                $after = (int) $transaction->quantity_after;
                if ($transaction->quantity_after === null || $after < 0) {
                    throw new InvalidArgumentException('Physical count cannot be negative.');
                }
                $transaction->quantity = abs($after - $before);
                break;

            default:
                throw new InvalidArgumentException('Unknown transaction type.');
        }

        $transaction->quantity_before = $before;
        $transaction->quantity_after = $after;
    }

    /**
     * AFTER the transaction row is saved: update the product quantity,
     * then send a low stock alert if the product just fell to the reorder level.
     */
    public function created(InventoryTransaction $transaction): void
    {
        $product = Product::findOrFail($transaction->product_id);
        $product->quantity = $transaction->quantity_after; // new stock
        $product->save();

        $before = (int) $transaction->quantity_before;
        $after = (int) $transaction->quantity_after;

        // Only alert when the product CROSSES the reorder level (no duplicate alerts)
        $crossedDown = $before > $product->reorder_level && $after <= $product->reorder_level;

        if ($crossedDown) {
            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new LowStockNotification($product));
        }
    }
}
