<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'full_name' => fake()->optional(0.7)->name(),
            'profile_picture_url' => fake()->optional(0.3)->imageUrl(200, 200, 'people'),
            'title' => fake()->optional(0.5)->jobTitle(),
            'bio' => fake()->optional(0.6)->paragraph(),
            'linkedin_url' => fake()->optional(0.4)->url(),
            'website_url' => fake()->optional(0.3)->url(),
            'is_public' => fake()->boolean(80), // 80% chance of being public
            'is_active' => fake()->boolean(95), // 95% chance of being active
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
