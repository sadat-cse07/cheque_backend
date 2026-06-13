<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    protected $fillable = [
        'name', 'alignment', 'branch', 'ifsc_code', 'is_active',
    ];

    protected $casts = [
        'alignment' => 'array',
        'is_active' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
