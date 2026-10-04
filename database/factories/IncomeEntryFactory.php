<?php

namespace Database\Factories;

use App\Enums\IncomeType;
use App\Models\Asset;
use App\Models\IncomeEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IncomeEntry>
 */
class IncomeEntryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'reference_month' => today()->startOfMonth(),
            'payment_date' => today(),
            'amount' => fake()->randomFloat(2, 1, 200),
            'type' => IncomeType::Dividend,
        ];
    }
}
