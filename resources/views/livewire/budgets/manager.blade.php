<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold mb-4">Budget</h3>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Category</label>
                <select wire:model="category_id" class="mt-1 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">Select category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Month</label>
                <select wire:model="month" class="mt-1 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ now()->startOfYear()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
                @error('month') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Year</label>
                <input type="number" wire:model="year" min="2000" max="2100" class="mt-1 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                @error('year') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Amount (£)</label>
                <input type="number" min="0" step="0.01" wire:model="amount" class="mt-1 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                @error('amount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
                @if ($budgetId)
                    <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancel</button>
                @endif
                @if (session()->has('status'))
                    <p class="text-sm text-emerald-600">{{ session('status') }}</p>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h3 class="text-lg font-semibold">Defined budgets</h3>
            <div class="flex gap-2 text-sm">
                <select wire:model.live="filterMonth" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ now()->startOfYear()->month($m)->shortMonthName }}</option>
                    @endforeach
                </select>
                <input type="number" wire:model.live="filterYear" class="w-24 rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" min="2000" max="2100" />
                <select wire:model.live="filterCategory" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Category</th>
                        <th class="px-3 py-2 text-left font-semibold">Month</th>
                        <th class="px-3 py-2 text-left font-semibold">Year</th>
                        <th class="px-3 py-2 text-left font-semibold">Amount</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($budgets as $budget)
                        <tr>
                            <td class="px-3 py-2">{{ $budget->category->name }}</td>
                            <td class="px-3 py-2">{{ now()->startOfYear()->month($budget->month)->format('F') }}</td>
                            <td class="px-3 py-2">{{ $budget->year }}</td>
                            <td class="px-3 py-2">£{{ number_format($budget->amount, 2) }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $budget->id }})" class="text-sm text-blue-600">Edit</button>
                                <button type="button" wire:click="delete({{ $budget->id }})" class="text-sm text-rose-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No budgets defined.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
