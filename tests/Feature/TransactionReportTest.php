<?php

namespace Tests\Feature;

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

    public function test_returns_empty_collection_when_transactions_table_missing(): void
    {
        Schema::shouldReceive('hasTable')
            ->once()
            ->with('transactions')
            ->andReturn(false);

        $report = TransactionReport::monthlyWithRecurring(1, 1, 2024);

        $this->assertTrue($report->isEmpty());
    }

    public function test_filters_transactions_by_category_and_merges_recurring_entries(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($category)->create([
            'type' => 'expense',
            'amount' => 50,
            'date' => '2024-03-10',
        ]);

        Transaction::factory()->for($user)->for($category)->recurring('monthly')->create([
            'type' => 'income',
            'amount' => 100,
            'date' => '2024-01-05',
        ]);

        Transaction::factory()->for($user)->for($otherCategory)->recurring('weekly')->create([
            'type' => 'expense',
            'amount' => 25,
            'date' => '2024-03-01',
        ]);

        $report = TransactionReport::monthlyWithRecurring($user->id, 3, 2024, $category->id);

        $this->assertCount(2, $report);
        $this->assertSame($category->id, $report->pluck('category_id')->unique()->sole());
        $this->assertEqualsCanonicalizing([100.00, 50.00], $report->pluck('amount')->all());
    }
}
