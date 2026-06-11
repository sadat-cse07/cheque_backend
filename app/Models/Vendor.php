<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'contact_person',
        'phone',
        'email',
        'status',         // 'active' or 'inactive'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * A vendor has many vouchers.
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * A vendor has many cheques (as payee).
     */
    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    /**
     * Scope for active vendors only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get total unpaid amount for this vendor.
     */
    public function getUnpaidAmountAttribute(): float
    {
        return $this->vouchers()->where('is_paid', false)->sum('amount');
    }

    /**
     * Get total paid amount for this vendor.
     */
    public function getPaidAmountAttribute(): float
    {
        return $this->vouchers()->where('is_paid', true)->sum('amount');
    }
}
