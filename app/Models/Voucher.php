<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'rank',
        'month',
        'year',
        'is_used',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_used' => 'boolean',
        ];
    }

    /**
     * Konstanta nilai voucher per ranking.
     */
    const VOUCHER_AMOUNTS = [
        1 => 100000, // Top 1: Rp100.000
        2 => 50000,  // Top 2: Rp50.000
        3 => 20000,  // Top 3: Rp20.000
    ];

    /**
     * User pemilik voucher ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
