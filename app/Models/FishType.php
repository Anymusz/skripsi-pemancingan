<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FishType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price_per_kg',
        'stock_kg',
        'min_stock_threshold',
        'last_restocked_at',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'stock_kg' => 'decimal:2',
            'min_stock_threshold' => 'decimal:2',
            'last_restocked_at' => 'datetime',
        ];
    }

    /**
     * Cek apakah stok di bawah threshold minimum.
     */
    public function isBelowThreshold(): bool
    {
        return $this->stock_kg < $this->min_stock_threshold;
    }
}
