<?php

declare(strict_types=1);

namespace App\Livewire\NetWorth;

use App\Models\Asset;
use App\Models\AssetGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Assets')]
class AssetManager extends Component
{
    #[Rule('required|string|min:2')]
    public string $groupName = '';

    public ?int $groupDisplayOrder = null;
    public ?int $groupId = null;

    #[Rule('required|exists:asset_groups,id')]
    public ?int $asset_group_id = null;

    #[Rule('required|string|min:2')]
    public string $assetName = '';

    public ?string $assetNotes = null;
    public ?int $assetId = null;

    public function saveGroup(): void
    {
        $this->validateOnly('groupName');

        AssetGroup::updateOrCreate([
            'id' => $this->groupId,
        ], [
            'user_id' => Auth::id(),
            'name' => $this->groupName,
            'display_order' => $this->groupDisplayOrder,
        ]);

        $this->reset(['groupName', 'groupDisplayOrder', 'groupId']);
        session()->flash('status', 'Asset group saved.');
    }

    public function editGroup(int $groupId): void
    {
        $group = AssetGroup::where('user_id', Auth::id())->findOrFail($groupId);
        $this->groupId = $group->id;
        $this->groupName = $group->name;
        $this->groupDisplayOrder = $group->display_order;
    }

    public function deleteGroup(int $groupId): void
    {
        AssetGroup::where('user_id', Auth::id())->where('id', $groupId)->delete();
        session()->flash('status', 'Asset group removed.');
    }

    public function saveAsset(): void
    {
        $this->validate();

        Asset::updateOrCreate([
            'id' => $this->assetId,
        ], [
            'user_id' => Auth::id(),
            'asset_group_id' => $this->asset_group_id,
            'name' => $this->assetName,
            'notes' => $this->assetNotes,
        ]);

        $this->reset(['asset_group_id', 'assetName', 'assetNotes', 'assetId']);
        session()->flash('status', 'Asset saved.');
    }

    public function editAsset(int $assetId): void
    {
        $asset = Asset::where('user_id', Auth::id())->findOrFail($assetId);
        $this->assetId = $asset->id;
        $this->asset_group_id = $asset->asset_group_id;
        $this->assetName = $asset->name;
        $this->assetNotes = $asset->notes;
    }

    public function deleteAsset(int $assetId): void
    {
        Asset::where('user_id', Auth::id())->where('id', $assetId)->delete();
        session()->flash('status', 'Asset removed.');
    }

    public function render(): View
    {
        $groups = AssetGroup::with('assets')
            ->where('user_id', Auth::id())
            ->orderByRaw('COALESCE(display_order, 9999)')
            ->orderBy('name')
            ->get();

        return view('livewire.net-worth.asset-manager', [
            'groups' => $groups,
        ]);
    }
}
