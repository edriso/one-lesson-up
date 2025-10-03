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
            if ($enrollment->completed_at) {
                foreach ($lessons as $index => $lesson) {
                    // Create lessons with different dates, starting from enrollment date
                    $daysAgo = fake()->numberBetween(0, 30); // 0-30 days ago
                    $createdAt = $enrollment->created_at->addDays($index)->subDays($daysAgo);
                    
                    // Ensure the date is not in the future
                    $now = now();
                    if ($createdAt->isFuture()) {
                        $createdAt = $now->subDays(fake()->numberBetween(0, 7));
                    }
                    
                    \App\Models\CompletedLesson::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                        'summary' => fake()->paragraph(),
                        'link' => fake()->optional(0.3)->url(),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            } else {
                // If not completed, complete 20-80% of lessons
                $completionRate = fake()->numberBetween(20, 80);
                $lessonsToComplete = (int) ($lessons->count() * $completionRate / 100);
                $lessonsToComplete = max(1, $lessonsToComplete); // At least 1 lesson
                
                $selectedLessons = $lessons->take($lessonsToComplete);
                
                foreach ($selectedLessons as $index => $lesson) {
                    // Create lessons with different dates, starting from enrollment date
                    $daysAgo = fake()->numberBetween(0, 30); // 0-30 days ago
                    $createdAt = $enrollment->created_at->addDays($index)->subDays($daysAgo);
                    
                    // Ensure the date is not in the future
                    $now = now();
                    if ($createdAt->isFuture()) {
                        $createdAt = $now->subDays(fake()->numberBetween(0, 7));
                    }
                    
                    \App\Models\CompletedLesson::create([
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $lesson->id,
                        'summary' => fake()->paragraph(),
                        'link' => fake()->optional(0.3)->url(),
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }
        }
    }
}
