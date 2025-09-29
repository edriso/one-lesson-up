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

        // Create featured courses
        \App\Models\Course::factory()
            ->featured()
            ->count(5)
            ->create()
            ->each(function ($course) use ($users, $tags) {
                $course->update(['creator_id' => $users->random()->id]);
                
                // Attach 2-4 random tags to each course
                $course->tags()->attach(
                    $tags->random(fake()->numberBetween(2, 4))->pluck('id')
                );
            });

        // Create regular courses
        \App\Models\Course::factory()
            ->count(15)
            ->create()
            ->each(function ($course) use ($users, $tags) {
                $course->update(['creator_id' => $users->random()->id]);
                
                // Attach 1-3 random tags to each course
                $course->tags()->attach(
                    $tags->random(fake()->numberBetween(1, 3))->pluck('id')
                );
            });

        // Create some inactive courses
        \App\Models\Course::factory()
            ->inactive()
            ->count(3)
            ->create()
            ->each(function ($course) use ($users) {
                $course->update(['creator_id' => $users->random()->id]);
            });
    }
}
