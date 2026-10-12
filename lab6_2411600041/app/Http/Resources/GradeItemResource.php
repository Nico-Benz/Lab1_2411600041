<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_code' => $this->subject_code,
            'subject_name' => $this->subject_name,
            'category' => $this->category,
            'current_grade' => (float) $this->current_grade,
            'low_grade_threshold' => (float) $this->low_grade_threshold,
            'status' => $this->grade_status,
            'status_color' => $this->grade_status_color,
            'url' => route('grade-items.show', $this->id),
            'updated_at' => $this->updated_at?->diffForHumans(),
        ];
    }
}