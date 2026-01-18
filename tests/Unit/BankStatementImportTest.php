<?php

namespace Tests\Unit;

use App\Models\BankProfile;
use App\Models\BankStatementImport;
use App\Models\ImportedTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BankStatementConfig;
use App\Support\BankStatementParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankStatementImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_bank_statement_import_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $this->assertInstanceOf(User::class, $import->user);
        $this->assertTrue($import->user->is($user));
    }

    public function test_bank_statement_import_belongs_to_bank_profile(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        $this->assertInstanceOf(BankProfile::class, $import->bankProfile);
        $this->assertTrue($import->bankProfile->is($profile));
    }

    public function test_bank_statement_import_has_many_imported_transactions(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();

        ImportedTransaction::factory()
            ->count(5)
            ->for($import)
            ->create();

        $this->assertCount(5, $import->importedTransactions);
        $this->assertTrue(
            $import->importedTransactions->every(
                fn ($transaction) => $transaction->bankStatementImport->is($import)
            )
        );
    }

    public function test_statement_type_delegation_to_bank_profile(): void
    {
        $user = User::factory()->create();
        $bankProfile = BankProfile::factory()->for($user)->create(['statement_type' => 'bank']);
        $creditCardProfile = BankProfile::factory()->for($user)->create(['statement_type' => 'credit_card']);

        $bankImport = BankStatementImport::factory()
            ->for($user)
            ->for($bankProfile, 'bankProfile')
            ->create(['statement_type' => 'credit_card']); // This should be overridden

        $creditCardImport = BankStatementImport::factory()
            ->for($user)
            ->for($creditCardProfile, 'bankProfile')
            ->create(['statement_type' => 'bank']); // This should be overridden

        // Should delegate to bank profile, not use stored statement_type
        $this->assertTrue($bankImport->isBankStatement());
        $this->assertFalse($bankImport->isCreditCardStatement());

        $this->assertTrue($creditCardImport->isCreditCardStatement());
        $this->assertFalse($creditCardImport->isBankStatement());
    }

    public function test_statement_type_fallback_when_no_bank_profile(): void
    {
        $user = User::factory()->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->create([
                'bank_profile_id' => null,
                'statement_type' => 'credit_card',
            ]);

        // Should fall back to stored statement_type when no bank profile
        $this->assertTrue($import->isCreditCardStatement());
        $this->assertFalse($import->isBankStatement());
    }

    public function test_is_uploaded(): void
    {
        $import = BankStatementImport::factory()->create(['status' => BankStatementConfig::STATUS_UPLOADED]);
        $this->assertTrue($import->isUploaded());
    }

    public function test_is_parsing(): void
    {
        $import = BankStatementImport::factory()->create(['status' => BankStatementConfig::STATUS_PARSING]);
        $this->assertTrue($import->isParsing());
    }

    public function test_is_parsed(): void
    {
        $import = BankStatementImport::factory()->create(['status' => BankStatementConfig::STATUS_PARSED]);
        $this->assertTrue($import->isParsed());
    }

    public function test_is_failed(): void
    {
        $import = BankStatementImport::factory()->create(['status' => BankStatementConfig::STATUS_FAILED]);
        $this->assertTrue($import->isFailed());
    }

    public function test_is_committed(): void
    {
        $import = BankStatementImport::factory()->create(['status' => BankStatementConfig::STATUS_COMMITTED]);
        $this->assertTrue($import->isCommitted());
    }

    public function test_scope_for_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        BankStatementImport::factory()->count(3)->for($user1)->create();
        BankStatementImport::factory()->count(2)->for($user2)->create();

        $importsForUser1 = BankStatementImport::forUser($user1->id)->get();
        $importsForUser2 = BankStatementImport::forUser($user2->id)->get();

        $this->assertCount(3, $importsForUser1);
        $this->assertCount(2, $importsForUser2);
    }

    // Tests for BankStatementParser functionality

    public function test_parser_returns_true_if_already_parsed(): void
    {
        $import = BankStatementImport::factory()->parsed()->create();
        $parser = new BankStatementParser($import);

        $result = $parser->parse();

        $this->assertTrue($result);
    }

    public function test_parser_returns_true_if_already_committed(): void
    {
        $import = BankStatementImport::factory()->committed()->create();
        $parser = new BankStatementParser($import);

        $result = $parser->parse();

        $this->assertTrue($result);
    }

    public function test_parser_fails_when_csv_file_not_found(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertFalse($result);
        $this->assertEquals(BankStatementConfig::STATUS_FAILED, $import->fresh()->status);
    }

    public function test_parser_fails_when_bank_profile_missing(): void
    {
        $user = User::factory()->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->create(['bank_profile_id' => null]);

        $this->createCsvFile($import->id, "Date,Description,Amount\n01/01/2024,TEST TRANSACTION,100.00\n");

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertFalse($result);
        $this->assertEquals(BankStatementConfig::STATUS_FAILED, $import->fresh()->status);
    }

    public function test_parser_successfully_parses_bank_statement(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "01/01/2024,TEST TRANSACTION,100.00\n02/01/2024,ANOTHER TRANSACTION,-50.25\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);

        // Capture any exceptions for debugging
        try {
            $result = $parser->parse();
        } catch (\Exception $e) {
            $this->fail('Parser threw exception: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        $freshImport = $import->fresh();
        $this->assertTrue($result, "Parser failed. Status: {$freshImport->status}");
        $this->assertEquals(BankStatementConfig::STATUS_PARSED, $freshImport->status);
        $this->assertCount(2, $freshImport->importedTransactions);
    }

    public function test_parser_successfully_parses_credit_card_statement(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->creditCard()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,PURCHASE,100.00\n02/01/2024,PAYMENT,-50.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->fresh()->importedTransactions;
        $this->assertCount(2, $transactions);

        // Credit card transactions should have flipped amounts
        $purchase = $transactions->where('description', 'PURCHASE')->first();
        $payment = $transactions->where('description', 'PAYMENT')->first();

        $this->assertEquals(-100.00, $purchase->amount);  // Positive becomes negative
        $this->assertEquals(50.00, $payment->amount);     // Negative becomes positive
    }

    public function test_parser_handles_separate_debit_credit_columns(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->separateColumns()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Debit,Credit\n01/01/2024,PURCHASE,100.00,\n02/01/2024,DEPOSIT,,150.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->fresh()->importedTransactions;
        $this->assertCount(2, $transactions);

        $purchase = $transactions->where('description', 'PURCHASE')->first();
        $deposit = $transactions->where('description', 'DEPOSIT')->first();

        $this->assertEquals(-100.00, $purchase->amount);
        $this->assertEquals(150.00, $deposit->amount);
    }

    public function test_parser_handles_different_date_formats(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->isoDateFormat()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n2024-01-01,TEST TRANSACTION,100.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transaction = $import->fresh()->importedTransactions->first();
        $this->assertEquals('2024-01-01', $transaction->date->toDateString());
    }

    public function test_parser_detects_duplicates_against_existing_transactions(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();

        // Create existing transaction
        Transaction::factory()->for($user)->create([
            'date' => '2024-01-01',
            'description' => 'TEST TRANSACTION',
            'amount' => 100.00,
        ]);

        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,Test Transaction,100.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $parser->parse();

        $importedTransaction = $import->fresh()->importedTransactions->first();
        $this->assertTrue($importedTransaction->is_duplicate);
    }

    public function test_parser_detects_duplicates_against_existing_imported_transactions(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();

        // Create existing imported transaction with the same hash
        $existingImport = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create();
        ImportedTransaction::factory()->for($existingImport)->create([
            'date' => '2024-01-01',
            'description' => 'TEST TRANSACTION',
            'amount' => 100.00,
            'hash' => sha1($user->id.'2024-01-01'.'100.00'.'TEST TRANSACTION'),
        ]);

        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,Test Transaction,100.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $parser->parse();

        $importedTransaction = $import->fresh()->importedTransactions->first();
        $this->assertTrue($importedTransaction->is_duplicate);
    }

    public function test_parser_handles_header_skip_configuration(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();

        // Update config to include header
        $config = $profile->config;
        $config['has_header'] = true;
        $profile->update(['config' => $config]);

        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,TEST TRANSACTION,100.00\n02/01/2024,ANOTHER TRANSACTION,-50.25\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertCount(2, $import->fresh()->importedTransactions);
    }

    public function test_parser_skips_empty_rows(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,TEST TRANSACTION,100.00\n,,\n02/01/2024,ANOTHER TRANSACTION,-50.25\n\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertCount(2, $import->fresh()->importedTransactions);
    }

    public function test_parser_handles_invalid_amounts(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,VALID TRANSACTION,100.00\n02/01/2024,INVALID AMOUNT,invalid\n03/01/2024,ANOTHER VALID,-50.25\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertCount(2, $import->fresh()->importedTransactions);
    }

    public function test_parser_handles_currency_symbols_in_amounts(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,GBP TRANSACTION,£100.00\n02/01/2024,USD TRANSACTION,$50.25\n03/01/2024,EUR TRANSACTION,€75.50\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->fresh()->importedTransactions;
        $this->assertCount(3, $transactions);

        $amounts = $transactions->pluck('amount')->sort()->values()->toArray();
        $expectedAmounts = [50.25, 75.50, 100.00];
        sort($expectedAmounts);

        $this->assertEquals($expectedAmounts, $amounts);
    }

    public function test_parser_normalizes_descriptions(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create();
        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,  test   transaction  ,100.00\n02/01/2024,another    description,50.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);

        $transactions = $import->fresh()->importedTransactions;
        $descriptions = $transactions->pluck('description')->toArray();

        $this->assertContains('TEST TRANSACTION', $descriptions);
        $this->assertContains('ANOTHER DESCRIPTION', $descriptions);
    }

    public function test_parser_fallback_date_formats(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->for($user)->create([
            'config' => [
                'columns' => [
                    'date' => 0,
                    'description' => 1,
                    'amount' => 2,
                ],
                'date_format' => 'Y-m-d', // Configure for ISO, but test other formats
            ],
        ]);

        $import = BankStatementImport::factory()
            ->for($user)
            ->for($profile, 'bankProfile')
            ->create();

        $csvData = "Date,Description,Amount\n01/01/2024,TEST DD/MM/YYYY,100.00\n1/2/2024,TEST M/D/Y,50.00\n2024-01-03,TEST ISO,75.00\n";
        $this->createCsvFile($import->id, $csvData);

        $parser = new BankStatementParser($import);
        $result = $parser->parse();

        $this->assertTrue($result);
        $this->assertCount(3, $import->fresh()->importedTransactions);
    }

    protected function createCsvFile(int $importId, string $content): void
    {
        Storage::put("statements/{$importId}.csv", $content);
    }
}
