<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_date',
        'particulars',
        'voucher_name',
        'vendor_id',
        'amount',
        'is_paid',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'amount'        => 'decimal:2',
        'is_paid'       => 'boolean',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    /**
     * Voucher belongs to a vendor.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Voucher can be attached to many cheques (rare, but possible if a cheque pays part).
     * Usually one-to-many via pivot, but we support direct relation.
     */
    public function cheques(): BelongsToMany
    {
        return $this->belongsToMany(Cheque::class, 'cheque_voucher')
            ->withTimestamps();
    }

    /**
     * Scope for unpaid vouchers.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope for paid vouchers.
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Check if voucher can be paid.
     */
    public function canBePaid(): bool
    {
        return !$this->is_paid;
    }

    /**
     * Mark voucher as paid.
     */
    public function markAsPaid(): void
    {
        if ($this->canBePaid()) {
            $this->update(['is_paid' => true]);
        }
    }

    /**
     * Mark voucher as unpaid.
     */
    public function markAsUnpaid(): void
    {
        if ($this->is_paid) {
            $this->update(['is_paid' => false]);
        }
    }
}
