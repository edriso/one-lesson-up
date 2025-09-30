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
            $completedLesson->enrollment->user->increment('points');

            // Create learning activity
            \App\Models\LearningActivity::createLessonCompleted(
                $completedLesson->enrollment->user_id,
                $completedLesson->enrollment_id,
                $completedLesson->lesson_id
            );
            
            // Check if this was the last lesson of the course
            $completedLesson->checkCourseCompletion();
        });

        // Remove 1 point when a lesson completion is deleted
        static::deleted(function ($completedLesson) {
            $completedLesson->enrollment->user->decrement('points');
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
            // Mark enrollment as completed (completed_at is the completion date)
            $enrollment->update(['completed_at' => now()]);
            
            // Calculate and award bonus points
            $this->awardCourseCompletionBonus($enrollment, $course);
            
            // Clear user's enrollment_id since course is completed
            $enrollment->user->update(['enrollment_id' => null]);
        }
    }

    /**
     * Award bonus points for course completion.
     */
    private function awardCourseCompletionBonus(Enrollment $enrollment, Course $course): void
    {
        $user = $enrollment->user;
        $lessonCount = $course->lessons_count;
        $deadline = $course->deadline_days;
        
        // Check if completed within deadline
        // start_date = enrollment created_at, deadline calculated from there
        $deadlineDate = $enrollment->created_at->addDays($deadline);
        $isCompletedOnTime = now()->lte($deadlineDate);
        
        // Calculate bonus points using PointSystemValue enum
        $bonusPoints = PointSystemValue::calculateCourseBonus($lessonCount, $isCompletedOnTime);
        
        // Award bonus points (round to nearest integer)
        $user->increment('points', round($bonusPoints));
        
        // Create learning activity for course completion
        \App\Models\LearningActivity::createCourseCompleted(
            $user->id,
            $enrollment->id,
            $bonusPoints
        );
    }

}
