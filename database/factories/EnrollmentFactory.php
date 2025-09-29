<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Enrollment>
 */
class EnrollmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'course_id' => \App\Models\Course::factory(),
            'reflection' => fake()->optional(0.3)->paragraph(),
            'completed_at' => fake()->optional(0.2)->dateTimeBetween('-30 days', 'now'), // 20% chance of being completed
        ];
    }

    /**
     * Create a completed enrollment.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
            'reflection' => fake()->paragraph(),
        ]);
    }

    /**
     * Create an active (incomplete) enrollment.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
            'reflection' => null,
        ]);
    }
}
