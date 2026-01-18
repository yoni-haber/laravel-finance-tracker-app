<?php

namespace App\Support;

class BankStatementConfig
{
    // File upload limits
    const MAX_FILE_SIZE_MB = 2;
    const MAX_FILE_SIZE_KB = self::MAX_FILE_SIZE_MB * 1024;
    
    // Job configuration
    const JOB_TIMEOUT_SECONDS = 60;
    const JOB_MAX_TRIES = 3;
    const JOB_MAX_EXCEPTIONS = 2;
    
    // Date parsing formats (in order of preference)
    const SUPPORTED_DATE_FORMATS = [
        'd/m/Y',
        'Y-m-d', 
        'm/d/Y',
        'd-m-Y'
    ];
    
    // CSV parsing settings
    const CSV_SKIP_EMPTY_ROWS = true;
    const CSV_HAS_HEADER_DEFAULT = true;
    
    // Statement types
    const STATEMENT_TYPE_BANK = 'bank';
    const STATEMENT_TYPE_CREDIT_CARD = 'credit_card';
    
    const VALID_STATEMENT_TYPES = [
        self::STATEMENT_TYPE_BANK,
        self::STATEMENT_TYPE_CREDIT_CARD,
    ];
    
    // Import status constants
    const STATUS_UPLOADED = 'uploaded';
    const STATUS_PARSING = 'parsing';
    const STATUS_PARSED = 'parsed';
    const STATUS_FAILED = 'failed';
    const STATUS_COMMITTED = 'committed';
    
    // Transaction processing
    const TRANSACTION_CHUNK_SIZE = 1000;
    const HASH_ALGORITHM = 'sha1';
    
    // Amount precision
    const AMOUNT_DECIMAL_PLACES = 2;
    const AMOUNT_SCALE = 10;
}