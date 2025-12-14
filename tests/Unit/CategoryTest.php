<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->assertTrue($category->user->is($user));
    }

    public function test_category_has_transactions_and_budgets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transactions = Transaction::factory()->count(2)->for($user)->for($category)->create();
        $budgets = Budget::factory()->count(2)->for($user)->for($category)->create();

        $this->assertCount(2, $category->transactions);
        $this->assertTrue($category->transactions->pluck('id')->diff($transactions->pluck('id'))->isEmpty());

        $this->assertCount(2, $category->budgets);
        $this->assertTrue($category->budgets->pluck('id')->diff($budgets->pluck('id'))->isEmpty());
    }
}
