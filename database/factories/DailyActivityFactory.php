<?php

namespace Database\Factories;

use App\Models\DailyActivity;
use App\Models\User;
use App\Models\Enrollment;
use App\Enums\PointValue;
use App\Enums\TimeBonusType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DailyActivity>
 */
class DailyActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasTimeBonus = fake()->boolean(40); // 40% chance of time bonus
        $bonusType = $hasTimeBonus ? fake()->randomElement(TimeBonusType::cases()) : null;
        
        return [
            'user_id' => User::factory(),
            'enrollment_id' => Enrollment::factory(),
            'activity_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'lessons_completed' => fake()->numberBetween(1, 5),
            'time_bonus_earned' => $hasTimeBonus,
            'time_bonus_type' => $bonusType,
        ];
    }

    /**
     * Create an activity for today
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_date' => now()->format('Y-m-d'),
        ]);
    }

    /**
     * Create an activity for yesterday
     */
    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'activity_date' => now()->subDay()->format('Y-m-d'),
        ]);
    }

    /**
     * Create an activity with time bonus
     */
    public function withTimeBonus(TimeBonusType $type = null): static
    {
        return $this->state(fn (array $attributes) => [
            'time_bonus_earned' => true,
            'time_bonus_type' => $type ?? fake()->randomElement(TimeBonusType::cases()),
        ]);
    }

    /**
     * Create an activity without time bonus
     */
    public function withoutTimeBonus(): static
    {
        return $this->state(fn (array $attributes) => [
            'time_bonus_earned' => false,
            'time_bonus_type' => null,
        ]);
    }
}
