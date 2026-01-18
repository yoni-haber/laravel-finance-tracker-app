<?php

namespace Tests\Unit\Support;

use App\Models\BankProfile;
use App\Models\BankStatementImport;
use App\Models\Category;
use App\Models\ImportedTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BankStatementConfig;
use App\Support\StatementImportCommitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatementImportCommitterTest extends TestCase
{
    use RefreshDatabase;

    public function test_commits_non_duplicate_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        // Create imported transactions
        ImportedTransaction::factory()->for($import)->create([
            'date' => '2026-01-01',
            'description' => 'Test Transaction',
            'amount' => 100.50,
            'is_duplicate' => false,
            'external_id' => "category:{$category->id}",
        ]);

        ImportedTransaction::factory()->for($import)->create([
            'date' => '2026-01-02',
            'description' => 'Duplicate Transaction',
            'amount' => 50.00,
            'is_duplicate' => true,
        ]);

        $committer = new StatementImportCommitter($import);
        $result = $committer->commit();

        $this->assertTrue($result);
        $this->assertEquals(BankStatementConfig::STATUS_COMMITTED, $import->fresh()->status);

        // Should create Transaction for non-duplicate only
        $transactions = Transaction::where('user_id', $user->id)->get();
        $this->assertCount(1, $transactions);

        $transaction = $transactions->first();
        $this->assertEquals('2026-01-01', $transaction->date->toDateString());
        $this->assertEquals('Test Transaction', $transaction->description);
        $this->assertEquals(100.50, $transaction->amount);
        $this->assertTrue($transaction->category->is($category));

        // ImportedTransactions should be marked as committed
        $committedImported = ImportedTransaction::where('is_committed', true)->get();
        $this->assertCount(1, $committedImported);
    }

    public function test_handles_category_assignment_from_external_id(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        ImportedTransaction::factory()->for($import)->create([
            'amount' => 100.00,
            'is_duplicate' => false,
            'external_id' => "category:{$category->id}",
        ]);

        ImportedTransaction::factory()->for($import)->create([
            'amount' => 50.00,
            'is_duplicate' => false,
            'external_id' => null,
        ]);

        $committer = new StatementImportCommitter($import);
        $committer->commit();

        $transactions = Transaction::where('user_id', $user->id)->orderBy('amount', 'desc')->get();
        $this->assertCount(2, $transactions);

        // First transaction should have category
        $this->assertTrue($transactions->first()->category->is($category));

        // Second transaction should have no category
        $this->assertNull($transactions->last()->category);
    }

    public function test_determines_transaction_type_based_on_amount_and_statement_type(): void
    {
        $user = User::factory()->create();

        // Test bank statement
        $bankProfile = BankProfile::factory()->create(['statement_type' => 'bank']);
        $bankImport = BankStatementImport::factory()->for($user)->for($bankProfile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        ImportedTransaction::factory()->for($bankImport)->create([
            'amount' => 100.00, // Positive = income
            'is_duplicate' => false,
        ]);

        ImportedTransaction::factory()->for($bankImport)->create([
            'amount' => -50.00, // Negative = expense
            'is_duplicate' => false,
        ]);

        // Test credit card statement
        $ccProfile = BankProfile::factory()->create(['statement_type' => 'credit_card']);
        $ccImport = BankStatementImport::factory()->for($user)->for($ccProfile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        ImportedTransaction::factory()->for($ccImport)->create([
            'amount' => 75.00, // Positive = income (payment/refund)
            'is_duplicate' => false,
        ]);

        ImportedTransaction::factory()->for($ccImport)->create([
            'amount' => -25.00, // Negative = expense (purchase)
            'is_duplicate' => false,
        ]);

        $bankCommitter = new StatementImportCommitter($bankImport);
        $ccCommitter = new StatementImportCommitter($ccImport);

        $bankCommitter->commit();
        $ccCommitter->commit();

        $transactions = Transaction::where('user_id', $user->id)->get();
        $this->assertCount(4, $transactions);

        $bankIncome = $transactions->where('amount', 100.00)->where('type', Transaction::TYPE_INCOME)->first();
        $bankExpense = $transactions->where('amount', 50.00)->where('type', Transaction::TYPE_EXPENSE)->first();
        $ccIncome = $transactions->where('amount', 75.00)->where('type', Transaction::TYPE_INCOME)->first();
        $ccExpense = $transactions->where('amount', 25.00)->where('type', Transaction::TYPE_EXPENSE)->first();

        // Bank: positive amount = income, negative amount = expense (now stored as positive)
        $this->assertNotNull($bankIncome);
        $this->assertNotNull($bankExpense);

        // Credit card: amounts stored as positive, type determined by imported amount sign
        $this->assertNotNull($ccIncome);
        $this->assertNotNull($ccExpense);
    }

    public function test_credit_card_expenses_are_stored_as_positive_amounts(): void
    {
        $user = User::factory()->create();
        $ccProfile = BankProfile::factory()->create(['statement_type' => 'credit_card']);
        $ccImport = BankStatementImport::factory()
            ->for($user)
            ->for($ccProfile, 'bankProfile')
            ->create(['status' => BankStatementConfig::STATUS_PARSED]);

        // Create imported transactions as they would come from the parser (already flipped)
        ImportedTransaction::factory()->for($ccImport)->create([
            'amount' => -100.00, // Expense (purchase) - negative in imported_transactions
            'description' => 'PURCHASE',
            'is_duplicate' => false,
        ]);

        ImportedTransaction::factory()->for($ccImport)->create([
            'amount' => 50.00, // Income (payment) - positive in imported_transactions
            'description' => 'PAYMENT',
            'is_duplicate' => false,
        ]);

        $committer = new StatementImportCommitter($ccImport);
        $result = $committer->commit();

        $this->assertTrue($result);

        $transactions = Transaction::where('user_id', $user->id)->get();
        $this->assertCount(2, $transactions);

        $expense = $transactions->where('type', Transaction::TYPE_EXPENSE)->first();
        $income = $transactions->where('type', Transaction::TYPE_INCOME)->first();

        // CRITICAL: Both amounts should be positive in the transactions table
        $this->assertEquals(100.00, $expense->amount); // Was -100, should be 100
        $this->assertEquals(Transaction::TYPE_EXPENSE, $expense->type);
        $this->assertEquals('PURCHASE', $expense->description);

        $this->assertEquals(50.00, $income->amount); // Should remain 50
        $this->assertEquals(Transaction::TYPE_INCOME, $income->type);
        $this->assertEquals('PAYMENT', $income->description);
    }

    public function test_fails_if_import_not_in_parsed_status(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_UPLOADED]);

        $committer = new StatementImportCommitter($import);
        $result = $committer->commit();

        $this->assertFalse($result);
        $this->assertEquals(BankStatementConfig::STATUS_UPLOADED, $import->fresh()->status);
    }

    public function test_is_idempotent(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        ImportedTransaction::factory()->for($import)->create([
            'amount' => 100.00,
            'is_duplicate' => false,
        ]);

        $committer = new StatementImportCommitter($import);

        // Commit twice
        $result1 = $committer->commit();
        $result2 = $committer->commit();

        $this->assertTrue($result1);
        $this->assertTrue($result2);

        // Should not create duplicate transactions
        $transactions = Transaction::where('user_id', $user->id)->get();
        $this->assertCount(1, $transactions);
        $this->assertEquals(BankStatementConfig::STATUS_COMMITTED, $import->fresh()->status);
    }

    public function test_rolls_back_on_failure(): void
    {
        $user = User::factory()->create();
        $profile = BankProfile::factory()->create();
        $import = BankStatementImport::factory()->for($user)->for($profile, 'bankProfile')->create(['status' => BankStatementConfig::STATUS_PARSED]);

        // Create imported transaction with invalid category reference
        ImportedTransaction::factory()->for($import)->create([
            'amount' => 100.00,
            'is_duplicate' => false,
            'external_id' => 'category:99999', // Non-existent category
        ]);

        $committer = new StatementImportCommitter($import);
        $result = $committer->commit();

        // Should fail gracefully
        $this->assertFalse($result);

        // No transactions should be created
        $this->assertCount(0, Transaction::where('user_id', $user->id)->get());

        // Import status should remain parsed
        $this->assertEquals(BankStatementConfig::STATUS_PARSED, $import->fresh()->status);
    }
}
