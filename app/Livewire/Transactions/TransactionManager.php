<?php

namespace App\Livewire\Transactions;

use App\Models\Category;
use App\Models\Transaction;
use App\Support\TransactionReport;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Transactions')]
class TransactionManager extends Component
{
    #[Rule('required|in:income,expense')]
    public string $type = 'expense';

    #[Rule('required|numeric|min:0.01')]
    public string $amount = '0.00';

    #[Rule('required|date')]
    public string $date;

    #[Rule('nullable|string|max:500')]
    public ?string $description = null;

    #[Rule('nullable|exists:categories,id')]
    public ?int $category_id = null;

    #[Rule('boolean')]
    public bool $is_recurring = false;

    #[Rule('nullable|required_if:is_recurring,true|in:weekly,monthly,yearly')]
    public ?string $frequency = null;

    public ?int $transactionId = null;
    public int $month;
    public int $year;
    public ?int $filterCategory = null;
    public ?string $filterType = null;

    public function mount(): void
    {
        $now = now();
        $this->date = $now->toDateString();
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['user_id'] = Auth::id();

        if (! $data['is_recurring']) {
            $data['frequency'] = null;
        }

        Transaction::updateOrCreate(['id' => $this->transactionId], $data);

        $this->resetForm();
        session()->flash('status', 'Transaction saved successfully.');
    }

    public function edit(int $transactionId): void
    {
        $transaction = Transaction::forUser(Auth::id())->findOrFail($transactionId);
        $this->transactionId = $transaction->id;
        $this->type = $transaction->type;
        $this->amount = (string) $transaction->amount;
        $this->date = $transaction->date->toDateString();
        $this->description = $transaction->description;
        $this->category_id = $transaction->category_id;
        $this->is_recurring = $transaction->is_recurring;
        $this->frequency = $transaction->frequency;
    }

    public function delete(int $transactionId, ?string $occurrenceDate = null): void
    {
        $transaction = Transaction::forUser(Auth::id())->findOrFail($transactionId);

        if ($transaction->is_recurring && $occurrenceDate) {
            $date = Carbon::parse($occurrenceDate)->toDateString();

            $transaction->occurrenceExceptions()->firstOrCreate(['date' => $date]);
            session()->flash('status', 'Transaction occurrence removed.');

            return;
        }

        $transaction->delete();
        session()->flash('status', 'Transaction removed.');
    }

    public function updatedIsRecurring(bool $value): void
    {
        if (! $value) {
            $this->frequency = null;
            return;
        }

        if (! $this->frequency) {
            $this->frequency = 'monthly';
        }
    }

    public function render(): View
    {
        $userId = Auth::id();

        $transactions = TransactionReport::monthlyWithRecurring($userId, $this->month, $this->year, $this->filterCategory)
            ->when($this->filterType, fn ($items) => $items->where('type', $this->filterType))
            ->sortByDesc('date');

        return view('livewire.transactions.manager', [
            'transactions' => $transactions,
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(),
        ]);
    }

    public function resetForm(): void
    {
        $this->transactionId = null;
        $this->type = 'expense';
        $this->amount = '0.00';
        $this->description = null;
        $this->category_id = null;
        $this->is_recurring = false;
        $this->frequency = null;

        $this->resetValidation();
        $this->resetErrorBag();
    }
}
