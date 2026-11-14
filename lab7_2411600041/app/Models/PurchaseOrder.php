<?php

namespace App\Models;

use App\Observers\PurchaseOrderObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PurchaseOrderObserver::class])]
class PurchaseOrder extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft', 'pending', 'approved', 'rejected', 'sent', 'received', 'cancelled'];

    /** Not finished yet: still waiting for approval, sending or delivery */
    public const OPEN_STATUSES = ['draft', 'pending', 'approved', 'sent'];

    protected $fillable = [
        'reference_number',
        'supplier_id',
        'status',
        'order_date',
        'expected_delivery_date',
        'subtotal',
        'tax',
        'total',
        'notes',
        'terms',
        'rejection_reason',
        'is_auto_generated',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'expected_delivery_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'is_auto_generated' => 'boolean',
            'approved_at' => 'datetime',
            'sent_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    // ---------- Relationships ----------

    /** withTrashed(): the order still shows its supplier even after the supplier was soft deleted */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PurchaseOrderLog::class)->orderBy('created_at')->orderBy('id');
    }

    /** Polymorphic notes (named stickyNotes because the table already has a "notes" column) */
    public function stickyNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    // ---------- Query scopes ----------

    /** Orders waiting for approval */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /** Orders not yet received or cancelled (rejected orders are finished too) */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['received', 'cancelled', 'rejected']);
    }

    /** Same as "open": the order can still bring stock in */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function scopeByStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeForSupplier(Builder $query, $supplierId): Builder
    {
        return $supplierId ? $query->where('supplier_id', $supplierId) : $query;
    }

    public function scopeDateBetween(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('order_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('order_date', '<=', $to));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if ($term === null || trim($term) === '') {
            return $query;
        }

        $like = '%' . trim($term) . '%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('reference_number', 'like', $like)
                ->orWhereHas('supplier', fn (Builder $s) => $s->where('name', 'like', $like));
        });
    }

    // ---------- Helper methods ----------

    /** Recalculate subtotal, tax and total from the line items and save them */
    public function calculateTotal(): static
    {
        $subtotal = (float) $this->items()->get()->sum(fn (PurchaseOrderItem $item) => $item->line_total);
        $tax = round($subtotal * (float) config('procurement.tax_rate', 0.12), 2);

        $this->subtotal = round($subtotal, 2);
        $this->tax = $tax;
        $this->total = round($subtotal + $tax, 2);
        $this->saveQuietly();

        return $this;
    }

    public function canBeEdited(): bool
    {
        return $this->status === 'draft';
    }

    public function canBeApproved(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeSent(): bool
    {
        return $this->status === 'approved';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'approved'], true);
    }

    /** Only a sent order can receive items, and only while something is still outstanding */
    public function canBeReceived(): bool
    {
        return $this->status === 'sent'
            && $this->items->contains(fn (PurchaseOrderItem $item) => $item->outstanding_quantity > 0);
    }

    public function isPartiallyReceived(): bool
    {
        return $this->status === 'sent'
            && $this->items->sum('received_quantity') > 0
            && $this->items->contains(fn (PurchaseOrderItem $item) => $item->outstanding_quantity > 0);
    }

    /** PO-20261113-0001 : PO + date + running number of that day (deleted orders are counted too) */
    public static function generateReferenceNumber(?Carbon $date = null): string
    {
        $prefix = 'PO-' . ($date ?? now())->format('Ymd') . '-';

        $number = static::withTrashed()->where('reference_number', 'like', $prefix . '%')->count();

        do {
            $number++;
            $reference = $prefix . str_pad((string) $number, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('reference_number', $reference)->exists());

        return $reference;
    }

    /** Write one line in the approval / status history */
    public function addLog(string $action, ?string $from = null, ?string $to = null, ?string $comment = null, ?int $userId = null): PurchaseOrderLog
    {
        return $this->statusLogs()->create([
            'user_id' => $userId,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'comment' => $comment,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'sent' && $this->relationLoaded('items') && $this->isPartiallyReceived()) {
            return 'Sent (partial)';
        }

        return ucfirst($this->status);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'pending' => 'warning',
            'approved' => 'primary',
            'sent' => 'info',
            'received' => 'success',
            'rejected' => 'danger',
            default => 'dark', // cancelled
        };
    }
}
