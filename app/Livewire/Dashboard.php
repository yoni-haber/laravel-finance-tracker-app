<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Category;
use App\Support\Money;
use App\Support\TransactionReport;
use Illuminate\Contracts\View\View;
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
    public ?int $categoryId = null;

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
                'categoryBreakdown' => collect(),
                'monthlyTrend' => [
                    'labels' => collect(range(1, 12))->map(fn ($month) => now()->startOfYear()->month($month)->shortMonthName),
                    'income' => array_fill(0, 12, 0),
                    'expenses' => array_fill(0, 12, 0),
                ],
                'categories' => collect(),
                'schemaMissing' => true,
            ]);
        }

        $transactions = TransactionReport::monthlyWithRecurring($userId, $this->month, $this->year, $this->categoryId);
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

        $categoryBreakdown = $transactions
            ->where('type', 'expense')
            ->groupBy('category.name')
            ->map(fn ($items, $category) => [
                'category' => $category ?? 'Uncategorised',
                'total' => $items->sum('amount'),
            ])->values();

        $monthlyTrend = $this->monthlyTrend($userId);

        $this->dispatch('dashboard-charts-updated',
            monthlyTrend: $monthlyTrend,
            categoryBreakdown: $categoryBreakdown->all(),
        );

        return view('livewire.dashboard', [
            'income' => $income,
            'expenses' => $expenses,
            'net' => $net,
            'budgetSummaries' => $budgetSummaries,
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
            'schemaMissing' => false,
        ]);
    }

    protected function monthlyTrend(int $userId): array
    {
        $transactions = TransactionReport::monthlyWithRecurring($userId, $this->month, $this->year, $this->categoryId);

        $label = now()->setDate($this->year, $this->month, 1)->format('F Y');
        $incomeTotal = (float) $transactions->where('type', 'income')->sum('amount');
        $expenseTotal = (float) $transactions->where('type', 'expense')->sum('amount');

        return [
            'labels' => [$label],
            'income' => [$incomeTotal],
            'expenses' => [$expenseTotal],
        ];
    }

    protected function schemaReady(): bool
    {
        return Schema::hasTable('transactions')
            && Schema::hasTable('categories')
            && Schema::hasTable('budgets');
    }
}
