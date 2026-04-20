<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Import Summary -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold">Import Summary</h3>
            <button 
                wire:click="backToImport"
                class="text-gray-600 hover:text-gray-800 text-sm underline"
            >
                ← Back to Import
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $summary['total'] }}</div>
                <div class="text-sm text-gray-600">Total Transactions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $summary['new_transactions'] }}</div>
                <div class="text-sm text-gray-600">New Transactions</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $summary['duplicates'] }}</div>
                <div class="text-sm text-gray-600">Duplicates (Skipped)</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold {{ $summary['total_amount'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                    £{{ number_format(abs($summary['total_amount']), 2) }}
                </div>
                <div class="text-sm text-gray-600">
                    {{ $summary['total_amount'] >= 0 ? 'Net Credit' : 'Net Debit' }}
                </div>
            </div>
        </div>

        <div class="border-t pt-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">{{ $import->original_filename }}</p>
                    <p class="text-sm text-gray-600">
                        Bank Profile: {{ $import->bankProfile->name ?? 'Unknown' }}
                    </p>
                    <p class="text-sm text-gray-600">
                        Type: {{ $import->statement_type === 'credit_card' ? 'Credit Card Statement' : 'Bank Statement' }}
                    </p>
                </div>
                
                @if ($summary['new_transactions'] > 0)
                    <div class="flex gap-3">
                        <flux:modal.trigger name="confirm-import-commit">
                            <button 
                                class="bg-emerald-600 text-white px-6 py-2 rounded-md hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                            >
                                Import {{ $summary['new_transactions'] }} Transactions
                            </button>
                        </flux:modal.trigger>
                    </div>
                @else
                    <div class="text-sm text-gray-600">
                        No new transactions to import.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Transaction List -->
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold">Transaction Details</h3>
            <p class="text-sm text-gray-600 mt-1">Review, edit, categorize, or remove transactions before importing.</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-zinc-900 dark:divide-gray-700">
                    @forelse ($transactions as $transaction)
                        <tr class="{{ $transaction->is_duplicate ? 'opacity-50 bg-gray-50 dark:bg-gray-800' : '' }}">
                            @if ($editingTransactionId === $transaction->id)
                                <!-- Edit Mode -->
                                <td class="px-6 py-4">
                                    <input 
                                        type="date" 
                                        wire:model="editForm.date" 
                                        class="w-full text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                    @error('editForm.date') 
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                    @enderror
                                </td>
                                <td class="px-6 py-4">
                                    <textarea 
                                        wire:model="editForm.description" 
                                        class="w-full text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700" 
                                        rows="2"
                                    ></textarea>
                                    @error('editForm.description') 
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                    @enderror
                                </td>
                                <td class="px-6 py-4">
                                    <select 
                                        wire:model="editForm.type" 
                                        class="w-full text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                        <option value="{{ \App\Models\Transaction::TYPE_EXPENSE }}">Expense</option>
                                        <option value="{{ \App\Models\Transaction::TYPE_INCOME }}">Income</option>
                                    </select>
                                    @error('editForm.type') 
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                    @enderror
                                </td>
                                <td class="px-6 py-4">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0.01"
                                        wire:model="editForm.amount" 
                                        class="w-full text-sm border-gray-300 rounded-md text-right dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                    @error('editForm.amount') 
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p> 
                                    @enderror
                                </td>
                                <td class="px-6 py-4">
                                    <select 
                                        wire:model="editForm.category_id" 
                                        class="w-full text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                        <option value="">Uncategorised</option>
                                        @foreach ($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                        Editing
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2 justify-center">
                                        <button 
                                            wire:click="updateTransaction" 
                                            class="text-emerald-600 hover:text-emerald-800 text-xs font-medium"
                                        >
                                            Save
                                        </button>
                                        <button 
                                            wire:click="cancelEdit" 
                                            class="text-gray-600 hover:text-gray-800 text-xs font-medium"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </td>
                            @else
                                <!-- View Mode -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $transaction->date->format('j M Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $transaction->description }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if (!$transaction->is_duplicate)
                                        <select 
                                            wire:change="updateType({{ $transaction->id }}, $event.target.value)"
                                            class="text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700"
                                        >
                                            @php
                                                // Determine correct type based on statement type and amount sign
                                                if ($import->statement_type === 'credit_card') {
                                                    // Credit Card: Negative = Expense (purchases), Positive = Income (payments/refunds) 
                                                    $currentType = $transaction->amount < 0 ? \App\Models\Transaction::TYPE_EXPENSE : \App\Models\Transaction::TYPE_INCOME;
                                                } else {
                                                    // Bank: Positive = Income (deposits), Negative = Expense (withdrawals)
                                                    $currentType = $transaction->amount >= 0 ? \App\Models\Transaction::TYPE_INCOME : \App\Models\Transaction::TYPE_EXPENSE;
                                                }
                                            @endphp
                                            <option value="{{ \App\Models\Transaction::TYPE_EXPENSE }}" {{ $currentType === \App\Models\Transaction::TYPE_EXPENSE ? 'selected' : '' }}>Expense</option>
                                            <option value="{{ \App\Models\Transaction::TYPE_INCOME }}" {{ $currentType === \App\Models\Transaction::TYPE_INCOME ? 'selected' : '' }}>Income</option>
                                        </select>
                                    @else
                                        <span class="text-gray-400 text-sm">
                                            @php
                                                if ($import->statement_type === 'credit_card') {
                                                    $displayType = $transaction->amount < 0 ? 'Expense' : 'Income';
                                                } else {
                                                    $displayType = $transaction->amount >= 0 ? 'Income' : 'Expense';
                                                }
                                            @endphp
                                            {{ $displayType }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right">
                                    £{{ number_format(abs($transaction->amount), 2) }}
                                </td>
                                </td>
                                <td class="px-6 py-4">
                                    @if (!$transaction->is_duplicate)
                                        <select 
                                            wire:change="updateCategory({{ $transaction->id }}, $event.target.value)"
                                            class="text-sm border-gray-300 rounded-md dark:bg-zinc-800 dark:border-zinc-700"
                                        >
                                            <option value="">Uncategorised</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected($transaction->category_id == $category->id)>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($transaction->is_duplicate)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                            Duplicate
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                            New
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if (!$transaction->is_duplicate)
                                        <div class="flex gap-2 justify-center">
                                            <button 
                                                wire:click="editTransaction({{ $transaction->id }})" 
                                                class="text-blue-600 hover:text-blue-800 text-xs font-medium"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                wire:click="confirmDeleteTransaction({{ $transaction->id }})"
                                                class="text-red-600 hover:text-red-800 text-xs font-medium"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">N/A</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No transactions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <flux:modal name="confirm-import-commit" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Confirm Import</flux:heading>
                <flux:subheading>
                    This will create <strong>{{ $summary['new_transactions'] }}</strong> new transactions in your account. 
                    Categories will be assigned as selected. This action cannot be undone.
                </flux:subheading>
            </div>
            
            @error('commit')
                <div class="rounded-md bg-red-50 border border-red-200 p-4">
                    <p class="text-sm text-red-800">{{ $message }}</p>
                </div>
            @enderror

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button 
                    variant="primary" 
                    wire:click="commitImport"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="commitImport">Confirm Import</span>
                    <span wire:loading wire:target="commitImport">Importing...</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Single shared Remove Transaction Modal --}}
    @php $deletingTransaction = $deletingTransactionId ? $transactions->find($deletingTransactionId) : null; @endphp
    <flux:modal
        name="confirm-remove-transaction"
        x-on:open-delete-modal.window="$flux.modal('confirm-remove-transaction').show()"
        x-on:close-delete-modal.window="$flux.modal('confirm-remove-transaction').close()"
        focusable
        class="max-w-lg"
    >
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Remove Transaction</flux:heading>
                <flux:subheading>
                    Are you sure you want to remove this transaction from the import?
                    @if ($deletingTransaction)
                        <br><br>
                        <strong>{{ $deletingTransaction->description }}</strong> - £{{ number_format(abs($deletingTransaction->amount), 2) }}
                        <br><br>
                    @endif
                    This will permanently remove the transaction from this import.
                </flux:subheading>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="deleteTransaction">
                    Remove Transaction
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>