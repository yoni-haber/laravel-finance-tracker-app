<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\LiabilityGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LiabilityGroup> */
class LiabilityGroupFactory extends Factory
{
    protected $model = LiabilityGroup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->word(),
            'display_order' => $this->faker->optional()->numberBetween(1, 5),
        ];
    }
}
