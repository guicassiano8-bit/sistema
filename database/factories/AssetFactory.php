<?php

namespace Database\Factories;

use App\Enums\Indexer;
use App\Models\Asset;
use App\Models\AssetType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    /**
     * CDB por padrão: renda fixa, com indexador e saldo informado à mão.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_type_id' => fn () => AssetType::firstOrCreate(['name' => 'CDB'], ['is_market_traded' => false])->id,
            'name' => 'CDB '.fake()->company(),
            'indexer' => Indexer::Cdi,
            'rate' => 110,
            'maturity_date' => today()->addYear(),
            'is_active' => true,
        ];
    }

    public function fii(): static
    {
        return $this->state(fn () => [
            'asset_type_id' => AssetType::firstOrCreate(['name' => 'FII'], ['is_market_traded' => true])->id,
            'name' => fake()->company(),
            'ticker' => strtoupper(fake()->unique()->lexify('????')).'11',
            'indexer' => null,
            'rate' => null,
            'maturity_date' => null,
        ]);
    }
}
