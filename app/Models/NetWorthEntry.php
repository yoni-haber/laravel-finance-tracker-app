<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetWorthEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'assets',
        'liabilities',
        'net_worth',
        'date',
    ];

    protected function casts(): array
    {
        return [
            'assets' => 'decimal:2',
            'liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
