<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TransactionReport
{
    public static function monthlyWithRecurring(int $userId, int $month, int $year, ?int $categoryId = null): Collection
    {
        if (! Schema::hasTable('transactions')
            || ! Schema::hasTable('categories')
            || ! Schema::hasTable('transaction_exceptions')) {
            return collect();
        }

        $baseQuery = Transaction::forUser($userId)
            ->forCategory($categoryId)
            ->with(['category', 'occurrenceExceptions'])
            ->where(function ($query) use ($month, $year) {
                $query->where(function ($sub) use ($month, $year) {
                    $sub->where('is_recurring', false)
                        ->forMonthYear($month, $year);
                })->orWhere('is_recurring', true);
            });

        return $baseQuery->get()->flatMap(fn (Transaction $transaction) => $transaction->projectedOccurrencesForMonth($month, $year));
    }
}
