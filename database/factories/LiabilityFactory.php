<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Liability;
use App\Models\LiabilityGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Liability> */
class LiabilityFactory extends Factory
{
    protected $model = Liability::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'liability_group_id' => LiabilityGroup::factory(),
            'name' => $this->faker->words(2, true),
            'notes' => $this->faker->optional()->sentence(),
            'interest_rate' => $this->faker->optional()->randomFloat(2, 1, 20),
        ];
    }
}
