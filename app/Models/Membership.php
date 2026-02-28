<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tier',
        'total_points',
    ];

    /**
     * Konstanta tier dan threshold poin.
     */
    const TIER_REGULAR = 'regular';
    const TIER_BRONZE = 'bronze';
    const TIER_SILVER = 'silver';
    const TIER_GOLD = 'gold';

    const TIER_THRESHOLDS = [
        self::TIER_REGULAR => 0,
        self::TIER_BRONZE  => 50,
        self::TIER_SILVER  => 400,
        self::TIER_GOLD    => 800,
    ];

    const TIER_DISCOUNTS = [
        self::TIER_REGULAR => 0,
        self::TIER_BRONZE  => 0.01,  // 1%
        self::TIER_SILVER  => 0.03,  // 3%
        self::TIER_GOLD    => 0.05,  // 5%
    ];

    /**
     * User pemilik membership ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hitung tier berdasarkan total poin saat ini.
     */
    public function calculateTier(): string
    {
        if ($this->total_points >= self::TIER_THRESHOLDS[self::TIER_GOLD]) {
            return self::TIER_GOLD;
        }
        if ($this->total_points >= self::TIER_THRESHOLDS[self::TIER_SILVER]) {
            return self::TIER_SILVER;
        }
        if ($this->total_points >= self::TIER_THRESHOLDS[self::TIER_BRONZE]) {
            return self::TIER_BRONZE;
        }
        return self::TIER_REGULAR;
    }

    /**
     * Dapatkan persentase diskon berdasarkan tier saat ini.
     */
    public function getDiscountPercentage(): float
    {
        return self::TIER_DISCOUNTS[$this->tier] ?? 0;
    }
}
