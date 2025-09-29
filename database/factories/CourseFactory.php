<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional(0.8)->paragraph(),
            'link' => fake()->url(),
            'creator_id' => \App\Models\User::factory(),
            'is_active' => fake()->boolean(90), // 90% chance of being active
            'is_featured' => fake()->boolean(20), // 20% chance of being featured
        ];
    }

    /**
     * Create a featured course.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive course.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'is_featured' => false,
        ]);
    }
}
