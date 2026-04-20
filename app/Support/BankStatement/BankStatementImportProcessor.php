<?php

namespace App\Support\BankStatement;

use App\Models\BankStatementImport;
use App\Support\BankStatementConfig;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BankStatementImportProcessor
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

        // Atomically claim the import by transitioning from uploaded → parsing.
        // This prevents two queue workers from processing the same import simultaneously.
        $claimed = BankStatementImport::where('id', $this->import->id)
            ->whereIn('status', [BankStatementConfig::STATUS_UPLOADED, BankStatementConfig::STATUS_PARSING])
            ->update(['status' => BankStatementConfig::STATUS_PARSING, 'updated_at' => now()]);

        if (! $claimed) {
            // Another worker already claimed it or it's in a non-processable state.
            $this->import->refresh();

            return $this->import->isParsed() || $this->import->isCommitted();
        }

        $this->import->refresh();

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

        // Step 4: Save imported transactions
        $this->saveImportedTransactions($transactions);

        $this->import->update(['status' => BankStatementConfig::STATUS_PARSED]);

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
     * Save imported transactions to database
     */
    private function saveImportedTransactions($transactions): void
    {
        DB::transaction(function () use ($transactions) {
            $transactions->chunk(BankStatementConfig::TRANSACTION_CHUNK_SIZE)
                ->each(function ($chunk) {
                    $data = $chunk->map(function ($transaction) {
                        return [
                            'import_id' => $this->import->id,
                            'date' => $transaction['date'],
                            'description' => $transaction['description'],
                            'amount' => $transaction['amount'],
                            'hash' => $transaction['hash'],
                            'is_duplicate' => $transaction['is_duplicate'],
                            'external_id' => $transaction['external_id'] ?? null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    })->toArray();

                    $this->import->importedTransactions()->insert($data);
                });
        });
    }
}
