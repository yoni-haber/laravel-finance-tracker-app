<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_filter_transactions(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $food = Category::factory()->for($user)->create();
        $rent = Category::factory()->for($user)->create();

        $januaryExpense = Transaction::factory()->for($user)->for($food)->create([
            'type' => 'expense',
            'date' => '2024-01-15',
        ]);
        $februaryIncome = Transaction::factory()->for($user)->for($rent)->create([
            'type' => 'income',
            'date' => '2024-02-01',
        ]);
        Transaction::factory()->for($otherUser)->for($food)->create([
            'type' => 'expense',
            'date' => '2024-01-05',
        ]);

        $this->assertEquals(
            [$user->id],
            Transaction::forUser($user->id)->pluck('user_id')->unique()->all()
        );

        $this->assertTrue(Transaction::income()->get()->contains($februaryIncome));
        $this->assertTrue(Transaction::expense()->get()->contains($januaryExpense));

        $this->assertCount(
            1,
            Transaction::forMonthYear(1, 2024)->forCategory($food->id)->get()
        );

        $this->assertCount(
            2,
            Transaction::forMonthYear(1, 2024)->forCategory(null)->get()
        );
    }

    public function test_non_recurring_transaction_in_month_returns_clone(): void
    {
        $transaction = Transaction::factory()->for(User::factory())->for(Category::factory())->create([
            'type' => 'expense',
            'date' => '2024-02-10',
        ]);

        $occurrences = $transaction->projectedOccurrencesForMonth(2, 2024);

        $this->assertCount(1, $occurrences);
        $this->assertFalse($occurrences->first()->getAttribute('projected'));
        $this->assertEquals('2024-02-10', $occurrences->first()->date->toDateString());
        $this->assertSame($transaction->category->id, $occurrences->first()->category->id);
    }

    public function test_non_recurring_transaction_outside_month_returns_empty(): void
    {
        $transaction = Transaction::factory()->for(User::factory())->for(Category::factory())->create([
            'type' => 'expense',
            'date' => '2024-01-15',
        ]);

        $occurrences = $transaction->projectedOccurrencesForMonth(2, 2024);

        $this->assertTrue($occurrences->isEmpty());
    }

    public function test_recurring_transactions_skip_exceptions_and_use_frequency(): void
    {
        $transaction = Transaction::factory()
            ->for(User::factory())
            ->for(Category::factory())
            ->recurring('monthly')
            ->create([
                'type' => 'income',
                'amount' => 500,
                'date' => Carbon::create(2024, 1, 15),
                'recurring_until' => Carbon::create(2024, 4, 20),
            ]);

        TransactionException::create([
            'transaction_id' => $transaction->id,
            'date' => '2024-03-15',
        ]);

        $marchOccurrences = $transaction->projectedOccurrencesForMonth(3, 2024);
        $februaryOccurrences = $transaction->projectedOccurrencesForMonth(2, 2024);

        $this->assertTrue($marchOccurrences->isEmpty());
        $this->assertCount(1, $februaryOccurrences);
        $this->assertTrue($februaryOccurrences->first()->getAttribute('projected'));
        $this->assertEquals('2024-02-15', $februaryOccurrences->first()->date->toDateString());
    }

    public function test_recurring_without_frequency_returns_empty(): void
    {
        $transaction = Transaction::factory()
            ->for(User::factory())
            ->for(Category::factory())
            ->create([
                'is_recurring' => true,
                'frequency' => null,
                'date' => Carbon::create(2024, 1, 1),
            ]);

        $occurrences = $transaction->projectedOccurrencesForMonth(1, 2024);

        $this->assertTrue($occurrences->isEmpty());
    }

    public function test_recurring_end_before_month_returns_empty(): void
    {
        $transaction = Transaction::factory()
            ->for(User::factory())
            ->for(Category::factory())
            ->recurring('weekly')
            ->create([
                'date' => Carbon::create(2024, 1, 1),
                'recurring_until' => Carbon::create(2024, 1, 20),
            ]);

        $occurrences = $transaction->projectedOccurrencesForMonth(2, 2024);

        $this->assertTrue($occurrences->isEmpty());
    }
}
