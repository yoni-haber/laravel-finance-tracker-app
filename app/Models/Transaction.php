<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'type',
        'amount',
        'date',
        'is_recurring',
        'frequency',
        'recurring_until',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_recurring' => 'boolean',
            'recurring_until' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function occurrenceExceptions(): HasMany
    {
        return $this->hasMany(TransactionException::class);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeForMonthYear($query, int $month, int $year)
    {
        return $query->whereMonth('date', $month)->whereYear('date', $year);
    }

    public function scopeForCategory($query, ?int $categoryId)
    {
        return $categoryId ? $query->where('category_id', $categoryId) : $query;
    }

    public function projectedOccurrencesForMonth(int $month, int $year): Collection
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $recurringEnd = $this->recurring_until ? Carbon::parse($this->recurring_until)->endOfDay() : null;
        // Use the earlier of the user-configured end date or the current month window so
        // projections never escape the period being reported.
        $cycleEnd = $recurringEnd && $recurringEnd < $end ? $recurringEnd : $end;

        if (! $this->is_recurring) {
            return ($this->date->between($start, $end))
                ? collect([$this->replicateForDate($this->date, false)])
                : collect();
        }

        if (! $this->frequency) {
            return collect();
        }

        if ($recurringEnd && $start->greaterThan($recurringEnd)) {
            return collect();
        }

        if ($recurringEnd && $this->date->greaterThan($recurringEnd)) {
            return collect();
        }

        // Normalise exception dates before comparing to the generated recurrence sequence.
        $skippedDates = $this->occurrenceExceptions
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->toArray();

        $occurrences = collect();
        $current = $this->date->copy();

        while ($current <= $cycleEnd) {
            if ($current->between($start, $end) && ! in_array($current->toDateString(), $skippedDates, true)) {
                $occurrences->push($this->replicateForDate($current));
            }

            switch ($this->frequency) {
                case 'weekly':
                    $current->addWeek();
                    break;
                case 'monthly':
                    $current->addMonth();
                    break;
                case 'yearly':
                    $current->addYear();
                    break;
                default:
                    break 2;
            }
        }

        return $occurrences;
    }

    protected function replicateForDate(Carbon $date, bool $isProjected = true): self
    {
        $clone = $this->replicate();
        $clone->id = $this->id;
        $clone->date = $date;
        $clone->setAttribute('projected', $isProjected);
        $clone->setRelation('category', $this->category);

        return $clone;
    }
}
