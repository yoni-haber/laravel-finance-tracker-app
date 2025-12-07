<div class="space-y-6">
    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold">Asset Groups</h2>
        <div class="grid gap-3 sm:grid-cols-3">
            <flux:input label="Group name" wire:model.defer="groupName" />
            <flux:input label="Display order" type="number" wire:model.defer="groupDisplayOrder" />
            <div class="flex items-end">
                <flux:button wire:click="saveGroup">Save Group</flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold">Assets</h2>
        <div class="grid gap-3 md:grid-cols-4">
            <flux:select wire:model.defer="asset_group_id" label="Group">
                <option value="">Select group</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </flux:select>
            <flux:input label="Asset name" wire:model.defer="assetName" />
            <flux:input label="Notes" wire:model.defer="assetNotes" />
            <div class="flex items-end">
                <flux:button wire:click="saveAsset">Save Asset</flux:button>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($groups as $group)
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold">{{ $group->name }}</p>
                            <p class="text-sm text-zinc-500">Display order: {{ $group->display_order ?? '—' }}</p>
                        </div>
                        <div class="flex gap-2">
                            <flux:button size="sm" wire:click="editGroup({{ $group->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="deleteGroup({{ $group->id }})">Delete</flux:button>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-2">
                        @forelse ($group->assets as $asset)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                                <div>
                                    <p class="font-medium">{{ $asset->name }}</p>
                                    @if ($asset->notes)
                                        <p class="text-xs text-zinc-500">{{ $asset->notes }}</p>
                                    @endif
                                </div>
                                <div class="flex gap-2">
                                    <flux:button size="xs" wire:click="editAsset({{ $asset->id }})">Edit</flux:button>
                                    <flux:button size="xs" variant="danger" wire:click="deleteAsset({{ $asset->id }})">Delete</flux:button>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No assets yet.</p>
                        @endforelse
                    </div>
                </div>
            @empty
                <p class="p-4 text-sm text-zinc-500">Create an asset group to begin.</p>
            @endforelse
        </div>
    </div>
</div>
