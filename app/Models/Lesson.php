<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $fillable = [
        'module_id',
        'name',
        'description',
        'lesson_order',
    ];

    /**
     * Get the module that owns the lesson.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the course through the module.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id', 'id')
            ->through('module');
    }

    /**
     * Get all completed lessons for this lesson.
     */
    public function completedLessons(): HasMany
    {
        return $this->hasMany(CompletedLesson::class);
    }
}
