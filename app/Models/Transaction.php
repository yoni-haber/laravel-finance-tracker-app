<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_recurring' => 'boolean',
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

        if (! $this->is_recurring) {
            return ($this->date->between($start, $end))
                ? collect([$this->replicateForDate($this->date, false)])
                : collect();
        }

        if (! $this->frequency) {
            return collect();
        }

        $occurrences = collect();
        $current = $this->date->copy();

        while ($current <= $end) {
            if ($current->between($start, $end)) {
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
