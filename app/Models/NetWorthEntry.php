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
        'recorded_on',
        'assets',
        'liabilities',
        'net_worth',
    ];

    protected $casts = [
        'recorded_on' => 'date',
        'assets' => 'decimal:2',
        'liabilities' => 'decimal:2',
        'net_worth' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
