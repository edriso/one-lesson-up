<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $tags = \App\Models\Tag::all();

        // Create featured courses with different creation dates
        \App\Models\Course::factory()
            ->featured()
            ->count(5)
            ->create()
            ->each(function ($course) use ($users, $tags) {
                // Set different creation dates - some older, some newer
                $daysAgo = fake()->numberBetween(1, 30); // 1-30 days ago
                $course->update([
                    'creator_id' => $users->random()->id,
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);
                
                // Attach 2-4 random tags to each course
                $course->tags()->attach(
                    $tags->random(fake()->numberBetween(2, 4))->pluck('id')
                );
            });

        // Create regular courses with different creation dates
        \App\Models\Course::factory()
            ->count(15)
            ->create()
            ->each(function ($course) use ($users, $tags) {
                // Set different creation dates - mix of recent and older courses
                $daysAgo = fake()->numberBetween(1, 60); // 1-60 days ago
                $course->update([
                    'creator_id' => $users->random()->id,
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);
                
                // Attach 1-3 random tags to each course
                $course->tags()->attach(
                    $tags->random(fake()->numberBetween(1, 3))->pluck('id')
                );
            });

        // Create some inactive courses with different creation dates
        \App\Models\Course::factory()
            ->inactive()
            ->count(3)
            ->create()
            ->each(function ($course) use ($users) {
                // Set different creation dates for inactive courses
                $daysAgo = fake()->numberBetween(30, 90); // 30-90 days ago (older courses)
                $course->update([
                    'creator_id' => $users->random()->id,
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);
            });
    }
}
