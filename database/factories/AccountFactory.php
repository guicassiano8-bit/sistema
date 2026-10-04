<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'type' => AccountType::Checking,
            'initial_balance' => 0,
            'is_active' => true,
        ];
    }

    public function wallet(): static
    {
        return $this->state(['name' => 'Carteira', 'type' => AccountType::Wallet]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
