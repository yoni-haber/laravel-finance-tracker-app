<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold mb-4">Add / Edit Transaction</h3>
        <form wire:submit.prevent="save" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Type</label>
                <select wire:model="type" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
                @error('type') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Amount (£)</label>
                <input type="number" min="0" step="0.01" wire:model="amount" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                @error('amount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Date</label>
                <input type="date" wire:model="date" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                @error('date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Category</label>
                <select wire:model="category_id" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">Uncategorised</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Description</label>
                <textarea wire:model="description" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" rows="2"></textarea>
                @error('description') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Recurring</span>
                    <input type="checkbox" wire:model.live="is_recurring" class="rounded border-gray-300 text-emerald-600 shadow-sm focus:ring-emerald-500" />
                </label>
                <select wire:model.live="frequency" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700 text-sm" @disabled(! $is_recurring)>
                    <option value="">Select frequency</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>
            <div class="md:col-span-2 lg:col-span-3 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
                @if ($transactionId)
                    <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancel</button>
                @endif
                @if (session()->has('status'))
                    <p class="text-sm text-emerald-600">{{ session('status') }}</p>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap gap-4 items-center justify-between">
            <h3 class="text-lg font-semibold">Transactions</h3>
            <div class="flex gap-2 text-sm">
                <select wire:model.live="month" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ now()->startOfYear()->month($m)->shortMonthName }}</option>
                    @endforeach
                </select>
                <input type="number" wire:model.live="year" class="w-24 rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" min="2000" max="2100" />
                <select wire:model.live="filterCategory" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterType" class="rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                    <option value="">Income & expense</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Date</th>
                        <th class="px-3 py-2 text-left font-semibold">Category</th>
                        <th class="px-3 py-2 text-left font-semibold">Type</th>
                        <th class="px-3 py-2 text-left font-semibold">Amount</th>
                        <th class="px-3 py-2 text-left font-semibold">Notes</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($transactions as $transaction)
                        <tr class="{{ $transaction->is_recurring ? 'bg-emerald-50/50 dark:bg-emerald-900/20' : '' }}">
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($transaction->date)->format('j M Y') }}</td>
                            <td class="px-3 py-2">{{ $transaction->category->name ?? 'Uncategorised' }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded-full px-2 py-1 text-xs {{ $transaction->type === 'income' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">{{ ucfirst($transaction->type) }}</span>
                                @if ($transaction->is_recurring)
                                    <span class="ml-2 text-xs text-emerald-600">Recurring ({{ ucfirst($transaction->frequency) }})</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 font-semibold {{ $transaction->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">£{{ number_format($transaction->amount, 2) }}</td>
                            <td class="px-3 py-2">{{ $transaction->description }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $transaction->id }})" class="text-sm text-blue-600">Edit</button>
                                <button
                                    type="button"
                                    wire:click="delete({{ $transaction->id }}, '{{ \Carbon\Carbon::parse($transaction->date)->toDateString() }}')"
                                    class="text-sm text-rose-600"
                                >
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-sm text-gray-500">No transactions found for this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
