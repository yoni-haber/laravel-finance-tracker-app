<?php

namespace App\Models;

use App\Support\BankStatementConfig;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_filename',
        'status',
        'bank_profile_id',
        'statement_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankProfile(): BelongsTo
    {
        return $this->belongsTo(BankProfile::class);
    }

    public function importedTransactions(): HasMany
    {
        return $this->hasMany(ImportedTransaction::class, 'import_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isUploaded(): bool
    {
        return $this->status === BankStatementConfig::STATUS_UPLOADED;
    }

    public function isParsing(): bool
    {
        return $this->status === BankStatementConfig::STATUS_PARSING;
    }

    public function isParsed(): bool
    {
        return $this->status === BankStatementConfig::STATUS_PARSED;
    }

    public function isFailed(): bool
    {
        return $this->status === BankStatementConfig::STATUS_FAILED;
    }

    public function isCommitted(): bool
    {
        return $this->status === BankStatementConfig::STATUS_COMMITTED;
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
