<?php

namespace App\Livewire\Statements;

use App\Models\BankStatementImport;
use App\Models\Category;
use App\Models\Transaction;
use App\Support\StatementImportCommitter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Review Import')]
class StatementImportReview extends Component
{
    public BankStatementImport $import;

    public bool $confirmingCommit = false;

    public ?int $editingTransactionId = null;

    public array $editForm = [];

    protected function rules(): array
    {
        return [
            'editForm.description' => 'required|string|max:500',
            'editForm.amount' => 'required|numeric|min:0.01',
            'editForm.date' => 'required|date',
            'editForm.type' => 'required|in:income,expense',
            'editForm.category_id' => 'nullable|exists:categories,id',
        ];
    }

    public function mount(int $importId): void
    {
        try {
            $this->import = BankStatementImport::with(['importedTransactions', 'bankProfile'])
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

    public function confirmCommit(): void
    {
        $this->confirmingCommit = true;
    }

    public function editTransaction(int $transactionId): void
    {
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);

        $this->editingTransactionId = $transactionId;
        $this->editForm = [
            'description' => $transaction->description,
            'amount' => (string) abs($transaction->amount), // Always show as positive
            'date' => $transaction->date->toDateString(),
            'type' => $this->determineTransactionType($transaction), // Use correct logic based on statement type
            'category_id' => $this->extractCategoryId($transaction->external_id),
        ];
    }

    public function updateTransaction(): void
    {
        $this->validate();

        $transaction = $this->import->importedTransactions()->findOrFail($this->editingTransactionId);

        // Convert amount based on type selection and statement type
        if ($this->import->bankProfile->isCreditCardStatement()) {
            // Credit Card: Expense = Negative (purchases), Income = Positive (payments)
            $amount = $this->editForm['type'] === Transaction::TYPE_EXPENSE
                ? -abs((float) $this->editForm['amount'])  // Expense = negative
                : abs((float) $this->editForm['amount']);  // Income = positive
        } else {
            // Bank Statement: Income = Positive, Expense = Negative
            $amount = $this->editForm['type'] === Transaction::TYPE_EXPENSE
                ? -abs((float) $this->editForm['amount']) // Expense = negative
                : abs((float) $this->editForm['amount']);  // Income = positive
        }

        $transaction->update([
            'description' => strtoupper(trim($this->editForm['description'])),
            'amount' => $amount,
            'date' => $this->editForm['date'],
        ]);

        // Update category assignment
        $categoryId = $this->editForm['category_id'] ?? null;
        $transaction->update([
            'external_id' => $categoryId ? "category:{$categoryId}" : null,
        ]);

        // Regenerate hash with updated data
        $transaction->update([
            'hash' => sha1(
                $this->import->user_id.
                $transaction->date->toDateString().
                number_format($transaction->amount, 2, '.', '').
                $transaction->description
            ),
        ]);

        $this->cancelEdit();
        session()->flash('status', 'Transaction updated successfully.');
    }

    public function updateCategory(int $transactionId, ?int $categoryId): void
    {
        // Store category selection for use during commit
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);
        $transaction->update(['external_id' => $categoryId ? "category:{$categoryId}" : null]);
    }

    public function updateType(int $transactionId, string $type): void
    {
        $transaction = $this->import->importedTransactions()->findOrFail($transactionId);

        // Convert amount based on type and statement type
        if ($this->import->bankProfile->isCreditCardStatement()) {
            // Credit Card: Expense = Negative (purchases), Income = Positive (payments)
            $amount = $type === Transaction::TYPE_EXPENSE
                ? -abs($transaction->amount)  // Expense = negative
                : abs($transaction->amount);  // Income = positive
        } else {
            // Bank Statement: Income = Positive, Expense = Negative
            $amount = $type === Transaction::TYPE_EXPENSE
                ? -abs($transaction->amount) // Expense = negative
                : abs($transaction->amount);  // Income = positive
        }

        $transaction->update(['amount' => $amount]);

        // Regenerate hash
        $transaction->update([
            'hash' => sha1(
                $this->import->user_id.
                $transaction->date->toDateString().
                number_format($transaction->amount, 2, '.', '').
                $transaction->description
            ),
        ]);
    }

    public function deleteTransaction(int $transactionId): void
    {
        $this->import->importedTransactions()->findOrFail($transactionId)->delete();
        session()->flash('status', 'Transaction removed from import.');
    }

    private function extractCategoryId(?string $externalId): ?int
    {
        if ($externalId && str_starts_with($externalId, 'category:')) {
            return (int) str_replace('category:', '', $externalId);
        }

        return null;
    }

    private function determineTransactionType($transaction): string
    {
        if ($this->import->bankProfile->isCreditCardStatement()) {
            // Credit Card: Negative = Expense (purchases), Positive = Income (payments/refunds)
            return $transaction->amount < 0 ? Transaction::TYPE_EXPENSE : Transaction::TYPE_INCOME;
        } else {
            // Bank Statement: Positive = Income, Negative = Expense
            return $transaction->amount >= 0 ? Transaction::TYPE_INCOME : Transaction::TYPE_EXPENSE;
        }
    }

    public function cancelCommit(): void
    {
        $this->confirmingCommit = false;
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
