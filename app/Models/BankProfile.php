<?php

namespace App\Models;

use App\Support\BankStatementConfig;
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
        return $this->statement_type === BankStatementConfig::STATEMENT_TYPE_BANK;
    }

    public function isCreditCardStatement(): bool
    {
        return $this->statement_type === BankStatementConfig::STATEMENT_TYPE_CREDIT_CARD;
    }
}
