<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionReport
{
    public static function monthlyWithRecurring(int $userId, int $month, int $year, ?int $categoryId = null): Collection
    {
        $baseQuery = Transaction::forUser($userId)
            ->forCategory($categoryId)
            ->with('category')
            ->where(function ($query) use ($month, $year) {
                $query->where(function ($sub) use ($month, $year) {
                    $sub->where('is_recurring', false)
                        ->forMonthYear($month, $year);
                })->orWhere('is_recurring', true);
            });

        return $baseQuery->get()->flatMap(fn (Transaction $transaction) => $transaction->projectedOccurrencesForMonth($month, $year));
    }
}
