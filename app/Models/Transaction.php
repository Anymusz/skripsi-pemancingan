<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'user_id',
        'guest_session_id',
        'subtotal',
        'fish_subtotal',
        'tier_discount',
        'voucher_discount',
        'total_pay',
        'deposit_used',
        'refund_amount',
        'underpayment_amount',
        'tips',
        'payment_status',
        'payment_method',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'fish_subtotal' => 'decimal:2',
            'tier_discount' => 'decimal:2',
            'voucher_discount' => 'decimal:2',
            'total_pay' => 'decimal:2',
            'deposit_used' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'underpayment_amount' => 'decimal:2',
            'tips' => 'decimal:2',
            'points_earned' => 'integer',
        ];
    }

    /**
     * User (Member) yang melakukan transaksi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Sesi guest yang melakukan transaksi.
     */
    public function guestSession(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class);
    }

    /**
     * Detail item dalam transaksi ini.
     */
    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }
}
