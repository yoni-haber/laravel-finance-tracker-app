<?php

namespace App\Jobs;

use App\Models\BankStatementImport;
use App\Support\BankStatement\BankStatementImportProcessor;
use App\Support\BankStatementConfig;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseBankStatementJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = BankStatementConfig::JOB_MAX_TRIES;

    public int $timeout = BankStatementConfig::JOB_TIMEOUT_SECONDS;

    public int $importId;

    public function __construct(int $importId)
    {
        $this->importId = $importId;
    }

    /**
     * Execute the job.
     */
    public function handle(): bool
    {
        $import = BankStatementImport::find($this->importId);

        if (!$import) {
            throw new ModelNotFoundException('Bank statement import not found');
        }

        // Prevent re-processing
        if (!$import->isUploaded() && !$import->isParsing()) {
            logger()->info('Import already processed or in invalid state', [
                'import_id' => $this->importId,
                'status' => $import->status,
            ]);

            return true;
        }

        try {
            $processor = new BankStatementImportProcessor($import);
            $success = $processor->process();

            if ($success) {
                logger()->info('Bank statement parsed successfully', ['import_id' => $this->importId]);
                return true;
            } else {
                logger()->error('Bank statement parsing failed', ['import_id' => $this->importId]);
                return false;
            }
        } catch (Exception $e) {
            $import->update(['status' => BankStatementConfig::STATUS_FAILED]);

            logger()->error('Bank statement parsing job failed', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $importId = $this->importId ?? null;

        if ($importId) {
            $import = BankStatementImport::find($importId);

            if ($import) {
                $import->update(['status' => BankStatementConfig::STATUS_FAILED]);
            }

            logger()->error('Bank statement parsing job failed permanently', [
                'import_id' => $importId,
                'error' => $exception->getMessage(),
            ]);
        } else {
            logger()->error('Bank statement parsing job failed permanently (no import ID available)', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
