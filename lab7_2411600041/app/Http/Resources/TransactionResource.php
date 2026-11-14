<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->created_at->format('M d, Y g:i A'),
            'product' => $this->product->name ?? null,
            'product_url' => $this->product ? route('products.show', $this->product_id) : null,
            'type' => $this->type_label,
            'type_color' => $this->type_color,
            'change' => $this->signed_change,
            'quantity_after' => $this->quantity_after,
            'user' => $this->user->name ?? null,
        ];
    }
}
