<div class="space-y-6"
     @if($polling)
         wire:poll.2s="checkImportStatus"
     @endif>
    @if (session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 p-4">
            <div class="flex">
                <div class="ml-3">
                    <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if ($currentImport)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold mb-4">Current Import</h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium">{{ $currentImport->original_filename }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Uploaded {{ $currentImport->created_at->diffForHumans() }}
                        </p>
                        @if ($currentImport->bankProfile)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Bank Profile: {{ $currentImport->bankProfile->name }}
                            </p>
                        @endif
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Type: {{ $currentImport->statement_type === 'credit_card' ? 'Credit Card Statement' : 'Bank Statement' }}
                        </p>
                    </div>
                    <div class="text-right">
                        @if ($currentImport->isUploaded())
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800">
                                Uploaded
                            </span>
                        @elseif ($currentImport->isParsing())
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800">
                                Processing...
                            </span>
                        @elseif ($currentImport->isParsed())
                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                Ready for Review
                            </span>
                        @elseif ($currentImport->isFailed())
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                Failed
                            </span>
                        @endif
                    </div>
                </div>

                <div class="flex gap-3">
                    @if ($currentImport->isParsed())
                        <button
                            wire:key="review-button-{{ $currentImport->id }}"
                            wire:click="proceedToReview"
                            class="bg-emerald-600 text-white px-4 py-2 rounded-md hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
                        >
                            Review Transactions
                        </button>
                    @endif

                    @if (!$currentImport->isCommitted())
                        <flux:modal.trigger name="confirm-delete-import">
                            <button
                                wire:key="delete-button-{{ $currentImport->id }}"
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                            >
                                Delete Import
                            </button>
                        </flux:modal.trigger>
                    @endif

                </div>

                @if ($currentImport->isParsing())
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <p>Processing your CSV file... This may take a few minutes.</p>
                        <p class="mt-1">You can safely leave this page - we'll notify you when it's ready.</p>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <h3 class="text-lg font-semibold mb-4">Upload Bank Statement</h3>

            <form wire:submit="uploadStatement" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                        Bank Profile
                    </label>
                    <select wire:model="bankProfileId" class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700">
                        <option value="">Select a bank profile...</option>
                        @foreach ($bankProfiles as $profile)
                            <option value="{{ $profile->id }}">
                                {{ $profile->name }}
                                ({{ $profile->statement_type === 'credit_card' ? 'Credit Card' : 'Bank Statement' }})
                            </option>
                        @endforeach
                    </select>
                    @error('bankProfileId')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">
                        CSV format and statement type are configured in the bank profile -
                        <a href="{{ route('statements.bank-profiles') }}" class="text-blue-600 hover:text-blue-500 underline">Manage Bank Profiles</a>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-2">
                        CSV File
                    </label>
                    <input
                        type="file"
                        wire:model="csvFile"
                        accept=".csv,.txt"
                        class="w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700"
                    >
                    @error('csvFile')
                        <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-gray-500 mt-1">Maximum file size: 2MB. Supported formats: CSV, TXT</p>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button
                        type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        wire:loading.attr="disabled"
                    >
                        <span wire:loading.remove wire:target="uploadStatement">Upload Statement</span>
                        <span wire:loading wire:target="uploadStatement">Uploading...</span>
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($bankProfiles->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-700 dark:bg-amber-900/20">
            <h3 class="text-lg font-semibold text-amber-800 dark:text-amber-200 mb-2">No Bank Profiles Found</h3>
            <p class="text-amber-700 dark:text-amber-300 mb-4">
                You need to create at least one bank profile before importing statements.
            </p>
            <a href="{{ route('statements.bank-profiles') }}" class="text-amber-600 hover:text-amber-500 underline">Create Bank Profile</a>
        </div>
    @endif

    {{-- Delete Import Confirmation Modal --}}
    <flux:modal name="confirm-delete-import" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Import</flux:heading>
                <flux:subheading>
                    Are you sure you want to delete this import? This will permanently remove 
                    the uploaded file and any processed transaction data. This action cannot be undone.
                </flux:subheading>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>

                <flux:modal.close>
                    <flux:button variant="danger" wire:click="cancelImport">
                        Delete Import
                    </flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
