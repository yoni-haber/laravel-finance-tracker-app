<?php

namespace App\Support;

use App\Models\BankStatementImport;
use App\Support\BankStatement\BankStatementImportProcessor;

/**
 * @deprecated Use BankStatementImportProcessor instead
 * This class is kept for backward compatibility
 */
class BankStatementParser
{
    public function __construct(
        private BankStatementImport $import
    ) {}

    /**
     * Parse the CSV file and create imported transaction records
     * 
     * @deprecated Use BankStatementImportProcessor::process() instead
     */
    public function parse(): bool
    {
        $processor = new BankStatementImportProcessor($this->import);
        return $processor->process();
    }
}