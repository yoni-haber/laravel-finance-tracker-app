<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\User;
use App\Support\Money;
use App\Support\TransactionReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
        $userId = Auth::id() ?? User::query()->value('id');

        if (! $userId) {
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

        $incomePennies = Money::normalize(
            $transactions->where('type', 'income')->sum('amount')
        );
        $expensePennies = Money::normalize(
            $transactions->where('type', 'expense')->sum('amount')
        );

        $income = Money::fromPennies($incomePennies);
        $expenses = Money::fromPennies($expensePennies);
        $net = Money::subtract($income, $expenses);

        $budgets = Budget::with('category')
            ->where('user_id', $userId)
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();

        $budgetSummaries = $budgets->map(function (Budget $budget) use ($transactions) {
            $budgetPennies = Money::normalize($budget->amount);
            $actualPennies = Money::normalize(
                $transactions
                    ->where('category_id', $budget->category_id)
                    ->where('type', 'expense')
                    ->sum('amount')
            );

            return [
                'category' => $budget->category->name,
                'budget' => Money::fromPennies($budgetPennies),
                'actual' => Money::fromPennies($actualPennies),
                'remaining' => Money::fromPennies($budgetPennies - $actualPennies),
                'overspent' => $actualPennies > $budgetPennies,
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
                'total' => Money::fromPennies(
                    Money::normalize($items->sum('amount'))
                ),
            ])->values();
    }
}
