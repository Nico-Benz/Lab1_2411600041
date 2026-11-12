<?php

namespace App\Models;

use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([InventoryTransactionObserver::class])]
class InventoryTransaction extends Model
{
    public const TYPES = ['in', 'out', 'adjustment'];

    protected $fillable = [
        'product_id',
        'transaction_type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reference_number',
        'reason',
        'notes',
        'user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isStockIn(): bool
    {
        return $this->transaction_type === 'in';
    }

    public function isStockOut(): bool
    {
        return $this->transaction_type === 'out';
    }

    public function isAdjustment(): bool
    {
        return $this->transaction_type === 'adjustment';
    }

    /** Signed change: +5, -3 */
    public function getSignedChangeAttribute(): int
    {
        if ($this->quantity_before !== null && $this->quantity_after !== null) {
            return (int) $this->quantity_after - (int) $this->quantity_before;
        }

        return $this->isStockOut() ? -(int) $this->quantity : (int) $this->quantity;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            'in' => 'Stock In',
            'out' => 'Stock Out',
            default => 'Adjustment',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->transaction_type) {
            'in' => 'success',
            'out' => 'danger',
            default => 'warning',
        };
    }

    public function scopeStockIn(Builder $query): Builder
    {
        return $query->where('transaction_type', 'in');
    }

    public function scopeStockOut(Builder $query): Builder
    {
        return $query->where('transaction_type', 'out');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
