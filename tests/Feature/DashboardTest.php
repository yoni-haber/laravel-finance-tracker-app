<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
    }

    public function test_mount_sets_current_month_and_year(): void
    {
        Carbon::setTestNow('2024-05-15');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSet('month', 5)
            ->assertSet('year', 2024);
    }

    public function test_it_shows_placeholder_data_when_schema_missing(): void
    {
        Schema::shouldReceive('hasTable')->once()->with('transactions')->andReturnFalse();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('schemaMissing', true)
            ->assertViewHas('income', 0)
            ->assertViewHas('expenses', 0)
            ->assertViewHas('net', 0);
    }

    public function test_render_calculates_dashboard_metrics(): void
    {
        Carbon::setTestNow('2024-05-15');

        $user = User::factory()->create();
        $salaryCategory = Category::factory()->create(['user_id' => $user->id, 'name' => 'Salary']);
        $groceriesCategory = Category::factory()->create(['user_id' => $user->id, 'name' => 'Groceries']);

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $groceriesCategory->id,
            'month' => 5,
            'year' => 2024,
            'amount' => 500,
        ]);

        $user->transactions()->createMany([
            [
                'category_id' => $salaryCategory->id,
                'type' => 'income',
                'amount' => 2000,
                'date' => '2024-05-05',
            ],
            [
                'category_id' => null,
                'type' => 'income',
                'amount' => 500,
                'date' => '2024-05-06',
            ],
            [
                'category_id' => $groceriesCategory->id,
                'type' => 'expense',
                'amount' => 200,
                'date' => '2024-05-07',
            ],
            [
                'category_id' => null,
                'type' => 'expense',
                'amount' => 50,
                'date' => '2024-05-08',
            ],
        ]);

        $component = Livewire::actingAs($user)->test(Dashboard::class);

        $component
            ->assertViewHas('income', '2500.00')
            ->assertViewHas('expenses', '250.00')
            ->assertViewHas('net', '2250.00')
            ->assertViewHas('schemaMissing', false);

        $component->assertViewHas('budgetSummaries', function ($summaries) {
            $groceries = $summaries->firstWhere('category', 'Groceries');

            return $groceries['budget'] === '500.00'
                && $groceries['actual'] === '200.00'
                && $groceries['remaining'] === '300.00'
                && $groceries['overspent'] === false;
        });

        $component->assertViewHas('incomeCategoryBreakdown', function ($breakdown) {
            $salary = collect($breakdown)->firstWhere('category', 'Salary');
            $uncategorised = collect($breakdown)->firstWhere('category', 'Uncategorised');

            return $salary['total'] === '2000.00'
                && $uncategorised['total'] === '500.00';
        });

        $component->assertViewHas('expenseCategoryBreakdown', function ($breakdown) {
            $groceries = collect($breakdown)->firstWhere('category', 'Groceries');
            $uncategorised = collect($breakdown)->firstWhere('category', 'Uncategorised');

            return $groceries['total'] === '200.00'
                && $uncategorised['total'] === '50.00';
        });
    }

    public function test_budget_actuals_ignore_future_projected_recurring_transactions(): void
    {
        Carbon::setTestNow('2024-05-10');

        $user = User::factory()->create();
        $groceriesCategory = Category::factory()->create(['user_id' => $user->id, 'name' => 'Groceries']);

        Budget::factory()->create([
            'user_id' => $user->id,
            'category_id' => $groceriesCategory->id,
            'month' => 5,
            'year' => 2024,
            'amount' => 500,
        ]);

        $user->transactions()->create([
            'category_id' => $groceriesCategory->id,
            'type' => 'expense',
            'amount' => 100,
            'date' => '2024-05-01',
            'is_recurring' => true,
            'frequency' => 'weekly',
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('budgetSummaries', function ($summaries) {
                $groceries = $summaries->firstWhere('category', 'Groceries');

                return $groceries['actual'] === '200.00'
                    && $groceries['remaining'] === '300.00';
            });
    }
}
