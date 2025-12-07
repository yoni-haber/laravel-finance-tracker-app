<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Collection;

class NetWorthSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'snapshot_date',
        'notes',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(NetWorthSnapshotItem::class);
    }

    public function assetItems(): HasManyThrough
    {
        return $this->hasManyThrough(NetWorthSnapshotItem::class, NetWorthSnapshotItem::class, 'net_worth_snapshot_id', 'id')
            ->where('item_type', NetWorthSnapshotItem::TYPE_ASSET);
    }

    public function liabilityItems(): HasManyThrough
    {
        return $this->hasManyThrough(NetWorthSnapshotItem::class, NetWorthSnapshotItem::class, 'net_worth_snapshot_id', 'id')
            ->where('item_type', NetWorthSnapshotItem::TYPE_LIABILITY);
    }

    public function totalAssets(): float
    {
        return (float) $this->items->where('item_type', NetWorthSnapshotItem::TYPE_ASSET)->sum('value');
    }

    public function totalLiabilities(): float
    {
        return (float) $this->items->where('item_type', NetWorthSnapshotItem::TYPE_LIABILITY)->sum('value');
    }

    public function netWorth(): float
    {
        return $this->totalAssets() - $this->totalLiabilities();
    }

    public static function latestForUser(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();
    }
}
