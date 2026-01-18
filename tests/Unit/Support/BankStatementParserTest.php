<?php

namespace Tests\Unit\Support;

use App\Models\BankProfile;
use App\Models\BankStatementImport;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BankStatementParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankStatementParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_single_amount_column_csv(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'statement_type' => 'bank',
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        // Create test CSV content with header
        $csvContent = "Date,Description,Amount\n01/01/2026,Test Transaction,100.50\n02/01/2026,Another Transaction,-50.25";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertEquals(BankStatementImport::STATUS_PARSED, $import->fresh()->status);

        $transactions = $import->importedTransactions;
        $this->assertCount(2, $transactions);

        $firstTransaction = $transactions->first();
        $this->assertEquals('2026-01-01', $firstTransaction->date->toDateString());
        $this->assertEquals('TEST TRANSACTION', $firstTransaction->description);
        $this->assertEquals(100.50, $firstTransaction->amount);
        $this->assertFalse($firstTransaction->is_duplicate);
    }

    public function test_parses_debit_credit_columns_csv(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'statement_type' => 'bank',
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'debit' => 2, 'credit' => 3],
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description,Debit,Credit\n01/01/2026,Income Transaction,,100.50\n02/01/2026,Expense Transaction,50.25,";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->importedTransactions;
        $this->assertCount(2, $transactions);

        $incomeTransaction = $transactions->where('amount', '>', 0)->first();
        $expenseTransaction = $transactions->where('amount', '<', 0)->first();

        $this->assertEquals(100.50, $incomeTransaction->amount);
        $this->assertEquals(-50.25, $expenseTransaction->amount);
    }

    public function test_applies_credit_card_amount_transformation(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'statement_type' => 'credit_card',
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description,Amount\n01/01/2026,Purchase,100.50\n02/01/2026,Payment,-200.00";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->importedTransactions;
        $this->assertCount(2, $transactions);

        $purchase = $transactions->where('description', 'PURCHASE')->first();
        $payment = $transactions->where('description', 'PAYMENT')->first();

        // Credit card: positive CSV amount = purchase = expense (negative)
        $this->assertEquals(-100.50, $purchase->amount);
        // Credit card: negative CSV amount = payment = income (positive)
        $this->assertEquals(200.00, $payment->amount);
    }

    public function test_detects_duplicates_against_existing_transactions(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'statement_type' => 'bank',
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        // Create existing transaction
        Transaction::factory()->for($user)->create([
            'date' => '2026-01-01',
            'description' => 'EXISTING TRANSACTION',
            'amount' => 100.50,
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description,Amount\n01/01/2026,Existing Transaction,100.50\n02/01/2026,New Transaction,75.00";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->importedTransactions;
        $this->assertCount(2, $transactions);

        $duplicate = $transactions->where('description', 'EXISTING TRANSACTION')->first();
        $newTransaction = $transactions->where('description', 'NEW TRANSACTION')->first();

        $this->assertTrue($duplicate->is_duplicate);
        $this->assertFalse($newTransaction->is_duplicate);
    }

    public function test_handles_different_date_formats(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'statement_type' => 'bank',
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'Y-m-d',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description,Amount\n2026-01-04,Test Transaction,100.00";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transaction = $import->importedTransactions->first();
        $this->assertEquals('2026-01-04', $transaction->date->toDateString());
    }

    public function test_fails_gracefully_with_invalid_csv(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'd/m/Y',
                'has_header' => false,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        Storage::fake('local');
        // Create a CSV file that will cause a fundamental parsing error
        Storage::put("statements/{$import->id}.csv", 'This is not a valid CSV file at all - it will cause parsing issues');

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        // Even invalid CSV shouldn't crash - it should return true but create no transactions
        $this->assertTrue($result);
        $this->assertEquals(BankStatementImport::STATUS_PARSED, $import->fresh()->status);

        // Should not create any transactions
        $this->assertCount(0, $import->importedTransactions);
    }

    public function test_fails_when_file_missing(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        Storage::fake('local'); // File doesn't exist

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertFalse($result);
        $this->assertEquals(BankStatementImport::STATUS_FAILED, $import->fresh()->status);

        // Assert that the error was logged with the expected message
        Log::shouldHaveReceived('error')
            ->once()
            ->with('Bank statement parsing failed', [
                'import_id' => $import->id,
                'error' => 'CSV file not found',
            ]);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'config' => [
                'columns' => ['date' => 0, 'description' => 1, 'amount' => 2],
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description,Amount\n01/01/2026,Test Transaction,100.50";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);

        // Parse twice
        $result1 = $parser->parse();
        $result2 = $parser->parse();

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Should not create duplicate imported transactions
        $this->assertCount(1, $import->fresh()->importedTransactions);
    }

    public function test_fails_when_bank_profile_missing(): void
    {
        Log::spy();

        $user = User::factory()->create();
        $import = BankStatementImport::factory()->for($user)->create([
            'bank_profile_id' => null,
        ]);

        $csvContent = "Date,Description,Amount\n01/01/2026,Test Transaction,100.50";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertFalse($result);
        $this->assertEquals(BankStatementImport::STATUS_FAILED, $import->fresh()->status);
        $this->assertCount(0, $import->importedTransactions);

        Log::shouldHaveReceived('error')
            ->once()
            ->with('Bank statement parsing failed', [
                'import_id' => $import->id,
                'error' => 'Bank profile is required for parsing',
            ]);
    }

    public function test_handles_missing_amount_columns(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create([
            'config' => [
                'columns' => ['date' => 0, 'description' => 1], // No amount, debit, or credit columns
                'date_format' => 'd/m/Y',
                'has_header' => true,
            ],
        ]);

        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $csvContent = "Date,Description\n01/01/2026,Transaction Without Amount";
        Storage::fake('local');
        Storage::put("statements/{$import->id}.csv", $csvContent);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertEquals(BankStatementImport::STATUS_PARSED, $import->fresh()->status);

        // Should not create any transactions because amount is null
        $this->assertCount(0, $import->importedTransactions);
    }
}
