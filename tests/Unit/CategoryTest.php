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

    public function test_category_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $category->user);
        $this->assertTrue($category->user->is($user));
    }

    public function test_category_has_many_transactions(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Transaction::factory()->count(3)->for($user)->for($category)->create();

        $this->assertCount(3, $category->transactions);
        $this->assertTrue($category->transactions->every(fn ($transaction) => $transaction->category->is($category)));
    }

    public function test_category_has_many_budgets(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        Budget::factory()
            ->count(2)
            ->state(new \Illuminate\Database\Eloquent\Factories\Sequence(
                ['month' => 1, 'year' => 2025],
                ['month' => 2, 'year' => 2025],
            ))
            ->for($user)
            ->for($category)
            ->create();

        $this->assertCount(2, $category->budgets);
        $this->assertTrue($category->budgets->every(fn ($budget) => $budget->category->is($category)));
    }
}
