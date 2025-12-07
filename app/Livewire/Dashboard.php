<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Support\Money;
use App\Support\TransactionReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public int $month;
    public int $year;
    public function mount(): void
    {
        $now = now();
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function render(): View
    {
        $userId = Auth::id();

        if (! $this->schemaReady()) {
            return view('livewire.dashboard', [
                'income' => 0,
                'expenses' => 0,
                'net' => 0,
                'budgetSummaries' => collect(),
                'incomeCategoryBreakdown' => collect(),
                'expenseCategoryBreakdown' => collect(),
                'schemaMissing' => true,
            ]);
        }

        $transactions = TransactionReport::monthlyWithRecurring($userId, $this->month, $this->year);
        $income = $transactions->where('type', 'income')->sum('amount');
        $expenses = $transactions->where('type', 'expense')->sum('amount');
        $net = Money::subtract($income, $expenses);

        $budgets = Budget::with('category')
            ->where('user_id', $userId)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();

        $budgetSummaries = $budgets->map(function (Budget $budget) use ($transactions) {
            $actual = $transactions
                ->where('category_id', $budget->category_id)
                ->where('type', 'expense')
                ->sum('amount');

            $remaining = Money::subtract($budget->amount, $actual);

            return [
                'category' => $budget->category->name,
                'budget' => $budget->amount,
                'actual' => $actual,
                'remaining' => $remaining,
                'overspent' => $actual > $budget->amount,
            ];
        });

        $categoryIncome = $this->categoryTotals($transactions, 'income');
        $categoryExpenses = $this->categoryTotals($transactions, 'expense');

        $this->dispatch('dashboard-charts-updated',
            incomeCategoryBreakdown: $categoryIncome->all(),
            expenseCategoryBreakdown: $categoryExpenses->all(),
        );

        return view('livewire.dashboard', [
            'income' => $income,
            'expenses' => $expenses,
            'net' => $net,
            'budgetSummaries' => $budgetSummaries,
            'incomeCategoryBreakdown' => $categoryIncome,
            'expenseCategoryBreakdown' => $categoryExpenses,
            'schemaMissing' => false,
        ]);
    }

    protected function schemaReady(): bool
    {
        return Schema::hasTable('transactions')
            && Schema::hasTable('categories')
            && Schema::hasTable('budgets');
    }

    private function categoryTotals(Collection $transactions, string $type): Collection
    {
        return $transactions
            ->where('type', $type)
            ->groupBy('category.name')
            ->map(fn ($items, $category) => [
                'category' => $category ?? 'Uncategorised',
                'total' => $items->sum('amount'),
            ])->values();
    }
}
