<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Note extends Model
{
    protected $fillable = ['user_id', 'body'];

    // Saan nakadikit ang note? (Supplier o PurchaseOrder)
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    // Sino ang sumulat ng note?
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}