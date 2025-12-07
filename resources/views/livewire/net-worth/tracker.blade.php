<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold">Record Net Worth</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Capture assets and liabilities for a specific date.</p>
            </div>
            @if ($entryId)
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">Editing entry</span>
            @endif
        </div>

        <form wire:submit.prevent="save" class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Date</label>
                <input
                    type="date"
                    wire:model="date"
                    class="mt-2 w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800"
                />
                @error('date')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Total Assets (£)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model="assets"
                    class="mt-2 w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800"
                />
                @error('assets')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Total Liabilities (£)</label>
                <input
                    type="number"
                    step="0.01"
                    min="0"
                    wire:model="liabilities"
                    class="mt-2 w-full rounded-md border-gray-300 dark:border-zinc-700 dark:bg-zinc-800"
                />
                @error('liabilities')
                    <p class="text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex flex-col justify-end gap-2">
                <div class="rounded-lg border border-dashed border-zinc-300 bg-zinc-50 px-4 py-3 text-sm font-semibold dark:border-zinc-700 dark:bg-zinc-800">
                    Current Net Worth
                    <div class="text-lg text-emerald-600 dark:text-emerald-400">£{{ number_format((float) $assets - (float) $liabilities, 2) }}</div>
                </div>
            </div>
            <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">{{ $entryId ? 'Update Entry' : 'Save Entry' }}</button>
                @if ($entryId)
                    <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-600">Cancel</button>
                @endif
                @if (session()->has('status'))
                    <p class="text-sm text-emerald-600 dark:text-emerald-400">{{ session('status') }}</p>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold">Net Worth History</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Review saved snapshots and make updates.</p>
            </div>
        </div>

        <div class="mt-4 overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
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
                            <td class="px-3 py-2">{{ $entry->date->format('j M, Y') }}</td>
                            <td class="px-3 py-2">£{{ number_format($entry->assets, 2) }}</td>
                            <td class="px-3 py-2">£{{ number_format($entry->liabilities, 2) }}</td>
                            <td class="px-3 py-2 font-semibold {{ $entry->net_worth >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">£{{ number_format($entry->net_worth, 2) }}</td>
                            <td class="px-3 py-2 text-right space-x-2">
                                <button type="button" wire:click="edit({{ $entry->id }})" class="text-sm text-blue-600 hover:underline">Edit</button>
                                <button type="button" wire:click="delete({{ $entry->id }})" class="text-sm text-rose-600 hover:underline">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No net worth entries yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
