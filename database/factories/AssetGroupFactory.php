<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AssetGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetGroup> */
class AssetGroupFactory extends Factory
{
    protected $model = AssetGroup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->unique()->word(),
            'display_order' => $this->faker->optional()->numberBetween(1, 5),
        ];
    }
}
