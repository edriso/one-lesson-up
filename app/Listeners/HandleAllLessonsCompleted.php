<?php

namespace App\Listeners;

use App\Events\AllLessonsCompleted;
use App\Models\LearningActivity;
use App\Enums\ActivityType;

class HandleAllLessonsCompleted
{
    /**
     * Handle the event.
     */
    public function handle(AllLessonsCompleted $event): void
    {
        $enrollment = $event->enrollment;
        
        // Create learning activity for completing all lessons
        LearningActivity::create([
            'user_id' => $enrollment->user_id,
            'enrollment_id' => $enrollment->id,
            'activity_type' => ActivityType::LESSONS_COMPLETED,
            'description' => "Completed all lessons in course: {$enrollment->course->name}",
            'points_earned' => 0, // No points for this activity, points are awarded per lesson
        ]);
    }
}

