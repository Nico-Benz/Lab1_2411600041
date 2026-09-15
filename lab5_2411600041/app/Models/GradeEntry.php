<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeEntry extends Model
{
    protected $fillable = [
        'grade_item_id',
        'user_id',
        'entry_type',
        'score',
        'status',
        'semester',
        'reference_note',
        'entry_date',
    ];

    public function gradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}