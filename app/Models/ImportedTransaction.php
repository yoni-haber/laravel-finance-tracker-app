<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportedTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'date',
        'description',
        'amount',
        'external_id',
        'category_id',
        'hash',
        'original_hash',
        'is_duplicate',
        'is_committed',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount' => 'decimal:2',
            'is_duplicate' => 'boolean',
            'is_committed' => 'boolean',
        ];
    }

    public function bankStatementImport(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'import_id');
    }

    public function scopeNotDuplicate($query)
    {
        return $query->where('is_duplicate', false);
    }

    public function scopeNotCommitted($query)
    {
        return $query->where('is_committed', false);
    }

    public function scopeCommittable($query)
    {
        return $query->notDuplicate()->notCommitted();
    }
}
