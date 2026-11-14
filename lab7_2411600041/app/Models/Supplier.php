<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes; // delete() only fills deleted_at, the row stays in the table

    /** Purchase order statuses that are still "open" (not finished yet) */
    public const OPEN_STATUSES = ['draft', 'pending', 'approved', 'sent'];

    protected $fillable = [
        'name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'lead_time_days',
        'minimum_order_amount',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lead_time_days' => 'integer',
            'minimum_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // ---------- Relationships ----------

    /** Products whose main (default) supplier is this supplier */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** Many-to-many: every product this supplier can supply, with the agreed price */
    public function suppliedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_supplier')
            ->withPivot(['supplier_price', 'supplier_sku', 'is_preferred'])
            ->withTimestamps();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /** Polymorphic: notes written about this supplier */
    public function stickyNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    // ---------- Query scopes ----------

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Suppliers that can deliver within the given number of days */
    public function scopeWithLeadTime(Builder $query, int $days): Builder
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('contact_person', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }

    // ---------- Helpers ----------

    /** "Ramon Dela Cruz · email · phone" */
    public function getFullContactAttribute(): string
    {
        return "{$this->contact_person} · {$this->email} · {$this->phone}";
    }

    /** On-time delivery percentage (null when nothing was delivered yet) */
    public function getPerformanceRatingAttribute(): ?float
    {
        $delivered = $this->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('received_at')
            ->whereNotNull('expected_delivery_date')
            ->get(['id', 'received_at', 'expected_delivery_date']);

        if ($delivered->isEmpty()) {
            return null;
        }

        $onTime = $delivered->filter(
            fn (PurchaseOrder $po) => $po->received_at->toDateString() <= $po->expected_delivery_date->toDateString()
        )->count();

        return round($onTime / $delivered->count() * 100, 1);
    }

    public function openOrdersCount(): int
    {
        return $this->purchaseOrders()->whereIn('status', self::OPEN_STATUSES)->count();
    }
}
