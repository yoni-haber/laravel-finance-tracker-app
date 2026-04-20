<?php

namespace Tests\Feature;

use App\Livewire\Budgets\BudgetManager;
use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BudgetManagerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // mount()
    // -------------------------------------------------------------------------

    public function test_mount_sets_month_and_year_to_current_date(): void
    {
        $user = User::factory()->create();
        $now = now();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->assertSet('month', (int) $now->month)
            ->assertSet('year', (int) $now->year)
            ->assertSet('filterMonth', (int) $now->month)
            ->assertSet('filterYear', (int) $now->year);
    }

    // -------------------------------------------------------------------------
    // save() – create
    // -------------------------------------------------------------------------

    public function test_save_creates_budget_with_valid_data(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 6)
            ->set('year', 2025)
            ->set('amount', '500.00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Budget saved.');

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'category_id' => $category->id,
            'month' => 6,
            'year' => 2025,
            'amount' => '500.00',
        ]);
    }

    public function test_save_create_detects_duplicate_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 3,
            'year' => 2025,
        ]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 3)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('save');
    }

    public function test_save_create_rejects_category_belonging_to_other_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherCategory = Category::factory()->for($otherUser)->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $otherCategory->id)
            ->set('month', 5)
            ->set('year', 2025)
            ->set('amount', '200.00')
            ->call('save')
            ->assertHasErrors('category_id');
    }

    public function test_save_create_validates_missing_category_id(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', null)
            ->set('month', 5)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('category_id');
    }

    public function test_save_create_validates_month_out_of_range(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 0)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('month');

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 13)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('month');
    }

    public function test_save_create_validates_year_out_of_range(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 5)
            ->set('year', 1999)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('year');

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('category_id', $category->id)
            ->set('month', 5)
            ->set('year', 2101)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('year');
    }

    // -------------------------------------------------------------------------
    // save() – update
    // -------------------------------------------------------------------------

    public function test_save_updates_existing_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 4,
            'year' => 2025,
            'amount' => '300.00',
        ]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('edit', $budget->id)
            ->set('amount', '450.00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Budget saved.');

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'amount' => '450.00',
        ]);
    }

    public function test_save_update_duplicate_check_excludes_self(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 7,
            'year' => 2025,
            'amount' => '200.00',
        ]);

        // Re-saving the same budget should not trigger the duplicate error
        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('edit', $budget->id)
            ->set('amount', '250.00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Budget saved.');
    }

    public function test_save_update_returns_error_when_budget_not_found(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('budgetId', 99999)
            ->set('category_id', $category->id)
            ->set('month', 5)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('save');
    }

    public function test_save_update_returns_error_for_another_users_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $otherCategory = Category::factory()->for($otherUser)->create();
        $otherBudget = Budget::factory()->for($otherUser)->for($otherCategory, 'category')->create([
            'month' => 5,
            'year' => 2025,
        ]);

        // budgetId points to a budget that exists in DB but belongs to another user
        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('budgetId', $otherBudget->id)
            ->set('category_id', $category->id)
            ->set('month', 5)
            ->set('year', 2025)
            ->set('amount', '100.00')
            ->call('save')
            ->assertHasErrors('save');

        // Other user's budget must remain unchanged
        $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id]);
    }

    // -------------------------------------------------------------------------
    // edit()
    // -------------------------------------------------------------------------

    public function test_edit_loads_all_fields_correctly(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 8,
            'year' => 2024,
            'amount' => '750.50',
        ]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('edit', $budget->id)
            ->assertSet('budgetId', $budget->id)
            ->assertSet('category_id', $category->id)
            ->assertSet('month', 8)
            ->assertSet('year', 2024)
            ->assertSet('amount', '750.50');
    }

    public function test_edit_throws_404_for_another_users_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherBudget = Budget::factory()
            ->for($otherUser)
            ->for(Category::factory()->for($otherUser), 'category')
            ->create();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('edit', $otherBudget->id);
    }

    // -------------------------------------------------------------------------
    // delete()
    // -------------------------------------------------------------------------

    public function test_delete_removes_own_budget_and_flashes_message(): void
    {
        $user = User::factory()->create();
        $budget = Budget::factory()
            ->for($user)
            ->for(Category::factory()->for($user), 'category')
            ->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('delete', $budget->id)
            ->assertSee('Budget removed.');

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
    }

    public function test_delete_silently_ignores_another_users_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherBudget = Budget::factory()
            ->for($otherUser)
            ->for(Category::factory()->for($otherUser), 'category')
            ->create();

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('delete', $otherBudget->id);

        $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id]);
    }

    // -------------------------------------------------------------------------
    // render() – filtering
    // -------------------------------------------------------------------------

    public function test_render_filters_by_category(): void
    {
        $user = User::factory()->create();
        $catA = Category::factory()->for($user)->create(['name' => 'Groceries']);
        $catB = Category::factory()->for($user)->create(['name' => 'Rent']);

        Budget::factory()->for($user)->for($catA, 'category')->create(['month' => 1, 'year' => 2025]);
        Budget::factory()->for($user)->for($catB, 'category')->create(['month' => 1, 'year' => 2025]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('filterMonth', 1)
            ->set('filterYear', 2025)
            ->set('filterCategory', $catA->id)
            ->assertViewHas('budgets', function ($budgets) use ($catA, $catB) {
                return $budgets->count() === 1
                    && $budgets->first()->category_id === $catA->id
                    && $budgets->doesntContain('category_id', $catB->id);
            });
    }

    public function test_render_filters_by_month_and_year(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Travel']);

        $budgetFeb = Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 2,
            'year' => 2025,
            'amount' => '100.00',
        ]);
        $budgetMar = Budget::factory()->for($user)->for($category, 'category')->create([
            'month' => 3,
            'year' => 2025,
            'amount' => '200.00',
        ]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->set('filterMonth', 2)
            ->set('filterYear', 2025)
            ->set('filterCategory', null)
            ->assertViewHas('budgets', function ($budgets) use ($budgetFeb, $budgetMar) {
                return $budgets->count() === 1
                    && $budgets->first()->id === $budgetFeb->id
                    && $budgets->doesntContain('id', $budgetMar->id);
            });
    }

    // -------------------------------------------------------------------------
    // resetForm()
    // -------------------------------------------------------------------------

    public function test_reset_form_clears_state_to_defaults(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $budget = Budget::factory()->for($user)->for($category, 'category')->create([
            'amount' => '999.00',
        ]);

        Livewire::actingAs($user)
            ->test(BudgetManager::class)
            ->call('edit', $budget->id)
            ->assertSet('budgetId', $budget->id)
            ->call('resetForm')
            ->assertSet('budgetId', null)
            ->assertSet('category_id', null)
            ->assertSet('amount', '0.00');
    }
}
