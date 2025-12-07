<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Support\TransactionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_income_and_expense_for_month(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($category)->create([
            'type' => 'income',
            'amount' => 500,
            'date' => '2024-02-05',
        ]);

        Transaction::factory()->for($user)->for($category)->create([
            'type' => 'expense',
            'amount' => 200,
            'date' => '2024-02-10',
        ]);

        $transactions = TransactionReport::monthlyWithRecurring($user->id, 2, 2024);

        $this->assertSame(500.0, $transactions->where('type', 'income')->sum('amount'));
        $this->assertSame(200.0, $transactions->where('type', 'expense')->sum('amount'));
    }
}
