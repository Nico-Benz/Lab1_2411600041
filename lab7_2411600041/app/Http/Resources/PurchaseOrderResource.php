<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference_number' => $this->reference_number,
            'supplier' => $this->supplier->name ?? null,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'total' => (float) $this->total,
            'order_date' => $this->order_date?->format('M d, Y'),
            'url' => route('purchase-orders.show', $this->id),
        ];
    }
}
