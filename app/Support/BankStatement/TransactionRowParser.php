<?php

namespace App\Support\BankStatement;

use App\Models\BankProfile;
use App\Support\BankStatementConfig;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;

readonly class TransactionRowParser
{
    public function __construct(
        private BankProfile $profile
    ) {}

    /**
     * Parse a single CSV row into transaction data
     */
    public function parseRow(array $row): ?array
    {
        $columns = $this->profile->config['columns'] ?? [];

        $date = $this->extractDate($row, $columns['date'] ?? null);
        $description = $this->extractDescription($row, $columns['description'] ?? null);
        $amount = $this->extractAmount($row, $columns);

        if (! $date || ! $description || $amount === null) {
            return null;
        }

        // Apply statement type logic
        if ($this->profile->isCreditCardStatement()) {
            $amount = -$amount; // Flip sign for credit cards
        }

        return [
            'date' => $date,
            'description' => $description,
            'amount' => $amount,
            'external_id' => null,
        ];
    }

    /**
     * Extract date from row
     */
    private function extractDate(array $row, ?int $dateIndex): ?Carbon
    {
        if ($dateIndex === null) {
            return null;
        }

        $dateString = trim($row[$dateIndex] ?? '');
        if (empty($dateString)) {
            return null;
        }

        return $this->parseDate($dateString);
    }

    /**
     * Extract description from row
     */
    private function extractDescription(array $row, ?int $descriptionIndex): ?string
    {
        if ($descriptionIndex === null) {
            return null;
        }

        $description = $this->normaliseDescription(trim($row[$descriptionIndex] ?? ''));

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
     * Parse date from string using supported formats
     */
    private function parseDate(string $dateString): ?Carbon
    {
        $formats = BankStatementConfig::SUPPORTED_DATE_FORMATS;

        // Try profile-specific format first
        if (isset($this->profile->config['date_format'])) {
            array_unshift($formats, $this->profile->config['date_format']);
        }

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateString);
            } catch (Exception) {
                continue;
            }
        }

        return null;
    }

    /**
     * Normalise description text
     */
    private function normaliseDescription(string $description): string
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
        $amountString = preg_replace('/[£$€¥,\s]/', '', $amountString);

        // Handle negative amounts in parentheses
        if (preg_match('/^\((.+)\)$/', $amountString, $matches)) {
            $amountString = '-'.$matches[1];
        }

        return is_numeric($amountString) ? (float) $amountString : null;
    }
}
