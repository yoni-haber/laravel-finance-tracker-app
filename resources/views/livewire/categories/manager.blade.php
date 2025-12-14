<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold mb-4">Add / Edit Category</h3>
        <form wire:submit.prevent="save" class="space-y-4">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-900 dark:text-white">Category Name</label>
                <input type="text" wire:model="name" placeholder="Category name" class="mt-2 w-full rounded-md border-gray-300 dark:bg-zinc-800 dark:border-zinc-700" />
                @error('name') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2 lg:col-span-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">Save</button>
                @if ($categoryId)
                    <button type="button" wire:click="resetForm" class="rounded-md border border-zinc-300 px-4 py-2 text-sm">Cancel</button>
                @endif
                @if (session()->has('status'))
                    <p class="text-sm text-emerald-600">{{ session('status') }}</p>
                @endif
            </div>
        </form>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h3 class="text-lg font-semibold">Categories</h3>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($categories as $category)
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-700">
                    <div>
                        <p class="font-medium">{{ $category->name }}</p>
                        <p class="text-xs text-gray-500">{{ $category->transactions()->count() }} transactions</p>
                    </div>
                    <div class="space-x-2 text-sm">
                        <button type="button" wire:click="edit({{ $category->id }})" class="text-blue-600">Edit</button>
                        <button type="button" wire:click="delete({{ $category->id }})" class="text-rose-600">Delete</button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No categories yet.</p>
            @endforelse
        </div>
    </div>
</div>
