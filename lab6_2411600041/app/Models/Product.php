<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'supplier',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
}
