<?php

namespace App\Models;

use App\Observers\GradeTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


#[ObservedBy([GradeTransactionObserver::class])]
class GradeTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'grade_item_id', 'user_id', 'type', 'quantity',
        'previous_grade', 'new_grade', 'reference_number', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'previous_grade' => 'decimal:2',
        'new_grade' => 'decimal:2',
    ];

    public function gradeItem(): BelongsTo
    {
        return $this->belongsTo(GradeItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeIncrease($query)
    {
        return $query->where('type', 'increase');
    }

    public function scopeDecrease($query)
    {
        return $query->where('type', 'decrease');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }
}