<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'brand_id',
        'category_id',
        'description',
        'quantity',
        'reorder_level',
        'unit_price',
        'supplier_id',
        'package_size',
        'min_order_quantity',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Main (default) supplier. withTrashed(): still shows the name if the supplier was soft deleted. */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    /** Many-to-many: all suppliers that can supply this product (with their own price) */
    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier')
            ->withPivot(['supplier_price', 'supplier_sku', 'is_preferred'])
            ->withTimestamps();
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity == 0;
    }

    public function isLowStock(): bool
    {
        return $this->quantity > 0 && $this->quantity <= $this->reorder_level;
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(InventoryTransaction::class)->latestOfMany();
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Out of Stock';
        }

        return $this->isLowStock() ? 'Low Stock' : 'In Stock';
    }

    public function getStockStatusColorAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'danger';
        }

        return $this->isLowStock() ? 'warning' : 'success';
    }

    /** Products at or below the reorder level (includes out of stock). */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    public function scopeInCategory(Builder $query, $category): Builder
    {
        return $query->where('category_id', $category);
    }

    // ---------- Lab 7 helpers and scopes ----------

    /** Price this supplier agreed for the product; falls back to the normal unit price */
    public function costFor(int $supplierId): float
    {
        $row = $this->suppliers()->withTrashed()->where('suppliers.id', $supplierId)->first();

        return (float) ($row?->pivot?->supplier_price ?? $this->unit_price);
    }

    /** Search by name, SKU, brand or supplier name */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', $like))
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', $like));
        });
    }

    public function scopeFromSupplier(Builder $query, $supplierId): Builder
    {
        return $supplierId ? $query->where('supplier_id', $supplierId) : $query;
    }

    /** $status: in = above reorder level, low = at/below reorder level but not zero, out = zero */
    public function scopeStockStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'out' => $query->where('quantity', '<=', 0),
            'low' => $query->where('quantity', '>', 0)->whereColumn('quantity', '<=', 'reorder_level'),
            'in' => $query->whereColumn('quantity', '>', 'reorder_level'),
            default => $query,
        };
    }
}
