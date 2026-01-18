<?php

namespace App\Support;

use App\Models\BankProfile;
use App\Models\BankStatementImport;
use App\Models\ImportedTransaction;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SplFileObject;

class BankStatementParser
{
    public function __construct(
        private BankStatementImport $import
    ) {}

    /**
     * Parse the CSV file and create imported transaction records
     */
    public function parse(): bool
    {
        if ($this->import->isParsed() || $this->import->isCommitted()) {
            return true;
        }

        try {
            $this->import->update(['status' => BankStatementImport::STATUS_PARSING]);

            $filePath = Storage::path("statements/{$this->import->id}.csv");
            if (! file_exists($filePath)) {
                throw new Exception('CSV file not found');
            }

            $profile = $this->import->bankProfile;
            if (! $profile) {
                throw new Exception('Bank profile is required for parsing');
            }

            $rows = $this->readCsvFile($filePath);
            $transactions = $this->parseRows($rows, $profile);
            $this->detectDuplicates($transactions);
            $this->saveImportedTransactions($transactions);

            $this->import->update(['status' => BankStatementImport::STATUS_PARSED]);

            return true;
        } catch (Exception $e) {
            $this->import->update(['status' => BankStatementImport::STATUS_FAILED]);

            logger()->error('Bank statement parsing failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Read CSV file and return rows
     */
    private function readCsvFile(string $filePath): Collection
    {
        $file = new SplFileObject($filePath);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);

        $rows = collect();
        $hasHeader = $this->import->bankProfile->config['has_header'] ?? false;
        $headerSkipped = ! $hasHeader;

        foreach ($file as $row) {
            if (! $headerSkipped) {
                $headerSkipped = true;

                continue;
            }

            if ($row && count(array_filter($row)) > 0) {
                $rows->push($row);
            }
        }

        return $rows;
    }

    /**
     * Parse CSV rows into transaction data
     */
    private function parseRows(Collection $rows, BankProfile $profile): Collection
    {
        return $rows->map(function ($row) use ($profile) {
            try {
                return $this->parseRow($row, $profile->config);
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
     * Parse a single CSV row
     */
    private function parseRow(array $row, array $config): ?array
    {
        $columns = $config['columns'] ?? [];

        $date = $this->extractDate($row, $columns['date'] ?? null, $config['date_format'] ?? 'd/m/Y');
        $description = $this->extractDescription($row, $columns['description'] ?? null);
        $amount = $this->extractAmount($row, $columns);

        if (! $date || ! $description || $amount === null) {
            return null;
        }

        // Apply statement type logic
        if ($this->import->bankProfile->isCreditCardStatement()) {
            $amount = -$amount; // Flip sign for credit cards
        }

        $hash = $this->generateTransactionHash(
            $this->import->user_id,
            $date,
            $amount,
            $description
        );

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'hash' => $hash,
            'external_id' => null,
        ];
    }

    /**
     * Extract date from row
     */
    private function extractDate(array $row, ?int $dateIndex, string $format): ?Carbon
    {
        if ($dateIndex === null) {
            return null;
        }

        $dateString = trim($row[$dateIndex] ?? '');
        if (empty($dateString)) {
            return null;
        }

        return $this->parseDate($dateString, $format);
    }

    /**
     * Extract description from row
     */
    private function extractDescription(array $row, ?int $descriptionIndex): ?string
    {
        if ($descriptionIndex === null) {
            return null;
        }

        $description = $this->normalizeDescription(trim($row[$descriptionIndex] ?? ''));

        return empty($description) ? null : $description;
    }

    /**
     * Extract amount from row
     */
    private function extractAmount(array $row, array $columns): ?float
    {
        $amountIndex = $columns['amount'] ?? null;
        $debitIndex = $columns['debit'] ?? null;
        $creditIndex = $columns['credit'] ?? null;

        return $this->parseAmount($row, $amountIndex, $debitIndex, $creditIndex);
    }

    /**
     * Parse date from string using specified format
     */
    private function parseDate(string $dateString, string $format): ?Carbon
    {
        $formats = [$format, 'd/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];

        foreach ($formats as $attemptFormat) {
            try {
                return Carbon::createFromFormat($attemptFormat, $dateString);
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Normalize description text
     */
    private function normalizeDescription(string $description): string
    {
        return Str::squish(Str::upper($description));
    }

    /**
     * Parse amount from row data
     */
    private function parseAmount(array $row, ?int $amountIndex, ?int $debitIndex, ?int $creditIndex): ?float
    {
        // Single amount column
        if ($amountIndex !== null) {
            return $this->parseAmountString(trim($row[$amountIndex] ?? ''));
        }

        // Separate debit/credit columns
        if ($debitIndex !== null || $creditIndex !== null) {
            $debit = $debitIndex !== null ?
                ($this->parseAmountString(trim($row[$debitIndex] ?? '')) ?? 0) : 0;

            $credit = $creditIndex !== null ?
                ($this->parseAmountString(trim($row[$creditIndex] ?? '')) ?? 0) : 0;

            return $credit - $debit;
        }

        return null;
    }

    /**
     * Parse amount string to float
     */
    private function parseAmountString(string $amountString): ?float
    {
        if (empty($amountString)) {
            return null;
        }

        // Remove common currency symbols and whitespace
        $cleaned = preg_replace('/[£$€,\s]/', '', $amountString);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Generate hash for deduplication
     */
    private function generateTransactionHash(int $userId, Carbon $date, float $amount, string $description): string
    {
        $data = $userId.$date->toDateString().number_format($amount, 2, '.', '').$description;

        return sha1($data);
    }

    /**
     * Detect duplicates against existing transactions and imported transactions
     */
    private function detectDuplicates(Collection &$transactions): void
    {
        $hashes = $transactions->pluck('hash')->unique();

        $existingTransactionHashes = $this->getExistingTransactionHashes()->toArray();
        $existingImportedHashes = $this->getExistingImportedHashes($hashes)->toArray();

        $allExistingHashes = array_unique(array_merge($existingTransactionHashes, $existingImportedHashes));
        $existingHashesLookup = array_flip($allExistingHashes);

        $transactions->transform(function ($transaction) use ($existingHashesLookup) {
            $transaction['is_duplicate'] = isset($existingHashesLookup[$transaction['hash']]);

            return $transaction;
        });
    }

    /**
     * Get hashes from existing transactions
     */
    private function getExistingTransactionHashes(): Collection
    {
        return Transaction::where('user_id', $this->import->user_id)
            ->get()
            ->map(fn ($transaction) => $this->generateTransactionHash(
                $this->import->user_id,
                $transaction->date,
                (float) $transaction->amount,
                $this->normalizeDescription($transaction->description)
            ))
            ->values();
    }

    /**
     * Get hashes from existing imported transactions
     */
    private function getExistingImportedHashes(Collection $hashes): Collection
    {
        return ImportedTransaction::whereHas('bankStatementImport', function ($query) {
            $query->where('user_id', $this->import->user_id);
        })
            ->whereIn('hash', $hashes->toArray())
            ->pluck('hash')
            ->values();
    }

    /**
     * Save imported transactions to database
     */
    private function saveImportedTransactions(Collection $transactions): void
    {
        $data = $transactions->map(fn ($transaction) => [
            'import_id' => $this->import->id,
            'date' => $transaction['date'],
            'description' => $transaction['description'],
            'amount' => $transaction['amount'],
            'external_id' => $transaction['external_id'],
            'hash' => $transaction['hash'],
            'is_duplicate' => $transaction['is_duplicate'],
            'is_committed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ])->toArray();

        DB::table('imported_transactions')->insert($data);
    }
}
