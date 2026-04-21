<?php

namespace App\Support\BankStatement;

use App\Models\BankStatementImport;
use App\Support\BankStatementConfig;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

readonly class BankStatementImportProcessor
{
    public function __construct(
        private BankStatementImport $import
    ) {}

    /**
     * Process the bank statement import
     */
    public function process(): bool
    {
        if ($this->import->isParsed() || $this->import->isCommitted()) {
            return true;
        }

        // Atomically claim the import by transitioning to parsing.
        // Only STATUS_UPLOADED and STATUS_FAILED are claimable — STATUS_PARSING means
        // another worker already holds the claim, and we must not proceed concurrently.
        // STATUS_FAILED is included so a re-dispatched job can recover after total failure.
        $claimed = BankStatementImport::where('id', $this->import->id)
            ->whereIn('status', [BankStatementConfig::STATUS_UPLOADED, BankStatementConfig::STATUS_FAILED])
            ->update(['status' => BankStatementConfig::STATUS_PARSING]);

        $this->import->refresh();

        if (! $claimed) {
            // Another worker already claimed it, or it's in a non-processable state.
            return $this->import->isParsed() || $this->import->isCommitted();
        }

        $filePath = Storage::disk('local')->path("statements/{$this->import->id}.csv");

        if (! $this->import->bankProfile) {
            logger()->error('Bank statement parsing failed', [
                'import_id' => $this->import->id,
                'error' => 'Bank profile is required for parsing',
            ]);
            $this->import->update(['status' => BankStatementConfig::STATUS_FAILED]);

            return false;
        }

        // Step 1: Read CSV file
        $reader = new CsvFileReader($filePath, $this->import->bankProfile);
        try {
            $rows = $reader->readRows();
        } catch (Exception $e) {
            logger()->error('Bank statement parsing failed', [
                'import_id' => $this->import->id,
                'error' => 'CSV file not found - '.$e->getMessage(),
            ]);
            $this->import->update(['status' => BankStatementConfig::STATUS_FAILED]);

            return false;
        }

        // Step 2: Parse rows into transactions
        $parser = new TransactionRowParser($this->import->bankProfile);
        $transactions = $this->parseRows($rows, $parser);

        // Step 3: Add hashes and detect duplicates
        $detector = new DuplicateDetector($this->import->user_id);
        $detector->detectDuplicates($transactions);

        // Step 4: Save imported transactions and mark parsed — both in one transaction
        // so a crash between the two operations cannot leave the import in an inconsistent state.
        $this->saveImportedTransactions($transactions);

        return true;
    }

    /**
     * Parse CSV rows into transaction data
     */
    private function parseRows($rows, TransactionRowParser $parser)
    {
        return $rows->map(function ($row) use ($parser) {
            try {
                return $parser->parseRow($row);
            } catch (Exception $e) {
                logger()->warning('Failed to parse CSV row', [
                    'import_id' => $this->import->id,
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        })->filter();
    }

    /**
     * Save imported transactions to database and mark the import as parsed,
     * all within a single transaction so the two operations are atomic.
     * Any existing rows are deleted first so re-processing after STATUS_FAILED
     * cannot produce duplicate staged transactions.
     */
    private function saveImportedTransactions($transactions): void
    {
        DB::transaction(function () use ($transactions) {
            // Clear any rows from a previous failed attempt before re-inserting.
            $this->import->importedTransactions()->delete();

            $transactions->chunk(BankStatementConfig::TRANSACTION_CHUNK_SIZE)
                ->each(function ($chunk) {
                    $data = $chunk->map(function ($transaction) {
                        return [
                            'import_id' => $this->import->id,
                            'date' => $transaction['date'],
                            'description' => $transaction['description'],
                            'amount' => $transaction['amount'],
                            'hash' => $transaction['hash'],
                            'original_hash' => $transaction['hash'],
                            'is_duplicate' => $transaction['is_duplicate'],
                            'external_id' => $transaction['external_id'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    $this->import->importedTransactions()->insert($data);
                });

            $this->import->update(['status' => BankStatementConfig::STATUS_PARSED]);
        });
    }
}
