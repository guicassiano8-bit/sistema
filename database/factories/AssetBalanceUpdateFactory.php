<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetBalanceUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetBalanceUpdate>
 */
class AssetBalanceUpdateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'reference_date' => today(),
            'gross_balance' => fake()->randomFloat(2, 100, 10000),
        ];
    }
}
