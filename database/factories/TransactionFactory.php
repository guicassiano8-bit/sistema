<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinanceCategory;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'finance_category_id' => FinanceCategory::factory(),
            'type' => TransactionType::Expense,
            'description' => fake()->words(3, true),
            'amount' => fake()->randomFloat(2, 5, 500),
            'date' => today(),
            'status' => TransactionStatus::Paid,
        ];
    }

    public function income(): static
    {
        return $this->state([
            'type' => TransactionType::Income,
            'finance_category_id' => FinanceCategory::factory()->income(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(['status' => TransactionStatus::Pending]);
    }
}
