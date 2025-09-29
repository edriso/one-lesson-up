<?php

namespace App\Models;

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
        // Award 1 point when a lesson is completed
        static::created(function ($completedLesson) {
            $completedLesson->enrollment->user->increment('points');
            
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
     * Get the user through the enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id')
            ->through('enrollment');
    }

    /**
     * Get the course through the enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'id')
            ->through('enrollment');
    }

    /**
     * Check if the lesson is the last lesson of the course.
     */
    public function isLastLesson(): bool
    {
        // return $this->lesson->lesson_order === $this->course->lessons_count;
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

        // Check if all lessons are completed
        if ($completedLessons === $totalLessons && !$enrollment->is_completed) {
            // Mark enrollment as completed
            $enrollment->update(['is_completed' => true]);
            
            // Calculate and award bonus points
            $this->awardCourseCompletionBonus($enrollment, $course);
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
        $deadlineDate = $enrollment->created_at->addDays($deadline);
        $isCompletedOnTime = now()->lte($deadlineDate);
        
        if ($isCompletedOnTime) {
            // On-time completion: 50% bonus
            $bonusPoints = $lessonCount * 0.5;
        } else {
            // Late completion: 25% bonus
            $bonusPoints = $lessonCount * 0.25;
        }
        
        // Award bonus points
        $user->increment('points', (int) $bonusPoints);
    }
}
