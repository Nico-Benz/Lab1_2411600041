<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'address',
        'payment_terms',
        'lead_time_days',
        'minimum_order_amount',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'lead_time_days' => 'integer',
        'minimum_order_amount' => 'decimal:2',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(GradeItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeWithLeadTime(Builder $query, int $days): Builder
    {
        return $query->where('lead_time_days', '<=', $days);
    }

    public function getFullContactAttribute(): string
    {
        return trim($this->contact_person
            . ' | ' . $this->email
            . ($this->phone ? ' | ' . $this->phone : ''));
    }

    // On-time delivery percentage (null kung wala pang natanggap na PO)
    public function getPerformanceRatingAttribute(): ?float
    {
        $received = $this->purchaseOrders()
            ->where('status', 'received')
            ->whereNotNull('received_at')
            ->whereNotNull('expected_delivery_date')
            ->get(['expected_delivery_date', 'received_at']);

        if ($received->isEmpty()) {
            return null;
        }

        $onTime = $received->filter(
            fn ($po) => $po->received_at->toDateString() <= $po->expected_delivery_date->toDateString()
        )->count();

        return round(($onTime / $received->count()) * 100, 1);
    }
}