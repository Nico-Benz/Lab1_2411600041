<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'reference_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'tax',
        'total',
        'notes',
        'terms',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'received_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // ---------- Relationships ----------

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ---------- Query scopes ----------

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['received', 'cancelled']);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // ---------- Helper methods ----------

    public function calculateTotal(): float
    {
        $subtotal = $this->items()->get()->sum(fn ($item) => $item->line_total);

        $this->subtotal = $subtotal;
        $this->total = $subtotal + (float) $this->tax;
        $this->save();

        return (float) $this->total;
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'sent';
    }

    public static function generateReferenceNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ymd') . '-';
        $count = static::where('reference_number', 'like', $prefix . '%')->count();

        do {
            $count++;
            $number = $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
        } while (static::where('reference_number', $number)->exists());

        return $number;
    }
}