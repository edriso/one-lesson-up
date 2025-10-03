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
                // Check if enrollment already exists
                if (\App\Models\Enrollment::where('user_id', $user->id)
                    ->where('course_id', $course->id)
                    ->exists()) {
                    continue;
                }
                
                $isCompleted = fake()->boolean(20); // 20% chance of completion
                
                \App\Models\Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'course_reflection' => $isCompleted ? fake()->paragraph() : null,
                    'course_reflection_link' => $isCompleted ? fake()->optional(0.3)->url() : null,
                    'completed_at' => $isCompleted ? now() : null,
                    'bonus_deadline' => $course->smart_deadline, // Learning deadline based on lesson count
                ]);
            }
        }

        // Create some additional random enrollments (avoiding duplicates)
        $existingEnrollments = \App\Models\Enrollment::all()->map(function ($enrollment) {
            return $enrollment->user_id . '-' . $enrollment->course_id;
        })->toArray();
        
        $attempts = 0;
        $maxAttempts = 30;
        
        while ($attempts < $maxAttempts) {
            $user = $users->random();
            $course = $courses->random();
            $enrollmentKey = $user->id . '-' . $course->id;
            
            if (!in_array($enrollmentKey, $existingEnrollments)) {
                \App\Models\Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $course->id,
                    'bonus_deadline' => $course->smart_deadline,
                ]);
                $existingEnrollments[] = $enrollmentKey;
            }
            
            $attempts++;
        }
    }
}
