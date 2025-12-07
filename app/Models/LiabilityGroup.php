<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiabilityGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'display_order',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function liabilities(): HasMany
    {
        return $this->hasMany(Liability::class);
    }
}
