<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\FinanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinanceCategory>
 */
class FinanceCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'type' => TransactionType::Expense,
            'is_active' => true,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => TransactionType::Income]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
