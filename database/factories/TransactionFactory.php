<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['income', 'expense']);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'type' => $type,
            'amount' => $type === 'income' ? fake()->randomFloat(2, 200, 2000) : fake()->randomFloat(2, 10, 800),
            'date' => fake()->dateTimeBetween('-3 months', '+3 months'),
            'is_recurring' => false,
            'frequency' => null,
            'description' => fake()->sentence(),
        ];
    }

    public function recurring(string $frequency = 'monthly'): self
    {
        return $this->state(fn () => [
            'is_recurring' => true,
            'frequency' => $frequency,
        ]);
    }
}
