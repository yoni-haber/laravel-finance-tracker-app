<?php

namespace App\Livewire\Reports;

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

    public function mount(): void
    {
        $this->chartData = $this->chartDataForRange($this->range, Auth::id());
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
}
