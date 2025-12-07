<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsDecimal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'month',
        'year',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => AsDecimal::class.':2',
            'month' => 'integer',
            'year' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Category::class, 'id', 'category_id', 'category_id');
    }
}
