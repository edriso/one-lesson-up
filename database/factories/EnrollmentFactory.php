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
            'bonus_deadline' => $this->faker->dateTimeBetween('+1 week', '+1 month'),
            'completed_at' => null, // Active enrollment by default
            'course_reflection' => null,
        ];
    }

    /**
     * Create a completed enrollment.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
            'course_reflection' => fake()->paragraph(),
        ]);
    }

    /**
     * Create an active (incomplete) enrollment.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => null,
            'course_reflection' => null,
        ]);
    }
}
