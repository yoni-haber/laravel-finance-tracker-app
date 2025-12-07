<?php

namespace App\Livewire;

use App\Models\Budget;
use App\Models\Category;
use App\Support\Money;
use App\Support\TransactionReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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

        return view('livewire.dashboard', [
            'income' => $income,
            'expenses' => $expenses,
            'net' => $net,
            'budgetSummaries' => $budgetSummaries,
            'categoryBreakdown' => $categoryBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    protected function monthlyTrend(int $userId): array
    {
        $months = collect(range(1, 12));
        $currentYear = $this->year;

        $incomeData = [];
        $expenseData = [];

        foreach ($months as $month) {
            $transactions = TransactionReport::monthlyWithRecurring($userId, $month, $currentYear);
            $incomeData[] = (float) $transactions->where('type', 'income')->sum('amount');
            $expenseData[] = (float) $transactions->where('type', 'expense')->sum('amount');
        }

        return [
            'labels' => $months->map(fn ($month) => now()->startOfYear()->month($month)->shortMonthName),
            'income' => $incomeData,
            'expenses' => $expenseData,
        ];
    }
}
