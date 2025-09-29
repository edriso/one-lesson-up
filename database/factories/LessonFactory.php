<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'module_id' => \App\Models\Module::factory(),
            'name' => fake()->sentence(2),
            'description' => fake()->optional(0.7)->paragraph(),
            'lesson_order' => fake()->numberBetween(1, 20),
        ];
    }

    /**
     * Create a lesson with a specific order.
     */
    public function withOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'lesson_order' => $order,
        ]);
    }
}
