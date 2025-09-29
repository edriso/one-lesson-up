<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompletedLessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enrollments = \App\Models\Enrollment::all();

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->course;
            $lessons = $course->lessons;
            
            // If enrollment is completed, complete all lessons
            if ($enrollment->is_completed) {
                foreach ($lessons as $lesson) {
                    \App\Models\CompletedLesson::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                        'summary' => fake()->paragraph(),
                        'link' => fake()->optional(0.3)->url(),
                    ]);
                }
            } else {
                // If not completed, complete 20-80% of lessons
                $completionRate = fake()->numberBetween(20, 80);
                $lessonsToComplete = (int) ($lessons->count() * $completionRate / 100);
                $lessonsToComplete = max(1, $lessonsToComplete); // At least 1 lesson
                
                $selectedLessons = $lessons->take($lessonsToComplete);
                
                foreach ($selectedLessons as $lesson) {
                    \App\Models\CompletedLesson::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                        'summary' => fake()->paragraph(),
                        'link' => fake()->optional(0.3)->url(),
                    ]);
                }
            }
        }
    }
}
