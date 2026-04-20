<?php

namespace App\Jobs;

use App\Models\BankStatementImport;
use App\Support\BankStatement\BankStatementImportProcessor;
use App\Support\BankStatementConfig;
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
     *
     * The guard skips only terminal success states (parsed/committed). Failed imports
     * remain retryable — the processor's atomic claim (uploaded → parsing) ensures
     * only one worker runs at a time. Non-retriable failures (missing file, missing
     * profile) are handled inside the processor, which sets status to failed and
     * returns false without throwing.
     */
    public function handle(): void
    {
        $import = BankStatementImport::find($this->importId);

        if (! $import) {
            throw new ModelNotFoundException('Bank statement import not found');
        }

        if ($import->isParsed() || $import->isCommitted()) {
            logger()->info('Import already processed, skipping', [
                'import_id' => $this->importId,
                'status' => $import->status,
            ]);

            return;
        }

        $processor = new BankStatementImportProcessor($import);
        $success = $processor->process();

        if ($success) {
            logger()->info('Bank statement parsed successfully', ['import_id' => $this->importId]);
        } else {
            logger()->error('Bank statement parsing failed (non-retriable)', ['import_id' => $this->importId]);
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $import = BankStatementImport::find($this->importId);

        if ($import) {
            $import->update(['status' => BankStatementConfig::STATUS_FAILED]);
        }

        logger()->error('Bank statement parsing job failed permanently', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
        ]);
    }
}
