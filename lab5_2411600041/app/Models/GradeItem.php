<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GradeItem extends Model
{
    protected $fillable = [
        'subject_code',
        'subject_name',
        'description',
        'category',
        'current_grade',
        'low_grade_threshold',
        'units',
        'instructor',
        'status',
    ];

    public function gradeEntries(): HasMany
    {
        return $this->hasMany(GradeEntry::class);
    }
    public function isBelowThreshold(): bool
{
    return $this->current_grade > 0 && $this->current_grade < $this->low_grade_threshold;
}

public function isPending(): bool
{
    return $this->current_grade == 0;
}
}