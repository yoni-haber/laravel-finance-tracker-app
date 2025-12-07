<?php

namespace App\Livewire\Reports;

use App\Models\NetWorthEntry;
use App\Support\TransactionReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Reports')]
class ReportsHub extends Component
{
    public string $range = '12_months';

    public array $chartData = [];

    public string $net_worth_date;

    public string $assets = '0.00';

    public string $liabilities = '0.00';

    public array $netWorthChartData = [];

    public bool $netWorthTableMissing = false;

    public function mount(): void
    {
        $this->chartData = $this->chartDataForRange($this->range, Auth::id());
        $this->net_worth_date = now()->toDateString();

        $this->netWorthTableMissing = ! Schema::hasTable('net_worth_entries');
        $this->netWorthChartData = $this->netWorthTableMissing
            ? []
            : $this->buildNetWorthChartData(Auth::id());
    }

    public function render(): View
    {
        return view('livewire.reports.hub', [
            'chartData' => $this->chartData,
            'rangeOptions' => $this->rangeOptions(),
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
        $this->netWorthTableMissing = ! Schema::hasTable('net_worth_entries');

        if ($this->netWorthTableMissing) {
            session()->flash('status', 'Run migrations to enable net worth tracking.');
            return;
        }

        $data = $this->validate([
            'net_worth_date' => 'required|date',
            'assets' => 'required|numeric|min:0',
            'liabilities' => 'required|numeric|min:0',
        ]);

        $netWorth = (float) $data['assets'] - (float) $data['liabilities'];

        NetWorthEntry::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'date' => $data['net_worth_date'],
            ],
            [
                'assets' => $data['assets'],
                'liabilities' => $data['liabilities'],
                'net_worth' => $netWorth,
            ],
        );

        $this->netWorthChartData = $this->buildNetWorthChartData(Auth::id());
        $this->dispatch('net-worth-chart-data', chartData: $this->netWorthChartData);

        session()->flash('status', 'Net worth entry saved.');
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

    protected function buildNetWorthChartData(int $userId): array
    {
        $entries = NetWorthEntry::forUser($userId)
            ->orderBy('date')
            ->get();

        return [
            'labels' => $entries->map(fn (NetWorthEntry $entry) => $entry->date->format('j M Y'))->toArray(),
            'netWorth' => $entries->map(fn (NetWorthEntry $entry) => (float) $entry->net_worth)->toArray(),
        ];
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
}
