<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $data = today();

        return [
            'title' => fake()->sentence(3),
            'points' => fake()->randomElement([10, 30, 50, 100]),
            'scheduled_date' => $data,
            'original_date' => $data,
            'status' => TaskStatus::Pending,
        ];
    }
}
