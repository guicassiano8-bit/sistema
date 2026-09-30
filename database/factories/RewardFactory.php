<?php

namespace Database\Factories;

use App\Enums\TaskRank;
use App\Models\Reward;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reward>
 */
class RewardFactory extends Factory
{
    protected $model = Reward::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'cost' => fake()->randomElement([50, 100, 300, 1000]),
            'rank' => TaskRank::E,
            'is_active' => true,
            'is_repeatable' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function single(): static
    {
        return $this->state(['is_repeatable' => false]);
    }
}
