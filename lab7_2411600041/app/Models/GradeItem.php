<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'supplier_id',
        'status',
    ];
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GradeTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(GradeTransaction::class)->latestOfMany();
    }

    public function isBelowThreshold(): bool
    {
        return $this->current_grade > 0 && $this->current_grade < $this->low_grade_threshold;
    }

    public function isPending(): bool
    {
        return $this->current_grade == 0;
    }

    public function getGradeStatusAttribute(): string
    {
        if ($this->isPending()) {
            return 'Pending';
        }

        return $this->isBelowThreshold() ? 'At Risk' : 'Passed';
    }

    public function getGradeStatusColorAttribute(): string
    {
        return match ($this->grade_status) {
            'Pending' => 'secondary',
            'At Risk' => 'danger',
            default => 'success',
        };
    }

    public function scopeAtRisk($query)
    {
        return $query->where('current_grade', '>', 0)
            ->whereColumn('current_grade', '<', 'low_grade_threshold');
    }

    public function scopePending($query)
    {
        return $query->where('current_grade', 0);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeFilter($query, array $filters)
    {
        return $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($w) use ($search) {
                    $w->where('subject_name', 'like', "%{$search}%")
                      ->orWhere('subject_code', 'like', "%{$search}%");
                });
            })
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when($filters['supplier_id'] ?? null, fn ($q, $supplierId) => $q->where('supplier_id', $supplierId))
            ->when($filters['status'] ?? null, function ($q, $status) {
                match ($status) {
                    'at_risk' => $q->atRisk(),
                    'pending' => $q->pending(),
                    'passed' => $q->where('current_grade', '>', 0)
                        ->whereColumn('current_grade', '>=', 'low_grade_threshold'),
                    default => null,
                };
            });
    }
}