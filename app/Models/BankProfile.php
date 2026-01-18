<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'statement_type',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankStatementImports(): HasMany
    {
        return $this->hasMany(BankStatementImport::class);
    }

    public function isBankStatement(): bool
    {
        return $this->statement_type === 'bank';
    }

    public function isCreditCardStatement(): bool
    {
        return $this->statement_type === 'credit_card';
    }
}
