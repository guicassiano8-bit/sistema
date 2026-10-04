<?php

namespace Database\Factories;

use App\Enums\InvestmentTransactionType;
use App\Models\Asset;
use App\Models\InvestmentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvestmentTransaction>
 */
class InvestmentTransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'asset_id' => Asset::factory(),
            'type' => InvestmentTransactionType::Deposit,
            'date' => today(),
            'total' => fake()->randomFloat(2, 100, 5000),
            'fees' => 0,
        ];
    }

    public function withdrawal(): static
    {
        return $this->state(['type' => InvestmentTransactionType::Withdrawal]);
    }
}
