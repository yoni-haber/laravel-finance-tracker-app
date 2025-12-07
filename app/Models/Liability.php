<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'liability_group_id',
        'name',
        'notes',
        'interest_rate',
    ];

    protected $casts = [
        'interest_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(LiabilityGroup::class, 'liability_group_id');
    }

    public function snapshotItems(): HasMany
    {
        return $this->hasMany(NetWorthSnapshotItem::class, 'item_id')
            ->where('item_type', NetWorthSnapshotItem::TYPE_LIABILITY);
    }
}
