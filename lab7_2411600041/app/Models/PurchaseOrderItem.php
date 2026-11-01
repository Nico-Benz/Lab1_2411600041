<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'grade_item_id',
        'quantity_ordered',
        'unit_price',
        'quantity_received',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'quantity_received' => 'decimal:2',
    ];

    // ---------- Relationships ----------

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    // "Product" natin ay GradeItem (subject)
    public function product(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class, 'grade_item_id');
    }

    // ---------- Helper methods ----------

    public function getLineTotalAttribute(): float
    {
        return (float) $this->quantity_ordered * (float) $this->unit_price;
    }

    public function getOutstandingQuantityAttribute(): float
    {
        return max(0, (float) $this->quantity_ordered - (float) $this->quantity_received);
    }
}