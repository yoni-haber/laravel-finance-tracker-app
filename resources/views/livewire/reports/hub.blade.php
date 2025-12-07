<div class="space-y-6" x-data="{ month: @entangle('month'), year: @entangle('year'), categoryId: @entangle('categoryId') }">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Month</label>
            <select x-model="month" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}">{{ now()->startOfYear()->month($m)->format('F') }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Year</label>
            <input type="number" x-model="year" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm" min="2000" max="2100">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-600 dark:text-gray-300">Category</label>
            <select x-model="categoryId" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm">
                <option value="">All</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Monthly Income</p>
            <p class="text-2xl font-semibold text-emerald-600">£{{ number_format($monthly['income'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Monthly Expenses</p>
            <p class="text-2xl font-semibold text-rose-600">£{{ number_format($monthly['expenses'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Monthly Net</p>
            <p class="text-2xl font-semibold {{ $monthly['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">£{{ number_format($monthly['net'], 2) }}</p>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Yearly Income</p>
            <p class="text-2xl font-semibold text-emerald-600">£{{ number_format($yearly['income'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Yearly Expenses</p>
            <p class="text-2xl font-semibold text-rose-600">£{{ number_format($yearly['expenses'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <p class="text-sm text-gray-500">Yearly Net</p>
            <p class="text-2xl font-semibold {{ $yearly['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">£{{ number_format($yearly['net'], 2) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold">Budget vs Actual</h3>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($budgetComparison as $comparison)
                <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <p class="text-sm font-medium">{{ $comparison['category'] }}</p>
                    <p class="text-xs text-gray-500">Budget £{{ number_format($comparison['budget'], 2) }}</p>
                    <p class="text-xs text-gray-500">Actual £{{ number_format($comparison['actual'], 2) }}</p>
                    <p class="mt-2 text-sm {{ $comparison['overspent'] ? 'text-rose-600' : 'text-emerald-600' }}">{{ $comparison['overspent'] ? 'Overspent' : 'Remaining' }} £{{ number_format($comparison['remaining'], 2) }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500">No budgets defined for this period.</p>
            @endforelse
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold">Monthly performance</h3>
            <canvas id="reportBarChart" wire:ignore class="mt-4"></canvas>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold">Category breakdown</h3>
            <canvas id="reportPieChart" wire:ignore class="mt-4"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:navigated', renderReports);
        document.addEventListener('DOMContentLoaded', renderReports);

        function renderReports() {
            const bar = document.getElementById('reportBarChart');
            const pie = document.getElementById('reportPieChart');
            if (!bar || !pie) return;

            new Chart(bar, {
                type: 'bar',
                data: {
                    labels: @json($barChart['labels']),
                    datasets: [
                        { label: 'Income', backgroundColor: '#10b981', data: @json($barChart['income']) },
                        { label: 'Expenses', backgroundColor: '#ef4444', data: @json($barChart['expenses']) }
                    ]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true } }
                }
            });

            new Chart(pie, {
                type: 'pie',
                data: {
                    labels: @json(collect($categoryBreakdown)->pluck('category')),
                    datasets: [{
                        data: @json(collect($categoryBreakdown)->pluck('expenses')),
                        backgroundColor: ['#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6366f1']
                    }]
                }
            });
        }
    </script>
</div>
