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
    #[Rule('required|date')]
    public string $date;

    #[Rule('required|numeric|min:0')]
    public string $assets = '0.00';

    #[Rule('required|numeric|min:0')]
    public string $liabilities = '0.00';

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

        if (! $this->entryId) {
            $existingEntry = NetWorthEntry::where('user_id', Auth::id())
                ->whereDate('date', $data['date'])
                ->first();

            if ($existingEntry) {
                $this->entryId = $existingEntry->id;
            }
        }

        NetWorthEntry::updateOrCreate([
            'id' => $this->entryId,
        ], $data);

        $this->resetForm();
        session()->flash('status', 'Net worth entry saved.');
    }

    public function edit(int $entryId): void
    {
        $entry = NetWorthEntry::where('user_id', Auth::id())->findOrFail($entryId);

        $this->entryId = $entry->id;
        $this->date = $entry->date->toDateString();
        $this->assets = (string) $entry->assets;
        $this->liabilities = (string) $entry->liabilities;
    }

    public function delete(int $entryId): void
    {
        NetWorthEntry::where('user_id', Auth::id())->where('id', $entryId)->delete();
        session()->flash('status', 'Net worth entry removed.');
    }

    public function render(): View
    {
        $entries = NetWorthEntry::where('user_id', Auth::id())
            ->orderByDesc('date')
            ->get();

        return view('livewire.net-worth.tracker', [
            'entries' => $entries,
        ]);
    }

    public function getCalculatedNetWorthProperty(): string
    {
        return number_format((float) $this->assets - (float) $this->liabilities, 2);
    }

    protected function resetForm(): void
    {
        $this->entryId = null;
        $this->assets = '0.00';
        $this->liabilities = '0.00';
        $this->date = now()->toDateString();
    }
}
