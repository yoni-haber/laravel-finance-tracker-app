<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold">Income vs Expenditure (Last 12 Months)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Shows monthly totals for the past year.</p>
        <canvas id="incomeVsExpensesChart" wire:ignore class="mt-6"></canvas>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', renderIncomeVsExpensesChart);
        document.addEventListener('DOMContentLoaded', renderIncomeVsExpensesChart);

        function renderIncomeVsExpensesChart() {
            const chartElement = document.getElementById('incomeVsExpensesChart');
            if (!chartElement) return;

            if (window.incomeVsExpensesChartInstance) {
                window.incomeVsExpensesChartInstance.destroy();
            }

            window.incomeVsExpensesChartInstance = new Chart(chartElement, {
                type: 'line',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [
                        {
                            label: 'Income',
                            data: @json($chartData['income']),
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.2)',
                            tension: 0.3,
                            fill: true,
                        },
                        {
                            label: 'Expenses',
                            data: @json($chartData['expenses']),
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
        }
    </script>
</div>
