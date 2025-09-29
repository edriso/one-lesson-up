<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'courses_count' => 'integer',
        ];
    }

    /**
     * Get all courses with this tag.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'course_tags');
    }

    /**
     * Get the courses count for the tag.
     */
    public function getCoursesCountAttribute(): int
    {
        return $this->courses()->count();
    }
}
