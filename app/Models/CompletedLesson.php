<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompletedLesson extends Model
{
    protected $fillable = [
        'enrollment_id',
        'lesson_id',
        'summary',
        'link',
    ];

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
        return $this->lesson->lesson_order === $this->course->lessons_count;
    }
}
