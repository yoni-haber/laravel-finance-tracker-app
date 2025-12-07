<?php

namespace App\Livewire\Reports;

use App\Models\NetWorthEntry;
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
    public string $range = '12_months';

    public array $chartData = [];

    public string $netWorthDate;

    public string $assets = '';

    public string $liabilities = '';

    public array $netWorthChart = [];

    public function mount(): void
    {
        $this->chartData = $this->chartDataForRange($this->range, Auth::id());

        $this->netWorthDate = now()->toDateString();

        $this->netWorthChart = $this->netWorthChartData(Auth::id());
    }

    public function render(): View
    {
        return view('livewire.reports.hub', [
            'chartData' => $this->chartData,
            'rangeOptions' => $this->rangeOptions(),
            'netWorthChart' => $this->netWorthChart,
        ]);
    }

    public function updatedRange(): void
    {
        if (! array_key_exists($this->range, $this->rangeOptions())) {
            $this->range = '12_months';
        }

        $this->chartData = $this->chartDataForRange($this->range, Auth::id());

        $this->dispatch('reports-chart-data', chartData: $this->chartData);
    }

    public function saveNetWorth(): void
    {
        $validated = $this->validate([
            'assets' => ['required', 'numeric', 'min:0'],
            'liabilities' => ['required', 'numeric', 'min:0'],
            'netWorthDate' => ['required', 'date'],
        ], [
            'assets.required' => 'Please enter your total assets.',
            'liabilities.required' => 'Please enter your total liabilities.',
            'netWorthDate.required' => 'Please choose the date for this entry.',
        ]);

        $netWorth = Money::subtract($validated['assets'], $validated['liabilities']);

        NetWorthEntry::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'recorded_on' => $validated['netWorthDate'],
            ],
            [
                'assets' => $validated['assets'],
                'liabilities' => $validated['liabilities'],
                'net_worth' => $netWorth,
            ],
        );

        $this->reset(['assets', 'liabilities']);

        $this->netWorthChart = $this->netWorthChartData(Auth::id());

        $this->dispatch('net-worth-chart-data', chartData: $this->netWorthChart);

        session()->flash('netWorthSaved', 'Net worth entry saved.');
    }

    protected function chartDataForRange(string $range, int $userId): array
    {
        $labels = [];
        $income = [];
        $expenses = [];

        $start = now()->startOfMonth();

        $monthsCount = match ($range) {
            '3_months' => 3,
            '6_months' => 6,
            'ytd' => (int) $start->month,
            default => 12,
        };

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $monthDate = $start->copy()->subMonths($i);
            $labels[] = $monthDate->format('M Y');
            $transactions = TransactionReport::monthlyWithRecurring($userId, (int) $monthDate->month, (int) $monthDate->year);
            $income[] = (float) $transactions->where('type', 'income')->sum('amount');
            $expenses[] = (float) $transactions->where('type', 'expense')->sum('amount');
        }

        return compact('labels', 'income', 'expenses');
    }

    protected function rangeOptions(): array
    {
        return [
            '3_months' => 'Last 3 Months',
            '6_months' => 'Last 6 Months',
            '12_months' => 'Last 12 Months',
            'ytd' => 'Year to Date',
        ];
    }

    protected function netWorthChartData(int $userId): array
    {
        $entries = NetWorthEntry::where('user_id', $userId)
            ->orderBy('recorded_on')
            ->get();

        return [
            'labels' => $entries->map(fn (NetWorthEntry $entry) => $entry->recorded_on?->format('d M Y'))->all(),
            'values' => $entries->map(fn (NetWorthEntry $entry) => (float) $entry->net_worth)->all(),
        ];
    }

    public function getNetWorthTotalProperty(): string
    {
        return Money::subtract($this->assets ?: 0, $this->liabilities ?: 0);
    }
}
