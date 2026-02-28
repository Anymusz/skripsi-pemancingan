<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'guest_id',
        'deposit_amount',
        'session_start',
        'session_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'deposit_amount' => 'decimal:2',
            'session_start' => 'datetime',
            'session_end' => 'datetime',
        ];
    }

    /**
     * Transaksi yang terkait dengan sesi guest ini.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
