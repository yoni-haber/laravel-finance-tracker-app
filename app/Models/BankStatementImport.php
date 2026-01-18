<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    use HasFactory;

    const STATUS_UPLOADED = 'uploaded';

    const STATUS_PARSING = 'parsing';

    const STATUS_PARSED = 'parsed';

    const STATUS_FAILED = 'failed';

    const STATUS_COMMITTED = 'committed';

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
        return $this->status === self::STATUS_UPLOADED;
    }

    public function isParsing(): bool
    {
        return $this->status === self::STATUS_PARSING;
    }

    public function isParsed(): bool
    {
        return $this->status === self::STATUS_PARSED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCommitted(): bool
    {
        return $this->status === self::STATUS_COMMITTED;
    }

    public function isBankStatement(): bool
    {
        return $this->bankProfile ? $this->bankProfile->isBankStatement() : ($this->statement_type === 'bank');
    }

    public function isCreditCardStatement(): bool
    {
        return $this->bankProfile ? $this->bankProfile->isCreditCardStatement() : ($this->statement_type === 'credit_card');
    }
}
