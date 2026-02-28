<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leaderboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_fish_weight',
    ];

    protected function casts(): array
    {
        return [
            'total_fish_weight' => 'decimal:2',
        ];
    }

    /**
     * User pemilik entri leaderboard ini.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
