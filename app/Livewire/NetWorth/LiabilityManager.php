<?php

declare(strict_types=1);

namespace App\Livewire\NetWorth;

use App\Models\Liability;
use App\Models\LiabilityGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Liabilities')]
class LiabilityManager extends Component
{
    #[Rule('required|string|min:2')]
    public string $groupName = '';

    public ?int $groupDisplayOrder = null;
    public ?int $groupId = null;

    #[Rule('required|exists:liability_groups,id')]
    public ?int $liability_group_id = null;

    #[Rule('required|string|min:2')]
    public string $liabilityName = '';

    public ?string $liabilityNotes = null;

    #[Rule('nullable|numeric|min:0')]
    public ?string $interest_rate = null;

    public ?int $liabilityId = null;

    public function saveGroup(): void
    {
        $this->validateOnly('groupName');

        LiabilityGroup::updateOrCreate([
            'id' => $this->groupId,
        ], [
            'user_id' => Auth::id(),
            'name' => $this->groupName,
            'display_order' => $this->groupDisplayOrder,
        ]);

        $this->reset(['groupName', 'groupDisplayOrder', 'groupId']);
        session()->flash('status', 'Liability group saved.');
    }

    public function editGroup(int $groupId): void
    {
        $group = LiabilityGroup::where('user_id', Auth::id())->findOrFail($groupId);
        $this->groupId = $group->id;
        $this->groupName = $group->name;
        $this->groupDisplayOrder = $group->display_order;
    }

    public function deleteGroup(int $groupId): void
    {
        LiabilityGroup::where('user_id', Auth::id())->where('id', $groupId)->delete();
        session()->flash('status', 'Liability group removed.');
    }

    public function saveLiability(): void
    {
        $this->validate();

        Liability::updateOrCreate([
            'id' => $this->liabilityId,
        ], [
            'user_id' => Auth::id(),
            'liability_group_id' => $this->liability_group_id,
            'name' => $this->liabilityName,
            'notes' => $this->liabilityNotes,
            'interest_rate' => $this->interest_rate,
        ]);

        $this->reset(['liability_group_id', 'liabilityName', 'liabilityNotes', 'interest_rate', 'liabilityId']);
        session()->flash('status', 'Liability saved.');
    }

    public function editLiability(int $liabilityId): void
    {
        $liability = Liability::where('user_id', Auth::id())->findOrFail($liabilityId);
        $this->liabilityId = $liability->id;
        $this->liability_group_id = $liability->liability_group_id;
        $this->liabilityName = $liability->name;
        $this->liabilityNotes = $liability->notes;
        $this->interest_rate = $liability->interest_rate?->toString();
    }

    public function deleteLiability(int $liabilityId): void
    {
        Liability::where('user_id', Auth::id())->where('id', $liabilityId)->delete();
        session()->flash('status', 'Liability removed.');
    }

    public function render(): View
    {
        $groups = LiabilityGroup::with('liabilities')
            ->where('user_id', Auth::id())
            ->orderByRaw('COALESCE(display_order, 9999)')
            ->orderBy('name')
            ->get();

        return view('livewire.net-worth.liability-manager', [
            'groups' => $groups,
        ]);
    }
}
