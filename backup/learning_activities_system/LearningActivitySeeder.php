<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\PointSystemValue;
use App\Models\CompletedLesson;
use App\Models\LearningActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LearningActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all completed lessons to create learning activities for them
        $completedLessons = CompletedLesson::with(['enrollment', 'lesson'])->get();

        foreach ($completedLessons as $completedLesson) {
            // Create a learning activity for the lesson completion
            LearningActivity::create([
                'user_id' => $completedLesson->enrollment->user_id,
                'enrollment_id' => $completedLesson->enrollment_id,
                'lesson_id' => $completedLesson->lesson_id,
                'activity_type' => ActivityType::LESSON_COMPLETED,
                'points_earned' => PointSystemValue::LESSON_COMPLETED->value,
                'created_at' => $completedLesson->created_at,
                'updated_at' => $completedLesson->updated_at,
            ]);
        }

        // Add some additional activities for variety across different time periods
        $users = User::limit(10)->get();
        
        // Add some activities for today
        foreach ($users->take(5) as $user) {
            $enrollment = $user->enrollments->first();
            if ($enrollment) {
                for ($i = 0; $i < rand(1, 3); $i++) {
                    LearningActivity::create([
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $enrollment->course->modules->first()?->lessons->first()?->id,
                        'activity_type' => ActivityType::LESSON_COMPLETED,
                        'points_earned' => PointSystemValue::LESSON_COMPLETED->value,
                        'created_at' => Carbon::today()->addHours(rand(8, 20)),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }

        // Add some activities for yesterday
        foreach ($users->take(7) as $user) {
            $enrollment = $user->enrollments->first();
            if ($enrollment) {
                for ($i = 0; $i < rand(1, 4); $i++) {
                    LearningActivity::create([
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $enrollment->course->modules->first()?->lessons->first()?->id,
                        'activity_type' => ActivityType::LESSON_COMPLETED,
                        'points_earned' => PointSystemValue::LESSON_COMPLETED->value,
                        'created_at' => Carbon::yesterday()->addHours(rand(8, 20)),
                        'updated_at' => Carbon::yesterday()->addHours(rand(8, 20)),
                    ]);
                }
            }
        }

        // Add some activities spread throughout this month
        foreach ($users as $user) {
            $enrollment = $user->enrollments->first();
            if ($enrollment) {
                $activitiesCount = rand(5, 15);
                for ($i = 0; $i < $activitiesCount; $i++) {
                    $randomDate = Carbon::now()->startOfMonth()->addDays(rand(0, Carbon::now()->day - 1));
                    LearningActivity::create([
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'lesson_id' => $enrollment->course->modules->first()?->lessons->first()?->id,
                        'activity_type' => ActivityType::LESSON_COMPLETED,
                        'points_earned' => PointSystemValue::LESSON_COMPLETED->value,
                        'created_at' => $randomDate->addHours(rand(8, 20)),
                        'updated_at' => $randomDate->addHours(rand(8, 20)),
                    ]);
                }
            }
        }

        $this->command->info('Learning activities seeded successfully!');
    }
}