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
    </script>
</div>
