<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'asset_group_id' => AssetGroup::factory(),
            'name' => $this->faker->words(2, true),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
