<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = \App\Models\User::all();
        $courses = \App\Models\Course::where('is_active', true)->get();

        // Create enrollments for users
        foreach ($users as $user) {
            // Each user enrolls in 1-3 courses
            $enrollmentCount = fake()->numberBetween(1, 3);
            $selectedCourses = $courses->random($enrollmentCount);
            
            foreach ($selectedCourses as $course) {
                $isCompleted = fake()->boolean(20); // 20% chance of completion
                
                \App\Models\Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'reflection' => $isCompleted ? fake()->paragraph() : null,
                    'is_completed' => $isCompleted,
                ]);
            }
        }

        // Create some additional random enrollments
        \App\Models\Enrollment::factory()
            ->count(30)
            ->create()
            ->each(function ($enrollment) use ($users, $courses) {
                $enrollment->update([
                    'user_id' => $users->random()->id,
                    'course_id' => $courses->random()->id,
                ]);
            });
    }
}
