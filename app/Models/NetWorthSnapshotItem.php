<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NetWorthSnapshotItem extends Model
{
    use HasFactory;

    public const TYPE_ASSET = 'asset';
    public const TYPE_LIABILITY = 'liability';

    protected $fillable = [
        'net_worth_snapshot_id',
        'item_type',
        'item_id',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(NetWorthSnapshot::class, 'net_worth_snapshot_id');
    }

    public function item(): MorphTo
    {
        return $this->morphTo(null, 'item_type', 'item_id');
    }
}
