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

    @error('delete')
    <div class="rounded-md bg-red-50 border border-red-200 p-4">
        <div class="flex">
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">{{ $message }}</p>
            </div>
        </div>
    </div>
    @enderror

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bank Profiles</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                Configure CSV parsing formats for different banks
            </p>
        </div>
        <div class="flex gap-3">
            <a
                href="{{ route('statements.import') }}"
                class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
            >
                Back to Import
            </a>
            <button
                wire:click="showCreate"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                Create Bank Profile
            </button>
        </div>
    </div>

    @if ($showCreateForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold mb-4">
                {{ $editingProfile ? 'Edit Bank Profile' : 'Create Bank Profile' }}
            </h3>

            <form wire:submit="save" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                        Profile Name
                    </label>
                    <input
                        type="text"
                        wire:model="form.name"
                        placeholder="e.g Halifax, American Express"
                        class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                    >
                    @error('form.name')
                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        A descriptive name for this CSV format
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                        Statement Type
                    </label>
                    <select wire:model="form.statement_type" class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="bank">Bank Statement</option>
                        <option value="credit_card">Credit Card Statement</option>
                    </select>
                    @error('form.statement_type')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        Bank: Positive = Income, Negative = Expense<br>
                        Credit Card: Positive = Expense, Negative = Income
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                            Date Column
                        </label>
                        <input
                            type="number"
                            wire:model="form.date_column"
                            min="1"
                            class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                        >
                        @error('form.date_column')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Which column contains the transaction date? (starting from 1)
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                            Description Column
                        </label>
                        <input
                            type="number"
                            wire:model="form.description_column"
                            min="1"
                            class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                        >
                        @error('form.description_column')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-500 mt-1">
                            Which column contains the transaction description?
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                        Date Format
                    </label>
                    <select
                        wire:model="form.date_format"
                        class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                    >
                        <option value="d/m/Y">DD/MM/YYYY (e.g 31/12/2025)</option>
                        <option value="Y-m-d">YYYY-MM-DD (e.g 2025-12-31)</option>
                        <option value="m/d/Y">MM/DD/YYYY (e.g 12/31/2025)</option>
                        <option value="d-m-Y">DD-MM-YYYY (e.g 31-12-2025)</option>
                    </select>
                    @error('form.date_format')
                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        Format of dates in the CSV file
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        wire:model="form.has_header"
                        id="has-header"
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-zinc-800 dark:border-zinc-600"
                    >
                    <label for="has-header" class="text-sm text-gray-900 dark:text-white">
                        CSV has a header row
                    </label>
                    <p class="text-xs text-gray-500">
                        Uncheck if the first row of your CSV is a data row (no column names)
                    </p>
                </div>

                <div class="pt-4">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <input
                                type="checkbox"
                                wire:model.live="hasSeparateColumns"
                                id="separate-columns"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500 dark:bg-zinc-800 dark:border-zinc-600"
                            >
                            <label for="separate-columns" class="text-sm text-gray-900 dark:text-white">
                                CSV has separate debit and credit columns
                            </label>
                        </div>

                        <p class="text-xs text-gray-500">
                            Check this if your bank statement has separate columns for money in and money out,
                            instead of a single amount column with +/- values.
                        </p>

                        @if (!$hasSeparateColumns)
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                                    Amount Column
                                </label>
                                <input
                                    type="number"
                                    wire:model="form.amount_column"
                                    min="1"
                                    class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                                >
                                @error('form.amount_column')
                                <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1">
                                    Which column contains the transaction amount?
                                </p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                                        Debit Column
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="form.debit_column"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                    @error('form.debit_column')
                                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">
                                        Which column shows expenses?
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                                        Credit Column
                                    </label>
                                    <input
                                        type="number"
                                        wire:model="form.credit_column"
                                        min="1"
                                        class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                                    >
                                    @error('form.credit_column')
                                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-gray-500 mt-1">
                                        Which column shows income?
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        {{ $editingProfile ? 'Update Profile' : 'Create Profile' }}
                    </button>
                    <button
                        type="button"
                        wire:click="cancel"
                        class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($profiles->count() > 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold">Bank Profiles</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
                @foreach ($profiles as $profile)
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-medium">{{ $profile->name }} {{ $profile->statement_type === 'credit_card' ? '(Credit Card)' : '(Bank Statement)' }}</h4>
                            </div>

                            <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                <p>
                                    <span class="font-medium">Date:</span> Column {{ ($profile->config['columns']['date'] ?? 0) + 1 }} ({{ $profile->config['date_format'] }})
                                </p>
                                <p>
                                    <span class="font-medium">Description:</span> Column {{ ($profile->config['columns']['description'] ?? 1) + 1 }}
                                </p>
                                @if (isset($profile->config['columns']['amount']))
                                    <p>
                                        <span class="font-medium">Amount:</span> Column {{ $profile->config['columns']['amount'] + 1 }} (signed +/-)
                                    </p>
                                @else
                                    <p>
                                        <span class="font-medium">Debit/Credit:</span> Columns {{ ($profile->config['columns']['debit'] ?? 0) + 1 }}/{{ ($profile->config['columns']['credit'] ?? 0) + 1 }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <div class="space-x-2 text-sm">
                            <button
                                wire:click="edit({{ $profile->id }})"
                                class="text-blue-600 hover:text-blue-500"
                            >
                                Edit
                            </button>
                            <flux:modal.trigger name="confirm-delete-profile-{{ $profile->id }}">
                                <button class="text-red-600 hover:text-red-500">
                                    Delete
                                </button>
                            </flux:modal.trigger>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        @if (!$showCreateForm)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-700 dark:bg-amber-900/20">
                <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">
                    No Bank Profiles Found
                </h3>
                <p class="text-amber-700 dark:text-amber-300 mb-4">
                    Create your first bank profile to start importing statements.
                </p>
                <button
                    wire:click="showCreate"
                    class="bg-amber-600 text-white px-4 py-2 rounded-md hover:bg-amber-700 focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                >
                    Create Your First Profile
                </button>
            </div>
        @endif
    @endif

    {{-- Delete Bank Profile Modals --}}
    @foreach ($profiles as $profile)
        <flux:modal name="confirm-delete-profile-{{ $profile->id }}" focusable class="max-w-lg">
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Delete Bank Profile</flux:heading>
                    <flux:subheading>
                        Are you sure you want to delete the bank profile <strong>"{{ $profile->name }}"</strong>?
                        <br><br>
                        This action cannot be undone.
                    </flux:subheading>
                </div>

                @error('delete')
                    <div class="rounded-md bg-red-50 border border-red-200 p-4">
                        <p class="text-sm text-red-800">{{ $message }}</p>
                    </div>
                @enderror

                <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                    <flux:modal.close>
                        <flux:button variant="filled">Cancel</flux:button>
                    </flux:modal.close>

                    <flux:modal.close>
                        <flux:button variant="danger" wire:click="delete({{ $profile->id }})">
                            Delete Profile
                        </flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    @endforeach
</div>
