<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_transactions_project_across_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($category)->recurring('weekly')->create([
            'type' => 'expense',
            'amount' => 25,
            'date' => '2024-03-01',
        ]);

        $transactions = TransactionReport::monthlyWithRecurring($user->id, 3, 2024);

        $this->assertCount(5, $transactions->where('type', 'expense'));
        $this->assertSame(125.0, $transactions->where('type', 'expense')->sum('amount'));
    }
}
