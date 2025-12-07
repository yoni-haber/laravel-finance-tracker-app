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
    public function render(): View
    {
        $userId = Auth::id();
        $chartData = $this->twelveMonthChartData($userId);

        return view('livewire.reports.hub', [
            'chartData' => $chartData,
        ]);
    }

    protected function twelveMonthChartData(int $userId): array
    {
        $labels = [];
        $income = [];
        $expenses = [];

        $start = now()->startOfMonth();

        for ($i = 11; $i >= 0; $i--) {
            $monthDate = $start->copy()->subMonths($i);
            $labels[] = $monthDate->format('M Y');
            $transactions = TransactionReport::monthlyWithRecurring($userId, (int) $monthDate->month, (int) $monthDate->year);
            $income[] = (float) $transactions->where('type', 'income')->sum('amount');
            $expenses[] = (float) $transactions->where('type', 'expense')->sum('amount');
        }

        return compact('labels', 'income', 'expenses');
    }
}
