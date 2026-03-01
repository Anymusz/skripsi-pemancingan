<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'event_date',
        'end_date',
        'category',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'dipublikasikan';
    }
}
