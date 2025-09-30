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
            'completed_at' => null, // Active enrollment by default
            'reflection' => null,
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
