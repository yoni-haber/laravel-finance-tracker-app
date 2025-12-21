<?php

namespace Tests\Unit\Support;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TransactionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_empty_collection_when_transactions_table_is_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('transactions')
            ->andReturnFalse();

        $result = TransactionReport::monthlyWithRecurring(1, 1, 2024);

        $this->assertTrue($result->isEmpty());
    }

    public function test_returns_empty_collection_when_categories_table_is_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('transactions')
            ->andReturnTrue();

        Schema::shouldReceive('hasTable')
            ->once()
            ->with('categories')
            ->andReturnFalse();

        $result = TransactionReport::monthlyWithRecurring(1, 1, 2024);

        $this->assertTrue($result->isEmpty());
    }

    public function test_filters_transactions_by_category_and_expands_recurring_entries(): void
    {
        $user = User::factory()->create();
        $primaryCategory = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($primaryCategory)->recurring('weekly')->create([
            'type' => 'expense',
            'amount' => 10,
            'date' => '2024-05-01',
        ]);

        Transaction::factory()->for($user)->for($primaryCategory)->create([
            'type' => 'expense',
            'amount' => 20,
            'date' => '2024-05-10',
        ]);

        Transaction::factory()->for($user)->for($otherCategory)->create([
            'type' => 'expense',
            'amount' => 99,
            'date' => '2024-05-05',
        ]);

        $transactions = TransactionReport::monthlyWithRecurring($user->id, 5, 2024, $primaryCategory->id);

        $this->assertCount(6, $transactions);
        $this->assertTrue($transactions->every(fn ($transaction) => $transaction->category_id === $primaryCategory->id));
        $this->assertSame(70.0, $transactions->sum('amount'));
    }
}
