<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLog extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'user_id',
        'action',
        'from_status',
        'to_status',
        'comment',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Created',
            'pending' => 'Submitted for approval',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'sent' => 'Sent to supplier',
            'received' => 'Fully received',
            'cancelled' => 'Cancelled',
            'items_received' => 'Items received',
            default => ucfirst($this->action),
        };
    }
}
