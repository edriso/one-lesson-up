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
        'reflection',
        'course_reflection',
        'completed_at',
        'reflection_completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'deadline_date' => 'datetime',
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
     * Get all learning activities for this enrollment.
     */
    public function learningActivities(): HasMany
    {
        return $this->hasMany(LearningActivity::class);
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
        return $this->updated_at->lte($this->deadline_date) && $this->is_completed;
    }

    /**
     * Check if the enrollment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completedLessons()->count() === $this->course->lessons_count;
    }

    /**
     * Check if all lessons are completed (eligible for reflection submission).
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
               $this->reflection !== null;
    }

    /**
     * Complete the enrollment with reflection.
     */
    public function completeWithReflection(string $reflection): bool
    {
        if (!$this->areAllLessonsCompleted()) {
            return false; // Cannot complete without all lessons done
        }

        if ($this->completed_at !== null) {
            return false; // Already completed
        }

        $this->update([
            'completed_at' => now(),
            'reflection' => $reflection,
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
        $courseBonus = \App\Enums\PointSystemValue::calculateCourseBonus(
            $this->course->lessons_count, 
            $this->isCompletedOnTime()
        );

        if ($courseBonus > 0) {
            $this->user->increment('points', $courseBonus);
            
            // Log the bonus activity
            \App\Models\LearningActivity::create([
                'user_id' => $this->user_id,
                'enrollment_id' => $this->id,
                'activity_type' => \App\Enums\ActivityType::COURSE_COMPLETED,
                'points_earned' => $courseBonus,
                'description' => "Completed course: {$this->course->name}",
            ]);
        }
    }
}
