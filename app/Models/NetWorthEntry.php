<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'assets' => 'decimal:2',
            'liabilities' => 'decimal:2',
            'net_worth' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(NetWorthLineItem::class);
    }
}
