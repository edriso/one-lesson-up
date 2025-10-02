<?php

namespace App\Models;

use App\Enums\PointValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        // Record daily activity and award points when a lesson is completed
        static::created(function ($completedLesson) {
            // Skip during seeding to avoid unique constraint violations
            if (app()->runningInConsole() && app()->environment('local')) {
                return;
            }
            
            $user = $completedLesson->enrollment->user;
            
            // Record daily activity and get points awarded
            [$dailyActivity, $pointsAwarded] = DailyActivity::recordActivity(
                $user->id,
                $completedLesson->enrollment_id,
                $user->timezone ?? 'UTC'
            );
            
            // Award points to user
            if ($pointsAwarded > 0) {
                $user->increment('points', $pointsAwarded);
            }
            
            // Check if this was the last lesson of the course
            $completedLesson->checkCourseCompletion();
        });

        // Note: We don't handle deletion point removal here anymore
        // since daily activities track the overall daily progress
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
