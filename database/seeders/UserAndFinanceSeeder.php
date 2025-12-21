<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionException;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserAndFinanceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            $users = $this->seedUsers();

            $categories = $this->seedCategories($users);

            $this->seedBudgets($users['alex'], $categories['alex'], [
                [
                    'category' => 'Housing',
                    'month' => $now->month,
                    'year' => $now->year,
                    'amount' => 1800.00,
                ],
                [
                    'category' => 'Groceries',
                    'month' => $now->month,
                    'year' => $now->year,
                    'amount' => 650.00,
                ],
                [
                    'category' => 'Utilities',
                    'month' => $now->copy()->subMonth()->month,
                    'year' => $now->copy()->subMonth()->year,
                    'amount' => 220.00,
                ],
                [
                    'category' => 'Travel',
                    'month' => $now->copy()->addMonths(2)->month,
                    'year' => $now->copy()->addMonths(2)->year,
                    'amount' => 1200.00,
                ],
            ]);

            $this->seedBudgets($users['jamie'], $categories['jamie'], [
                [
                    'category' => 'Living',
                    'month' => $now->month,
                    'year' => $now->year,
                    'amount' => 1200.00,
                ],
                [
                    'category' => 'Consulting',
                    'month' => $now->copy()->addMonth()->month,
                    'year' => $now->copy()->addMonth()->year,
                    'amount' => 1000.00,
                ],
                [
                    'category' => 'Education',
                    'month' => $now->copy()->addMonths(2)->month,
                    'year' => $now->copy()->addMonths(2)->year,
                    'amount' => 350.00,
                ],
            ]);

            $this->seedTransactions($users, $categories);
        });
    }

    private function seedUsers(): array
    {
        $now = Carbon::now();

        $alex = User::updateOrCreate(
            ['email' => 'alex@example.com'],
            [
                'name' => 'Alex Financier',
                'password' => 'password',
                'email_verified_at' => $now->copy()->subDay(),
                'two_factor_secret' => 'alex-2fa-secret',
                'two_factor_recovery_codes' => json_encode(['alex-recovery-1', 'alex-recovery-2']),
                'two_factor_confirmed_at' => $now,
                'remember_token' => Str::random(10),
            ],
        );

        $jamie = User::updateOrCreate(
            ['email' => 'jamie@example.com'],
            [
                'name' => 'Jamie Auditor',
                'password' => 'password',
                'email_verified_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => Str::random(10),
            ],
        );

        return [
            'alex' => $alex,
            'jamie' => $jamie,
        ];
    }

    private function seedCategories(array $users): array
    {
        $alexCategories = collect([
            'Salary',
            'Freelance',
            'Housing',
            'Groceries',
            'Utilities',
            'Entertainment',
            'Travel',
            'Savings',
        ])->mapWithKeys(function ($name) use ($users) {
            $category = Category::updateOrCreate(
                ['user_id' => $users['alex']->id, 'name' => $name],
                []
            );

            return [$name => $category];
        });

        $jamieCategories = collect([
            'Consulting',
            'Living',
            'Education',
        ])->mapWithKeys(function ($name) use ($users) {
            $category = Category::updateOrCreate(
                ['user_id' => $users['jamie']->id, 'name' => $name],
                []
            );

            return [$name => $category];
        });

        return [
            'alex' => $alexCategories,
            'jamie' => $jamieCategories,
        ];
    }

    private function seedBudgets(User $user, $categories, array $budgetDefinitions): void
    {
        foreach ($budgetDefinitions as $definition) {
            // Unique constraint ensures repeatable seeds. updateOrCreate keeps values current.
            Budget::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_id' => $categories[$definition['category']]->id,
                    'month' => $definition['month'],
                    'year' => $definition['year'],
                ],
                ['amount' => $definition['amount']],
            );
        }
    }

    private function seedTransactions(array $users, array $categories): void
    {
        $alex = $users['alex'];
        $jamie = $users['jamie'];
        $now = Carbon::now();

        $transactions = [
            // Alex income and recurring salary with an extended end date for projections.
            [
                'user' => $alex,
                'category' => $categories['alex']['Salary'],
                'type' => 'income',
                'amount' => 5500.00,
                'date' => $now->copy()->subMonths(1)->startOfMonth(),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'recurring_until' => $now->copy()->addMonths(6),
                'description' => 'Full-time salary (recurring)',
            ],
            [
                'user' => $alex,
                'category' => $categories['alex']['Freelance'],
                'type' => 'income',
                'amount' => 850.00,
                'date' => $now->copy()->subWeeks(2),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Freelance web design gig',
            ],

            // Alex housing and utilities showing mixed recurrence and one-off adjustments.
            [
                'user' => $alex,
                'category' => $categories['alex']['Housing'],
                'type' => 'expense',
                'amount' => 1750.00,
                'date' => $now->copy()->startOfMonth(),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'recurring_until' => $now->copy()->addMonths(11),
                'description' => 'Apartment rent recurring',
            ],
            [
                'user' => $alex,
                'category' => $categories['alex']['Utilities'],
                'type' => 'expense',
                'amount' => 95.40,
                'date' => $now->copy()->subMonth()->startOfMonth()->addDays(5),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Electricity bill (prior month)',
            ],
            [
                'user' => $alex,
                'category' => $categories['alex']['Utilities'],
                'type' => 'expense',
                'amount' => 0.00,
                'date' => $now->copy()->startOfMonth()->addDays(5),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Utility credit from provider',
            ],

            // Alex groceries and entertainment illustrate category coverage.
            [
                'user' => $alex,
                'category' => $categories['alex']['Groceries'],
                'type' => 'expense',
                'amount' => 125.80,
                'date' => $now->copy()->subDays(10),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Weekly grocery run',
            ],
            [
                'user' => $alex,
                'category' => $categories['alex']['Entertainment'],
                'type' => 'expense',
                'amount' => 64.99,
                'date' => $now->copy()->subDays(3),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Concert ticket',
            ],
            [
                'user' => $alex,
                'category' => $categories['alex']['Travel'],
                'type' => 'expense',
                'amount' => 480.00,
                'date' => $now->copy()->addMonths(1)->startOfMonth(),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Flight booking for conference',
            ],

            // Alex savings recurring transfer with an exception later.
            [
                'user' => $alex,
                'category' => $categories['alex']['Savings'],
                'type' => 'expense',
                'amount' => 300.00,
                'date' => $now->copy()->startOfMonth()->addDays(2),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'recurring_until' => $now->copy()->addMonths(4),
                'description' => 'Automatic savings transfer',
            ],

            // Jamie transactions showcase another user's data and uncategorized spending.
            [
                'user' => $jamie,
                'category' => $categories['jamie']['Consulting'],
                'type' => 'income',
                'amount' => 3200.00,
                'date' => $now->copy()->subMonths(2)->startOfMonth()->addDays(7),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'recurring_until' => $now->copy()->addMonths(1),
                'description' => 'Consulting retainer',
            ],
            [
                'user' => $jamie,
                'category' => $categories['jamie']['Living'],
                'type' => 'expense',
                'amount' => 900.00,
                'date' => $now->copy()->subMonth()->addDays(1),
                'is_recurring' => true,
                'frequency' => 'monthly',
                'recurring_until' => $now->copy()->addMonths(3),
                'description' => 'Shared apartment rent',
            ],
            [
                'user' => $jamie,
                'category' => $categories['jamie']['Education'],
                'type' => 'expense',
                'amount' => 200.00,
                'date' => $now->copy()->addMonths(1)->startOfMonth()->addDays(12),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Online course enrollment',
            ],
            [
                'user' => $jamie,
                'category' => null,
                'type' => 'expense',
                'amount' => 45.00,
                'date' => $now->copy()->subDays(6),
                'is_recurring' => false,
                'frequency' => null,
                'recurring_until' => null,
                'description' => 'Misc uncategorized purchase',
            ],
        ];

        $recurringExceptionSeeds = [];

        foreach ($transactions as $data) {
            $transaction = Transaction::updateOrCreate(
                [
                    'user_id' => $data['user']->id,
                    'date' => $data['date']->toDateString(),
                    'amount' => $data['amount'],
                    'description' => $data['description'],
                ],
                [
                    'category_id' => $data['category']?->id,
                    'type' => $data['type'],
                    'is_recurring' => $data['is_recurring'],
                    'frequency' => $data['frequency'],
                    'recurring_until' => $data['recurring_until'],
                ],
            );

            // Capture exceptions to create after we know transaction IDs.
            if ($transaction->description === 'Apartment rent recurring') {
                $recurringExceptionSeeds[] = [
                    'transaction' => $transaction,
                    'dates' => [
                        $data['date']->copy()->addMonths(2), // skipped rent due to free month promotion
                    ],
                ];
            }

            if ($transaction->description === 'Automatic savings transfer') {
                $recurringExceptionSeeds[] = [
                    'transaction' => $transaction,
                    'dates' => [
                        $data['date']->copy()->addMonths(3)->addDay(), // postpone one transfer by a day to simulate adjustment
                    ],
                ];
            }

            if ($transaction->description === 'Consulting retainer') {
                $recurringExceptionSeeds[] = [
                    'transaction' => $transaction,
                    'dates' => [
                        $data['date']->copy()->addMonth(), // paused retainer for a month
                    ],
                ];
            }
        }

        foreach ($recurringExceptionSeeds as $exceptionSeed) {
            foreach ($exceptionSeed['dates'] as $exceptionDate) {
                TransactionException::updateOrCreate(
                    [
                        'transaction_id' => $exceptionSeed['transaction']->id,
                        'date' => $exceptionDate->toDateString(),
                    ],
                    [],
                );
            }
        }
    }
}
