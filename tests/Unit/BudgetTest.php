<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_casts_attributes_correctly(): void
    {
        $budget = Budget::factory()->create([
            'amount' => 150.5,
            'month' => 3,
            'year' => 2024,
        ])->fresh();

        $this->assertSame('150.50', $budget->amount);
        $this->assertSame(3, $budget->month);
        $this->assertSame(2024, $budget->year);
    }

    public function test_it_belongs_to_a_user_and_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $budget = Budget::factory()
            ->for($user)
            ->for($category)
            ->create();

        $this->assertTrue($budget->user->is($user));
        $this->assertTrue($budget->category->is($category));
    }

    public function test_it_retrieves_transactions_for_budget_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($user)->create();

        $budget = Budget::factory()
            ->for($user)
            ->for($category)
            ->create();

        $categoryTransaction = Transaction::factory()
            ->for($user)
            ->for($category)
            ->create();

        Transaction::factory()
            ->for($user)
            ->for($otherCategory)
            ->create();

        $transactions = $budget->transactions;

        $this->assertCount(1, $transactions);
        $this->assertTrue($transactions->first()->is($categoryTransaction));
    }

    public function test_it_ignores_transactions_that_do_not_belong_to_budget_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($owner)->create();

        $budget = Budget::factory()->for($owner)->for($category)->create();

        Transaction::factory()
            ->for($otherUser)
            ->create([
                'category_id' => $category->id,
                'user_id' => $otherUser->id,
            ]);

        $this->assertCount(0, $budget->transactions);
    }
}
