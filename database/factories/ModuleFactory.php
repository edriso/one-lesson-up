<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_order' => fake()->numberBetween(1, 10),
            'course_id' => \App\Models\Course::factory(),
            'name' => fake()->optional(0.8)->sentence(2),
            'description' => fake()->optional(0.6)->paragraph(),
        ];
    }

    /**
     * Create a module with a specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'module_order' => $order,
        ]);
    }
}
