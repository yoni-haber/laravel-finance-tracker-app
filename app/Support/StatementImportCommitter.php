<?php

namespace App\Support;

use App\Models\BankStatementImport;
use App\Models\Transaction;
use App\Support\BankStatementConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StatementImportCommitter
{
    public function __construct(
        private BankStatementImport $import
    ) {}

    /**
     * Commit imported transactions to create real Transaction records
     */
    public function commit(): bool
    {
        $this->import->refresh(); // Refresh to get latest status

        // Skip if already committed (idempotency)
        if ($this->import->isCommitted()) {
            return true;
        }

        if (! $this->import->isParsed()) {
            return false;
        }

        try {
            DB::transaction(function () {
                $importedTransactions = $this->import->importedTransactions()
                    ->committable()
                    ->lockForUpdate()  // Add row locking to prevent race conditions
                    ->get();

                foreach ($importedTransactions as $importedTransaction) {
                    // For credit cards, parser has already flipped signs for business logic,
                    // but Transaction table should store positive amounts for expenses
                    $amount = $importedTransaction->amount;

                    if ($this->import->bankProfile && $this->import->bankProfile->isCreditCardStatement()) {
                        // Credit card: flip back to positive amounts, determine type from original CSV logic
                        $isExpense = $amount < 0; // Negative imported amount = expense
                        $amount = abs($amount); // Store positive amount
                        $type = $isExpense ? Transaction::TYPE_EXPENSE : Transaction::TYPE_INCOME;
                    } else {
                        // Bank statement: amounts are as-is
                        $type = $amount >= 0 ? Transaction::TYPE_INCOME : Transaction::TYPE_EXPENSE;
                        $amount = abs($amount); // Ensure positive amounts for consistency
                    }

                    // Extract category from external_id if set
                    $categoryId = null;
                    if ($importedTransaction->external_id && str_starts_with($importedTransaction->external_id, 'category:')) {
                        $categoryId = (int) str_replace('category:', '', $importedTransaction->external_id);
                    }

                    // Create real transaction
                    Transaction::create([
                        'user_id' => $this->import->user_id,
                        'date' => $importedTransaction->date,
                        'description' => $importedTransaction->description,
                        'amount' => $amount, // Keep signed amount
                        'type' => $type,
                        'category_id' => $categoryId,
                        'is_recurring' => false,
                        'frequency' => null,
                        'recurring_until' => null,
                    ]);

                    // Mark imported transaction as committed
                    $importedTransaction->update(['is_committed' => true]);
                }

                // Update import status
                $this->import->update(['status' => BankStatementConfig::STATUS_COMMITTED]);

                // Clean up CSV file for GDPR compliance
                $this->cleanupCsvFile();
            });

            return true;
        } catch (\Exception $e) {
            logger()->error('Failed to commit statement import', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up CSV file after successful commit
     */
    private function cleanupCsvFile(): void
    {
        $filePath = "statements/{$this->import->id}.csv";
        
        if (Storage::exists($filePath)) {
            try {
                Storage::delete($filePath);
                logger()->info('CSV file deleted for GDPR compliance', [
                    'import_id' => $this->import->id,
                    'user_id' => $this->import->user_id,
                ]);
            } catch (\Exception $e) {
                // Log but don't fail the transaction - file cleanup is not critical
                logger()->warning('Failed to delete CSV file after import', [
                    'import_id' => $this->import->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get summary statistics for the import
     */
    public function getSummary(): array
    {
        $transactions = $this->import->importedTransactions;

        return [
            'total' => $transactions->count(),
            'duplicates' => $transactions->where('is_duplicate', true)->count(),
            'new_transactions' => $transactions->where('is_duplicate', false)->count(),
            'total_amount' => $transactions->where('is_duplicate', false)->sum('amount'),
            'committed' => $transactions->where('is_committed', true)->count(),
        ];
    }
}
