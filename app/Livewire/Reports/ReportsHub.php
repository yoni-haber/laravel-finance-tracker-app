<?php

namespace App\Livewire\Reports;

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
#[Title('Reports')]
class ReportsHub extends Component
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

        $monthly = [
            'income' => $transactions->where('type', 'income')->sum('amount'),
            'expenses' => $transactions->where('type', 'expense')->sum('amount'),
        ];
        $monthly['net'] = Money::subtract($monthly['income'], $monthly['expenses']);

        $yearlyTransactions = collect(range(1, 12))->flatMap(
            fn ($month) => TransactionReport::monthlyWithRecurring($userId, $month, $this->year)
        );

        $yearlyIncome = $yearlyTransactions->where('type', 'income')->sum('amount');
        $yearlyExpenses = $yearlyTransactions->where('type', 'expense')->sum('amount');

        $categoryBreakdown = $transactions
            ->groupBy('category.name')
            ->map(fn ($items, $category) => [
                'category' => $category ?? 'Uncategorised',
                'income' => $items->where('type', 'income')->sum('amount'),
                'expenses' => $items->where('type', 'expense')->sum('amount'),
            ])->values();

        $budgetComparison = $this->budgetComparison($transactions);

        $barChart = $this->barChartData($userId);

        return view('livewire.reports.hub', [
            'monthly' => $monthly,
            'yearly' => [
                'income' => $yearlyIncome,
                'expenses' => $yearlyExpenses,
                'net' => Money::subtract($yearlyIncome, $yearlyExpenses),
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'budgetComparison' => $budgetComparison,
            'barChart' => $barChart,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    protected function budgetComparison($transactions): array
    {
        $budgets = Budget::with('category')
            ->where('user_id', Auth::id())
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->get();

        return $budgets->map(function (Budget $budget) use ($transactions) {
            $actual = $transactions
                ->where('category_id', $budget->category_id)
                ->where('type', 'expense')
                ->sum('amount');

            return [
                'category' => $budget->category->name,
                'budget' => $budget->amount,
                'actual' => $actual,
                'remaining' => Money::subtract($budget->amount, $actual),
                'overspent' => $actual > $budget->amount,
            ];
        })->values()->all();
    }

    protected function barChartData(int $userId): array
    {
        $currentYear = $this->year;
        $labels = [];
        $income = [];
        $expenses = [];

        foreach (range(1, 12) as $month) {
            $labels[] = now()->startOfYear()->month($month)->shortMonthName;
            $transactions = TransactionReport::monthlyWithRecurring($userId, $month, $currentYear);
            $income[] = (float) $transactions->where('type', 'income')->sum('amount');
            $expenses[] = (float) $transactions->where('type', 'expense')->sum('amount');
        }

        return compact('labels', 'income', 'expenses');
    }
}
