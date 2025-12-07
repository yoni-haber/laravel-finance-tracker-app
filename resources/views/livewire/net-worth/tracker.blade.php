<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">Net Worth</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Track your assets and liabilities over time.</p>
            </div>
            @if (session()->has('status'))
                <p class="text-sm text-emerald-600">{{ session('status') }}</p>
            @endif
        </div>

        <form wire:submit.prevent="save" class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Date</label>
                <input
                    type="date"
                    wire:model="date"
                    class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                />
                @error('date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Assets (£)</label>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    wire:model="assets"
                    class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                />
                @error('assets') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Liabilities (£)</label>
                <input
                    type="number"
                    min="0"
                    step="0.01"
                    wire:model="liabilities"
                    class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                />
                @error('liabilities') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="flex flex-col justify-end space-y-2">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Calculated Net Worth</p>
                <div class="rounded-lg bg-zinc-100 px-3 py-2 text-lg font-semibold text-emerald-700 dark:bg-zinc-800 dark:text-emerald-400">
                    £{{ $this->calculatedNetWorth }}
                </div>
            </div>
            <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save Entry</button>
                @if ($entryId)
                    <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancel</button>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold">History</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">View your recorded net worth entries.</p>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">Date</th>
                        <th class="px-3 py-2 text-left font-semibold">Assets</th>
                        <th class="px-3 py-2 text-left font-semibold">Liabilities</th>
                        <th class="px-3 py-2 text-left font-semibold">Net Worth</th>
                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="px-3 py-2">{{ $entry->date->format('M d, Y') }}</td>
                            <td class="px-3 py-2">£{{ number_format($entry->assets, 2) }}</td>
                            <td class="px-3 py-2">£{{ number_format($entry->liabilities, 2) }}</td>
                            <td class="px-3 py-2 font-semibold text-emerald-700 dark:text-emerald-400">£{{ number_format($entry->net_worth, 2) }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $entry->id }})" class="text-sm text-blue-600">Edit</button>
                                <button type="button" wire:click="delete({{ $entry->id }})" class="text-sm text-rose-600">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500">No entries yet. Start by adding your assets and liabilities.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
