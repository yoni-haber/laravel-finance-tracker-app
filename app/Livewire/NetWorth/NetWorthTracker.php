<?php

namespace App\Livewire\NetWorth;

use App\Models\NetWorthEntry;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Net Worth')]
class NetWorthTracker extends Component
{
    #[Rule('required|numeric|min:0')]
    public string $assets = '0.00';

    #[Rule('required|numeric|min:0')]
    public string $liabilities = '0.00';

    #[Rule('required|date')]
    public string $date;

    public ?int $entryId = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();
        $data['net_worth'] = (float) $data['assets'] - (float) $data['liabilities'];

        NetWorthEntry::updateOrCreate(['id' => $this->entryId], $data);

        $this->resetForm();

        session()->flash('status', 'Net worth entry saved successfully.');
    }

    public function edit(int $entryId): void
    {
        $entry = NetWorthEntry::forUser(Auth::id())->findOrFail($entryId);

        $this->entryId = $entry->id;
        $this->assets = (string) $entry->assets;
        $this->liabilities = (string) $entry->liabilities;
        $this->date = $entry->date->toDateString();
    }

    public function delete(int $entryId): void
    {
        NetWorthEntry::forUser(Auth::id())->where('id', $entryId)->delete();

        if ($this->entryId === $entryId) {
            $this->resetForm();
        }

        session()->flash('status', 'Net worth entry removed.');
    }

    public function render(): View
    {
        return view('livewire.net-worth.tracker', [
            'entries' => NetWorthEntry::forUser(Auth::id())->orderByDesc('date')->get(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->entryId = null;
        $this->assets = '0.00';
        $this->liabilities = '0.00';
        $this->date = now()->toDateString();
    }
}
