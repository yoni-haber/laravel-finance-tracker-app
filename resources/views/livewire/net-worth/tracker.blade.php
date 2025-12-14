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

        <form wire:submit.prevent="save" class="mt-6 space-y-6">
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Date</label>
                    <input
                        type="date"
                        wire:model="date"
                        class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                    />
                    @error('date') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex flex-col justify-end space-y-2 md:col-span-2 lg:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Calculated Net Worth</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="rounded-lg bg-emerald-50 px-3 py-2 text-lg font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                            £{{ $this->calculatedNetWorth }}
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Assets £{{ number_format(array_sum(array_column($assetLines, 'amount')), 2) }} | Liabilities £{{ number_format(array_sum(array_column($liabilityLines, 'amount')), 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold">Assets</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Categorise your assets to track them over time.</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div class="grid gap-3 sm:grid-cols-12 sm:items-center">
                            <div class="sm:col-span-6">
                                <label class="sr-only">Asset Category</label>
                                <input
                                    type="text"
                                    placeholder="e.g. Cash, Investments"
                                    wire:model="newAssetCategory"
                                    class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                />
                                @error('newAssetCategory') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sr-only">Asset Amount</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    wire:model="newAssetAmount"
                                    class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                />
                                @error('newAssetAmount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2 sm:text-right">
                                <button type="button" wire:click="addAssetLine" class="w-full rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Add</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Category</th>
                                        <th class="px-3 py-2 text-left font-semibold">Amount</th>
                                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @forelse ($assetLines as $index => $asset)
                                        <tr>
                                            <td class="px-3 py-2 align-top">
                                                @if ($editingAssetIndex === $index)
                                                    <input
                                                        type="text"
                                                        wire:model="assetLines.{{ $index }}.category"
                                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                                    />
                                                    @error('assetLines.' . $index . '.category') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                                @else
                                                    <span class="text-gray-800 dark:text-gray-100">{{ $asset['category'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                @if ($editingAssetIndex === $index)
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        wire:model="assetLines.{{ $index }}.amount"
                                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                                    />
                                                    @error('assetLines.' . $index . '.amount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                                @else
                                                    <span class="font-medium">£{{ number_format((float) $asset['amount'], 2) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right space-x-2">
                                                @if ($editingAssetIndex === $index)
                                                    <button type="button" wire:click="saveAssetLine({{ $index }})" class="text-sm text-emerald-600">Save</button>
                                                @else
                                                    <button type="button" wire:click="editAssetLine({{ $index }})" class="text-sm text-blue-600">Edit</button>
                                                @endif
                                                <button type="button" wire:click="removeAssetLine({{ $index }})" class="text-sm text-rose-600">Delete</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-3 text-center text-gray-500">No assets added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="font-semibold">Liabilities</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Categorise liabilities so you can monitor repayments.</p>
                        </div>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div class="grid gap-3 sm:grid-cols-12 sm:items-center">
                            <div class="sm:col-span-6">
                                <label class="sr-only">Liability Category</label>
                                <input
                                    type="text"
                                    placeholder="e.g. Mortgage, Credit Card"
                                    wire:model="newLiabilityCategory"
                                    class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                />
                                @error('newLiabilityCategory') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-4">
                                <label class="sr-only">Liability Amount</label>
                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    wire:model="newLiabilityAmount"
                                    class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                />
                                @error('newLiabilityAmount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2 sm:text-right">
                                <button type="button" wire:click="addLiabilityLine" class="w-full rounded-md bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-700">Add</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold">Category</th>
                                        <th class="px-3 py-2 text-left font-semibold">Amount</th>
                                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @forelse ($liabilityLines as $index => $liability)
                                        <tr>
                                            <td class="px-3 py-2 align-top">
                                                @if ($editingLiabilityIndex === $index)
                                                    <input
                                                        type="text"
                                                        wire:model="liabilityLines.{{ $index }}.category"
                                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                                    />
                                                    @error('liabilityLines.' . $index . '.category') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                                @else
                                                    <span class="text-gray-800 dark:text-gray-100">{{ $liability['category'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 align-top">
                                                @if ($editingLiabilityIndex === $index)
                                                    <input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        wire:model="liabilityLines.{{ $index }}.amount"
                                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-900 dark:border-zinc-700"
                                                    />
                                                    @error('liabilityLines.' . $index . '.amount') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                                                @else
                                                    <span class="font-medium">£{{ number_format((float) $liability['amount'], 2) }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right space-x-2">
                                                @if ($editingLiabilityIndex === $index)
                                                    <button type="button" wire:click="saveLiabilityLine({{ $index }})" class="text-sm text-emerald-600">Save</button>
                                                @else
                                                    <button type="button" wire:click="editLiabilityLine({{ $index }})" class="text-sm text-blue-600">Edit</button>
                                                @endif
                                                <button type="button" wire:click="removeLiabilityLine({{ $index }})" class="text-sm text-rose-600">Delete</button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-3 py-3 text-center text-gray-500">No liabilities added yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
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
                            <td class="px-3 py-2">
                                <div class="space-y-1">
                                    @foreach ($entry->lineItems->where('type', 'asset') as $item)
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span class="text-gray-700 dark:text-gray-200">{{ $item->category }}</span>
                                            <span class="font-medium">£{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                    @endforeach
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Total £{{ number_format($entry->assets, 2) }}</p>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="space-y-1">
                                    @foreach ($entry->lineItems->where('type', 'liability') as $item)
                                        <div class="flex items-center justify-between gap-2 text-sm">
                                            <span class="text-gray-700 dark:text-gray-200">{{ $item->category }}</span>
                                            <span class="font-medium">£{{ number_format($item->amount, 2) }}</span>
                                        </div>
                                    @endforeach
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Total £{{ number_format($entry->liabilities, 2) }}</p>
                                </div>
                            </td>
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
