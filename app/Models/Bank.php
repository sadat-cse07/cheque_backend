<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch',
        'ifsc_code',
        'alignment',        // JSON: stores X,Y coordinates for cheque printing
        'is_active',        // boolean
    ];

    protected $casts = [
        'alignment' => 'array',     // Auto JSON encode/decode
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * A bank has many cheques.
     */
    public function cheques(): HasMany
    {
        return $this->hasMany(Cheque::class);
    }

    /**
     * Scope for active banks only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the alignment for a specific field.
     */
    public function getFieldAlignment(string $field): ?array
    {
        return $this->alignment[$field] ?? null;
    }

    /**
     * Get default alignment structure.
     */
    public static function defaultAlignment(): array
    {
        return [
            'date'          => ['x' => 120, 'y' => 40,  'unit' => 'mm', 'font_size' => 12],
            'payee'         => ['x' => 25,  'y' => 55,  'unit' => 'mm', 'font_size' => 12],
            'amount'        => ['x' => 140, 'y' => 55,  'unit' => 'mm', 'font_size' => 14],
            'amount_words'  => ['x' => 25,  'y' => 70,  'unit' => 'mm', 'font_size' => 10],
            'cheque_no'     => ['x' => 140, 'y' => 20,  'unit' => 'mm', 'font_size' => 12],
        ];
    }
}
