<?php

namespace Database\Factories;

use App\Enums\Frequency;
use App\Models\RecurringTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTask>
 */
class RecurringTaskFactory extends Factory
{
    protected $model = RecurringTask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'points' => fake()->randomElement([10, 30, 50, 100]),
            'gold' => fn (array $atributos) => $atributos['points'],
            'frequency' => Frequency::Daily,
            'interval' => 1,
            'starts_on' => today(),
            'is_active' => true,
        ];
    }
}
