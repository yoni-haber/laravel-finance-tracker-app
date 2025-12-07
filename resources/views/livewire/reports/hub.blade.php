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
        <div class="flex flex-col gap-2">
            <div class="flex flex-col gap-1">
                <h3 class="text-lg font-semibold">Net Worth Tracker</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Log assets and liabilities to see how your net worth changes over time.</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-2 lg:items-start">
                <form wire:submit.prevent="saveNetWorth" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Assets (£)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="assets"
                                class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                            />
                            @error('assets') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Liabilities (£)</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                wire:model="liabilities"
                                class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                            />
                            @error('liabilities') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Entry Date</label>
                            <input
                                type="date"
                                wire:model="netWorthDate"
                                class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                            />
                            @error('netWorthDate') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="rounded-lg border border-indigo-100 bg-indigo-50 p-4 text-indigo-900 dark:border-indigo-500/50 dark:bg-indigo-500/10 dark:text-indigo-100">
                            <p class="text-xs font-semibold uppercase tracking-wide">Net Worth</p>
                            <p class="mt-1 text-2xl font-bold">£{{ number_format($this->netWorthTotal, 2) }}</p>
                            <p class="mt-1 text-xs text-indigo-700 dark:text-indigo-200">Assets minus liabilities</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Save entry</button>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Entries with the same date will be updated automatically.</p>
                        @if (session()->has('netWorthSaved'))
                            <p class="text-sm text-emerald-600">{{ session('netWorthSaved') }}</p>
                        @endif
                    </div>
                </form>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold">Net worth over time</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Track how your position changes month to month.</p>
                        </div>
                    </div>
                    <canvas
                        id="netWorthTrendChart"
                        wire:ignore
                        data-chart-data='@json($netWorthChart)'
                        class="mt-2"
                    ></canvas>
                </div>
            </div>
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
        })();

        (() => {
            if (window.netWorthChartInitialized) return;
            window.netWorthChartInitialized = true;

            let netWorthChartInstance;

            const renderNetWorthChart = (chartData) => {
                const chartElement = document.getElementById('netWorthTrendChart');
                if (!chartElement) return;

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
                                data: chartData.values,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99, 102, 241, 0.15)',
                                tension: 0.35,
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
                const chartElement = document.getElementById('netWorthTrendChart');
                if (!chartElement) return;

                const data = chartElement.dataset.chartData;
                if (!data) return;

                try {
                    renderNetWorthChart(JSON.parse(data));
                } catch (error) {
                    console.error('Unable to parse net worth chart data', error);
                }
            };

            document.addEventListener('DOMContentLoaded', hydrateFromElement);
            document.addEventListener('livewire:navigated', hydrateFromElement);

            document.addEventListener('livewire:init', () => {
                Livewire.on('net-worth-chart-data', (payload) => {
                    const chartData = payload.chartData ?? payload;
                    renderNetWorthChart(chartData);
                });
            });
        })();
    </script>
</div>
