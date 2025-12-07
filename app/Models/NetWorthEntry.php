<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builders\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetWorthEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'assets',
        'liabilities',
        'net_worth',
    ];

    protected $casts = [
        'date' => 'date',
        'assets' => 'decimal:2',
        'liabilities' => 'decimal:2',
        'net_worth' => 'decimal:2',
    ];

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
