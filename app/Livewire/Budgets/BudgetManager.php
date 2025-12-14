<?php

namespace App\Livewire\Budgets;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Budgets')]
class BudgetManager extends Component
{
    #[Rule('required|exists:categories,id')]
    public ?int $category_id = null;

    #[Rule('required|integer|min:1|max:12')]
    public int $month;

    #[Rule('required|integer|min:2000|max:2100')]
    public int $year;

    #[Rule('required|numeric|min:0')]
    public string $amount = '0.00';

    public ?int $budgetId = null;

    public ?int $filterCategory = null;

    public int $filterMonth;

    public int $filterYear;

    public function mount(): void
    {
        $now = now();
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
        $this->filterMonth = (int) $now->month;
        $this->filterYear = (int) $now->year;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();

        if ($this->budgetExists($data)) {
            $this->addError('save', 'A budget for this category, month, and year already exists.');

            return;
        }

        Budget::updateOrCreate([
            'id' => $this->budgetId,
            'user_id' => Auth::id(),
        ], $data);

        $this->resetForm();
        session()->flash('status', 'Budget saved.');
    }

    public function edit(int $budgetId): void
    {
        $budget = Budget::where('user_id', Auth::id())->findOrFail($budgetId);
        $this->budgetId = $budget->id;
        $this->category_id = $budget->category_id;
        $this->month = $budget->month;
        $this->year = $budget->year;
        $this->amount = (string) $budget->amount;
    }

    public function delete(int $budgetId): void
    {
        Budget::where('user_id', Auth::id())->where('id', $budgetId)->delete();
        session()->flash('status', 'Budget removed.');
    }

    public function render(): View
    {
        $userId = Auth::id();

        $budgets = Budget::with('category')
            ->where('user_id', $userId)
            ->when($this->filterCategory, fn ($query) => $query->where('category_id', $this->filterCategory))
            ->when($this->filterMonth, fn ($query) => $query->where('month', $this->filterMonth))
            ->when($this->filterYear, fn ($query) => $query->where('year', $this->filterYear))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        return view('livewire.budgets.manager', [
            'budgets' => $budgets,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    public function resetForm(): void
    {
        $this->budgetId = null;
        $this->category_id = null;
        $this->amount = '0.00';

        $this->resetValidation();
        $this->resetErrorBag();
    }

    private function budgetExists(array $data): bool
    {
        return Budget::where('user_id', $data['user_id'])
            ->where('category_id', $data['category_id'])
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->when($this->budgetId, fn ($query) => $query->where('id', '!=', $this->budgetId))
            ->exists();
    }
}
