<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompletedLesson>
 */
class CompletedLessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'enrollment_id' => \App\Models\Enrollment::factory(),
            'lesson_id' => \App\Models\Lesson::factory(),
            'summary' => fake()->paragraph(),
            'link' => fake()->optional(0.4)->url(),
        ];
    }

    /**
     * Create a completed lesson with a link.
     */
    public function withLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => fake()->url(),
        ]);
    }

    /**
     * Create a completed lesson without a link.
     */
    public function withoutLink(): static
    {
        return $this->state(fn (array $attributes) => [
            'link' => null,
        ]);
    }
}
