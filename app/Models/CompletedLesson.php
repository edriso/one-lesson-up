<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\PointSystemValue;

class CompletedLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'summary',
        'link',
    ];

    /**
     * Boot the model and register event listeners.
     */
    protected static function booted()
    {
        // Award 1 point when a lesson is completed
        static::created(function ($completedLesson) {
            $pointsEarned = PointSystemValue::LESSON_COMPLETED->value;
            $completedLesson->enrollment->user->increment('points', $pointsEarned);

            // Create learning activity for tracking
            \App\Models\LearningActivity::create([
                'user_id' => $completedLesson->enrollment->user_id,
                'enrollment_id' => $completedLesson->enrollment_id,
                'lesson_id' => $completedLesson->lesson_id,
                'activity_type' => \App\Enums\ActivityType::LESSON_COMPLETED,
                'points_earned' => $pointsEarned, // Track points for leaderboard
            ]);
            
            // Check if this was the last lesson of the course
            $completedLesson->checkCourseCompletion();
        });

        // Remove points when a lesson completion is deleted
        static::deleted(function ($completedLesson) {
            $pointsToRemove = PointSystemValue::LESSON_COMPLETED->value;
            $completedLesson->enrollment->user->decrement('points', $pointsToRemove);
        });
    }

    /**
     * Get the enrollment that owns the completed lesson.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the lesson that was completed.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Check if the lesson is the last lesson of the course.
     */
    public function isLastLesson(): bool
    {
        $totalLessons = $this->enrollment->course->lessons_count;
        $completedLessons = $this->enrollment->completedLessons()->count();
        
        return $completedLessons === $totalLessons;
    }

    /**
     * Check if the course is completed and award bonus points.
     */
    public function checkCourseCompletion(): void
    {
        $enrollment = $this->enrollment;
        $course = $enrollment->course;
        $totalLessons = $course->lessons_count;
        $completedLessons = $enrollment->completedLessons()->count();

        // Check if all lessons are completed and enrollment not yet marked as complete
        if ($completedLessons === $totalLessons && !$enrollment->completed_at) {
            // Note: We don't auto-complete the enrollment here anymore
            // Completion now requires manual action via UI
            // The completion will be triggered when user clicks "Complete Course"
        }
    }
}
