<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'course_reflection',
        'course_reflection_link',
        'completed_at',
        'bonus_deadline',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'bonus_deadline' => 'date',
        ];
    }

    /**
     * Get the user that owns the enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course for the enrollment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get all completed lessons for this enrollment.
     */
    public function completedLessons(): HasMany
    {
        return $this->hasMany(CompletedLesson::class);
    }

    /**
     * Get all daily activities for this enrollment.
     */
    public function dailyActivities(): HasMany
    {
        return $this->hasMany(DailyActivity::class);
    }

    /**
     * Get the number of active days for this enrollment.
     * This represents "engagement days" - days the user worked on this specific course.
     */
    public function getActiveDaysCount(): int
    {
        return $this->dailyActivities()->where('lessons_completed', '>', 0)->count();
    }

    /**
     * Get the current module progress percentage.
     */
    public function getCurrentModuleProgressPercentageAttribute(): int
    {
        $totalLessons = $this->course->lessons_count;
        $completedLessons = $this->completedLessons()->count();
        
        if ($totalLessons === 0) {
            return 0;
        }
        
        return (int) round(($completedLessons / $totalLessons) * 100);
    }

    /**
     * Get the course deadline date.
     */
    public function getDeadlineDateAttribute(): Carbon
    {
        return $this->created_at->addDays($this->course->deadline_days);
    }

    /**
     * Check if the enrollment is completed on time.
     */
    public function isCompletedOnTime(): bool
    {
        return $this->updated_at->lte($this->deadline_date) && $this->completed_at !== null;
    }

    /**
     * Check if the enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completedLessons()->count() === $this->course->lessons_count;
    }

    /**
     * Check if all lessons are completed.
     */
    public function areAllLessonsCompleted(): bool
    {
        return $this->completedLessons()->count() === $this->course->lessons_count;
    }

    /**
     * Check if the enrollment is fully completed (all lessons + reflection).
     */
    public function isFullyCompleted(): bool
    {
        return $this->areAllLessonsCompleted() && 
               $this->completed_at !== null && 
               $this->course_reflection !== null;
    }

    /**
     * Complete the enrollment with reflection.
     */
    public function completeWithReflection(string $reflection, ?string $reflectionLink = null): bool
    {
        if (!$this->areAllLessonsCompleted()) {
            return false; // Cannot complete without all lessons done
        }

        if ($this->completed_at !== null && $this->course_reflection !== null) {
            return false; // Already completed with reflection
        }

        $this->update([
            'completed_at' => now(),
            'course_reflection' => $reflection,
            'course_reflection_link' => $reflectionLink,
        ]);

        // Award course completion bonus
        $this->awardCourseCompletionBonus();
        
        // Clear user's enrollment_id since course is completed
        $this->user->update(['enrollment_id' => null]);

        return true;
    }

    /**
     * Award course completion bonus points.
     */
    private function awardCourseCompletionBonus(): void
    {
        // Calculate active days from daily activities
        $activeDays = $this->dailyActivities()->count();
        
        $courseBonus = \App\Enums\PointValue::calculateCompletionBonus(
            $activeDays, 
            $this->isCompletedOnTime()
        );

        if ($courseBonus > 0) {
            $this->user->increment('points', $courseBonus);
            
            // Course completion bonus is already part of user's total points
            // Daily activity tracking happens automatically via lesson completion
            // No need to create separate activity for course completion bonus
        }
    }
}
