<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cheque extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_id',
        'cheque_number',
        'cheque_date',
        'vendor_id',
        'amount',
        'amount_in_words',
        'status',           // 'active', 'voided', 'printed'
        'printed_at',
        'voided_at',
        'void_reason',
    ];

    protected $casts = [
        'cheque_date' => 'date',
        'amount'      => 'decimal:2',
        'printed_at'  => 'datetime',
        'voided_at'   => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Boot method to set default status.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cheque) {
            if (!$cheque->status) {
                $cheque->status = 'active';
            }
        });
    }

    /**
     * Cheque belongs to a bank.
     */
    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    /**
     * Cheque belongs to a vendor (payee).
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Cheque has many vouchers through pivot.
     */
    public function vouchers(): BelongsToMany
    {
        return $this->belongsToMany(Voucher::class, 'cheque_voucher')
            ->withTimestamps();
    }

    /**
     * Scope for active cheques.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for voided cheques.
     */
    public function scopeVoided($query)
    {
        return $query->where('status', 'voided');
    }

    /**
     * Void the cheque and release vouchers.
     */
    public function void(string $reason = ''): void
    {
        if ($this->status === 'voided') {
            throw new \Exception('Cheque is already voided.');
        }

        \DB::transaction(function () use ($reason) {
            // Release all attached vouchers
            Voucher::whereIn('id', $this->vouchers()->pluck('voucher_id'))
                ->update(['is_paid' => false]);

            // Update cheque status
            $this->update([
                'status'      => 'voided',
                'voided_at'   => now(),
                'void_reason' => $reason,
            ]);
        });
    }

    /**
     * Mark cheque as printed.
     */
    public function markAsPrinted(): void
    {
        $this->update([
            'printed_at' => now(),
        ]);
    }

    /**
     * Check if cheque is voidable.
     */
    public function isVoidable(): bool
    {
        return $this->status === 'active';
    }

    // Add this relationship
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }
}
