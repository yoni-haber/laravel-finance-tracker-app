<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold">Income vs Expenditure</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Compare totals for your selected range.</p>
            </div>
            <label class="flex items-center gap-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                <select
                    aria-label="Chart range"
                    wire:model.live="range"
                    class="rounded-xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:border-indigo-500/60 dark:bg-gradient-to-b dark:from-zinc-800 dark:to-zinc-900 dark:text-gray-100 dark:shadow-[0_0_0_1px_rgba(99,102,241,0.4)]"
                >
                    @foreach ($rangeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <canvas
            id="incomeVsExpensesChart"
            wire:ignore
            data-chart-data='@json($chartData)'
            class="mt-6"
        ></canvas>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-2">
                <div>
                    <h3 class="text-lg font-semibold">Net Worth Tracker</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Record your assets and liabilities to see how your net worth changes over time.</p>
                </div>
                <form wire:submit.prevent="saveNetWorth" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Assets (£)</label>
                        <input type="number" min="0" step="0.01" wire:model="assets" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                        @error('assets') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Liabilities (£)</label>
                        <input type="number" min="0" step="0.01" wire:model="liabilities" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                        @error('liabilities') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Date</label>
                        <input type="date" wire:model="net_worth_date" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                        @error('net_worth_date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap items-center gap-3">
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            Current entry net worth: <span class="font-semibold">£{{ number_format(((float) $assets) - ((float) $liabilities), 2) }}</span>
                        </div>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">Save Net Worth</button>
                        @if (session()->has('status'))
                            <p class="text-sm text-emerald-600">{{ session('status') }}</p>
                        @endif
                    </div>
                </form>
            </div>

            @if ($netWorthTableMissing)
                <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-amber-900 shadow-sm lg:w-80">
                    <p class="font-semibold">Net worth tracking unavailable</p>
                    <p class="text-sm">Run <code>php artisan migrate</code> to create the net_worth_entries table.</p>
                </div>
            @else
                <div class="mt-4 w-full lg:mt-0 lg:max-w-xl">
                    <canvas id="netWorthChart" wire:ignore data-chart-data='@json($netWorthChartData)' class="w-full"></canvas>
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            if (window.incomeVsExpensesChartInitialized) return;
            window.incomeVsExpensesChartInitialized = true;

            let chartInstance;

            const renderIncomeVsExpensesChart = (chartData) => {
                const chartElement = document.getElementById('incomeVsExpensesChart');
                if (!chartElement) return;

                if (chartInstance) {
                    chartInstance.destroy();
                }

                chartInstance = new Chart(chartElement, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Income',
                                data: chartData.income,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                tension: 0.3,
                                fill: true,
                            },
                            {
                                label: 'Expenses',
                                data: chartData.expenses,
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239, 68, 68, 0.2)',
                                tension: 0.3,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            intersect: false,
                            mode: 'index',
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => `£${value}`,
                                },
                            },
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    usePointStyle: true,
                                },
                            },
                        },
                    },
                });
            };

            const hydrateFromElement = () => {
                const chartElement = document.getElementById('incomeVsExpensesChart');
                if (!chartElement) return;

                const data = chartElement.dataset.chartData;
                if (!data) return;

                try {
                    renderIncomeVsExpensesChart(JSON.parse(data));
                } catch (error) {
                    console.error('Unable to parse chart data', error);
                }
            };

            document.addEventListener('DOMContentLoaded', hydrateFromElement);
            document.addEventListener('livewire:navigated', hydrateFromElement);

            document.addEventListener('livewire:init', () => {
                Livewire.on('reports-chart-data', (payload) => {
                    const chartData = payload.chartData ?? payload;
                    renderIncomeVsExpensesChart(chartData);
                });
            });

            let netWorthChartInstance;

            const renderNetWorthChart = (chartData) => {
                const chartElement = document.getElementById('netWorthChart');
                if (!chartElement || !chartData) return;

                if (netWorthChartInstance) {
                    netWorthChartInstance.destroy();
                }

                netWorthChartInstance = new Chart(chartElement, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [
                            {
                                label: 'Net Worth',
                                data: chartData.netWorth,
                                borderColor: '#2563eb',
                                backgroundColor: 'rgba(37, 99, 235, 0.15)',
                                tension: 0.35,
                                fill: true,
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (value) => `£${value}`,
                                },
                            },
                        },
                        plugins: {
                            legend: {
                                labels: {
                                    usePointStyle: true,
                                },
                            },
                        },
                    },
                });
            };

            const hydrateNetWorthChart = () => {
                const chartElement = document.getElementById('netWorthChart');
                if (!chartElement) return;

                const data = chartElement.dataset.chartData;
                if (!data) return;

                try {
                    renderNetWorthChart(JSON.parse(data));
                } catch (error) {
                    console.error('Unable to parse net worth chart data', error);
                }
            };

            document.addEventListener('DOMContentLoaded', hydrateNetWorthChart);
            document.addEventListener('livewire:navigated', hydrateNetWorthChart);

            document.addEventListener('livewire:init', () => {
                Livewire.on('net-worth-chart-data', (payload) => {
                    const chartData = payload.chartData ?? payload;
                    renderNetWorthChart(chartData);
                });
            });
        })();
    </script>
</div>
