<?php

namespace App\Livewire\Statements;

use App\Models\BankStatementImport;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\BankStatement\DuplicateDetector;
use App\Support\StatementImportCommitter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Review Import')]
class StatementImportReview extends Component
{
    public BankStatementImport $import;

    public ?int $editingTransactionId = null;

    public ?int $deletingTransactionId = null;

    public array $editForm = [];

    protected function rules(): array
    {
        return [
            'editForm.description' => 'required|string|max:500',
            'editForm.amount' => 'required|numeric|min:0.01',
            'editForm.date' => 'required|date',
            'editForm.type' => 'required|in:income,expense',
            'editForm.category_id' => ['nullable', Rule::exists('categories', 'id')->where('user_id', Auth::id())],
        ];
    }

    public function mount(int $importId): void
    {
        try {
            $this->import = BankStatementImport::with(['bankProfile'])
                ->forUser(Auth::id())
                ->findOrFail($importId);
        } catch (ModelNotFoundException) {
            $this->redirectRoute('statements.import');

            return;
        }

        if (! $this->import->isParsed()) {
            session()->flash('error', 'Import is not ready for review.');
            $this->redirectRoute('statements.import', navigate: true);
        }
    }

    public function editTransaction(int $transactionId): void
    {
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);

        $this->editingTransactionId = $transactionId;
        $this->editForm = [
            'description' => $transaction->description,
            'amount' => (string) abs($transaction->amount),
            'date' => $transaction->date->toDateString(),
            'type' => $this->determineTransactionType($transaction),
            'category_id' => $transaction->category_id,
        ];
    }

    public function updateTransaction(): void
    {
        $this->validate();

        $transaction = $this->import->importedTransactions()->findOrFail($this->editingTransactionId);

        $normalizedDescription = strtoupper(trim($this->editForm['description']));

        $amount = $this->editForm['type'] === Transaction::TYPE_EXPENSE
            ? -abs((float) $this->editForm['amount'])
            : abs((float) $this->editForm['amount']);

        $transaction->update([
            'description' => $normalizedDescription,
            'amount' => $amount,
            'date' => $this->editForm['date'],
            'category_id' => $this->editForm['category_id'] ?? null,
        ]);

        // Regenerate hash using the saved (normalized) values so it matches future imports
        $duplicateDetector = new DuplicateDetector($this->import->user_id);
        $hash = $duplicateDetector->generateTransactionHash(
            $this->import->user_id,
            $this->editForm['date'],
            $amount,
            $normalizedDescription
        );
        $isDuplicate = $duplicateDetector->isDuplicateExcluding($hash, $transaction->id);
        $transaction->update([
            'hash' => $hash,
            'is_duplicate' => $isDuplicate,
        ]);

        $this->cancelEdit();
        session()->flash('status', 'Transaction updated successfully.');
    }

    public function updateCategory(int $transactionId, ?int $categoryId): void
    {
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);
        $transaction->update(['category_id' => $categoryId]);
    }

    public function updateType(int $transactionId, string $type): void
    {
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);

        $amount = $type === Transaction::TYPE_EXPENSE
            ? -abs($transaction->amount)
            : abs($transaction->amount);

        $transaction->update(['amount' => $amount]);

        // Regenerate hash using the explicit $amount variable, not the post-update model attribute
        $duplicateDetector = new DuplicateDetector($this->import->user_id);
        $hash = $duplicateDetector->generateTransactionHash(
            $this->import->user_id,
            $transaction->date,
            $amount,
            $transaction->description
        );
        $isDuplicate = $duplicateDetector->isDuplicateExcluding($hash, $transaction->id);
        $transaction->update([
            'hash' => $hash,
            'is_duplicate' => $isDuplicate,
        ]);
    }

    public function confirmDeleteTransaction(int $transactionId): void
    {
        $this->deletingTransactionId = $transactionId;
        $this->dispatch('open-delete-modal');
    }

    public function deleteTransaction(): void
    {
        if ($this->deletingTransactionId) {
            $this->import->importedTransactions()->findOrFail($this->deletingTransactionId)->delete();
            $this->deletingTransactionId = null;
            session()->flash('status', 'Transaction removed from import.');
        }
        $this->dispatch('close-delete-modal');
    }

    private function determineTransactionType($transaction): string
    {
        if ($this->import->isCreditCardStatement()) {
            return $transaction->amount < 0 ? Transaction::TYPE_EXPENSE : Transaction::TYPE_INCOME;
        }

        return $transaction->amount >= 0 ? Transaction::TYPE_INCOME : Transaction::TYPE_EXPENSE;
    }

    public function commitImport()
    {
        if (! $this->import->isParsed()) {
            $this->addError('commit', 'Import is not ready to be committed.');

            return;
        }

        try {
            $committer = new StatementImportCommitter($this->import);
            $success = $committer->commit();

            if ($success) {
                session()->flash('status', 'Transactions imported successfully.');

                return redirect()->route('transactions');
            } else {
                $this->addError('commit', 'Failed to import transactions. Please try again.');
            }
        } catch (\Exception $e) {
            logger()->error('Failed to commit import', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('commit', 'Failed to import transactions. Please try again.');
        }
    }

    public function backToImport()
    {
        return redirect()->route('statements.import');
    }

    public function cancelEdit(): void
    {
        $this->editingTransactionId = null;
        $this->editForm = [];
        $this->resetValidation();
    }

    public function render(): View
    {
        $transactions = $this->import->importedTransactions()
            ->orderBy('date', 'desc')
            ->orderBy('created_at')
            ->get();

        $summary = [
            'total' => $transactions->count(),
            'duplicates' => $transactions->where('is_duplicate', true)->count(),
            'new_transactions' => $transactions->where('is_duplicate', false)->count(),
            'total_amount' => $transactions->where('is_duplicate', false)->sum('amount'),
        ];

        return view('livewire.statements.import-review', [
            'transactions' => $transactions,
            'summary' => $summary,
            'categories' => Category::where('user_id', Auth::id())->orderBy('name')->get(),
        ]);
    }
}
