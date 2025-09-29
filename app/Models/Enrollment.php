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
}
