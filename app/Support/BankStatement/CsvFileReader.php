<?php

namespace App\Support\BankStatement;

use App\Support\BankStatementConfig;
use Exception;
use Illuminate\Support\Collection;
use SplFileObject;

class CsvFileReader
{
    public function __construct(
        private string $filePath
    ) {}

    /**
     * Read CSV file and return filtered rows
     */
    public function readRows(): Collection
    {
        if (!file_exists($this->filePath)) {
            throw new Exception('CSV file not found: ' . $this->filePath);
        }

        $file = new SplFileObject($this->filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV);

        $rows = collect();
        $isFirstRow = true;

        while (!$file->eof()) {
            $row = $file->fgetcsv();

            // Skip header row if it exists
            if ($isFirstRow && BankStatementConfig::CSV_HAS_HEADER_DEFAULT) {
                $isFirstRow = false;
                continue;
            }

            // Skip empty rows if configured
            if (BankStatementConfig::CSV_SKIP_EMPTY_ROWS && (!$row || count(array_filter($row)) === 0)) {
                continue;
            }

            if ($row && count(array_filter($row)) > 0) {
                $rows->push($row);
            }
        }

        return $rows;
    }
}