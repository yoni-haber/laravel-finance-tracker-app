<div class="space-y-6">
    <div class="flex flex-wrap gap-3 items-center">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Month</label>
            <select wire:model.live="month" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ now()->startOfYear()->month($m)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Year</label>
            <input type="number" wire:model.live="year" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm" min="2000" max="2100">
        </div>
    </div>

    @if ($schemaMissing)
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm">
            <p class="font-semibold">Database not migrated</p>
            <p class="text-sm">Run <code>php artisan migrate --seed</code> to create the required tables before using the dashboard.</p>
        </div>
    @endif

    <div class="grid gap-4 md:grid-cols-3" @if($schemaMissing) aria-hidden="true" @endif>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Income</p>
            <p class="text-2xl font-semibold text-emerald-600">£{{ number_format($income, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Expenses</p>
            <p class="text-2xl font-semibold text-rose-600">£{{ number_format($expenses, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Net Balance</p>
            <p class="text-2xl font-semibold {{ $net >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">£{{ number_format($net, 2) }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3" @if($schemaMissing) aria-hidden="true" @endif>
        <div class="lg:col-span-2 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Income vs Expenses</h3>
            </div>
            <div class="mt-4">
                <canvas id="barChart" wire:ignore class="w-full"></canvas>
            </div>
        </div>
        <div class="space-y-6">
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Income by Category</h3>
                </div>
                <div class="mt-4">
                    <canvas id="incomeCategoryChart" wire:ignore class="w-full"></canvas>
                </div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Expenses by Category</h3>
                </div>
                <div class="mt-4">
                    <canvas id="expenseCategoryChart" wire:ignore class="w-full"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900" @if($schemaMissing) aria-hidden="true" @endif>
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Budgets vs Actuals</h3>
        </div>
        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($budgetSummaries as $summary)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="text-sm font-medium">{{ $summary['category'] }}</p>
                    <p class="text-xs text-gray-500">Budget £{{ number_format($summary['budget'], 2) }}</p>
                    <p class="text-xs text-gray-500">Actual £{{ number_format($summary['actual'], 2) }}</p>
                    <div class="mt-2 h-2 rounded-full bg-zinc-200 dark:bg-zinc-800">
                        @php
                            $ratio = $summary['budget'] > 0 ? min(1, $summary['actual'] / $summary['budget']) : 0;
                        @endphp
                        <div class="h-2 rounded-full {{ $summary['overspent'] ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ $ratio * 100 }}%"></div>
                    </div>
                    <p class="mt-2 text-sm {{ $summary['overspent'] ? 'text-rose-600' : 'text-emerald-600' }}">
                        {{ $summary['overspent'] ? 'Overspent' : 'Remaining' }} £{{ number_format($summary['remaining'], 2) }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No budgets defined.</p>
            @endforelse
        </div>
    </div>

    @unless ($schemaMissing)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            let barChartInstance;
            let incomeCategoryChartInstance;
            let expenseCategoryChartInstance;

            function renderCharts(payload) {
                const barCtx = document.getElementById('barChart');
                const incomeCategoryCtx = document.getElementById('incomeCategoryChart');
                const expenseCategoryCtx = document.getElementById('expenseCategoryChart');

                if (!barCtx || !incomeCategoryCtx || !expenseCategoryCtx) return;

                if (barChartInstance) barChartInstance.destroy();
                if (incomeCategoryChartInstance) incomeCategoryChartInstance.destroy();
                if (expenseCategoryChartInstance) expenseCategoryChartInstance.destroy();

                const barData = {
                    labels: payload.monthlyTrend.labels,
                    datasets: [
                        {
                            label: 'Income',
                            backgroundColor: '#10b981',
                            data: payload.monthlyTrend.income
                        },
                        {
                            label: 'Expenses',
                            backgroundColor: '#ef4444',
                            data: payload.monthlyTrend.expenses
                        }
                    ]
                };

                barChartInstance = new Chart(barCtx, {
                    type: 'bar',
                    data: barData,
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true }
                        }
                    }
                });

                const incomeCategoryData = {
                    labels: payload.incomeCategoryBreakdown.map(item => item.category),
                    datasets: [{
                        data: payload.incomeCategoryBreakdown.map(item => item.total),
                        backgroundColor: ['#1d4ed8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9']
                    }]
                };

                const expenseCategoryData = {
                    labels: payload.expenseCategoryBreakdown.map(item => item.category),
                    datasets: [{
                        data: payload.expenseCategoryBreakdown.map(item => item.total),
                        backgroundColor: ['#1d4ed8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9']
                    }]
                };

                incomeCategoryChartInstance = new Chart(incomeCategoryCtx, { type: 'pie', data: incomeCategoryData });
                expenseCategoryChartInstance = new Chart(expenseCategoryCtx, { type: 'pie', data: expenseCategoryData });
            }

            document.addEventListener('DOMContentLoaded', () => renderCharts({
                monthlyTrend: @json($monthlyTrend),
                incomeCategoryBreakdown: @json($incomeCategoryBreakdown->toArray()),
                expenseCategoryBreakdown: @json($expenseCategoryBreakdown->toArray()),
            }));

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('dashboard-charts-updated', (monthlyTrend, incomeCategoryBreakdown, expenseCategoryBreakdown) => {
                    renderCharts({ monthlyTrend, incomeCategoryBreakdown, expenseCategoryBreakdown });
                });
            });
        </script>
    @endunless
</div>
