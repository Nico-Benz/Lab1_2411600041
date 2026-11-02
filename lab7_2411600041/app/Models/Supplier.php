<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
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

    /**
     * Many-to-many: mga subject na puwedeng ituro ng instructor na ito
     * bilang alternate. Ginagamit ang pivot table na grade_item_supplier.
     */
    public function alternateItems(): BelongsToMany
    {
        return $this->belongsToMany(GradeItem::class, 'grade_item_supplier')
            ->withPivot('is_preferred')
            ->withTimestamps();
    }

    // Mga sticky note na nakadikit sa supplier na ito
       public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
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