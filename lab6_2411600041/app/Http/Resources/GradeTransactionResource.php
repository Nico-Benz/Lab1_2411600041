<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_code' => $this->gradeItem->subject_code,
            'subject_name' => $this->gradeItem->subject_name,
            'type' => $this->type,
            'quantity' => (float) $this->quantity,
            'previous_grade' => (float) $this->previous_grade,
            'new_grade' => (float) $this->new_grade,
            'user' => $this->user->name,
            'created_at' => $this->created_at->format('M d, Y h:i A'),
            'url' => route('grade-transactions.show', $this->id),
        ];
    }
}