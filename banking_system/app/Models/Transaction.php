<?php

namespace App\Models;

use App\Observers\AccountObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy(AccountObserver::class)]
class Transaction extends Model
{
    protected $fillable = [
        'account_id',
        'type',
        'amount',
        'reference_number',
        'notes',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}