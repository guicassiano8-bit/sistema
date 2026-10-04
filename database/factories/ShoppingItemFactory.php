<?php

namespace Database\Factories;

use App\Enums\ShoppingCategory;
use App\Enums\ShoppingStatus;
use App\Models\ShoppingItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingItem>
 */
class ShoppingItemFactory extends Factory
{
    protected $model = ShoppingItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'category' => ShoppingCategory::Daily,
            'quantity' => 1,
            'status' => ShoppingStatus::Pending,
        ];
    }

    public function urgent(): static
    {
        return $this->state(['category' => ShoppingCategory::Urgent]);
    }

    public function purchased(): static
    {
        return $this->state([
            'status' => ShoppingStatus::Purchased,
            'purchased_at' => now(),
        ]);
    }
}
