<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\ActivityType;
use App\Enums\PointValue;

class LearningActivity extends Model
{
    protected $fillable = [
        'user_id',
        'enrollment_id',
        'lesson_id',
        'activity_type',
        'points_earned',
    ];

    protected function casts(): array
    {
        return [
            'points_earned' => 'integer',
            'activity_type' => ActivityType::class,
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    // Scopes
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByActivityType($query, ActivityType $activityType)
    {
        return $query->where('activity_type', $activityType->value);
    }

    /**
     * Create a learning activity for lesson completion.
     */
    public static function createLessonCompleted(int $userId, int $enrollmentId, int $lessonId): self
    {
        return self::create([
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'lesson_id' => $lessonId,
            'activity_type' => ActivityType::LESSON_COMPLETED,
            'points_earned' => PointValue::LESSON_COMPLETED->value,
        ]);
    }

    /**
     * Create a learning activity for course start.
     */
    public static function createCourseStarted(int $userId, int $enrollmentId): self
    {
        return self::create([
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'lesson_id' => null,
            'activity_type' => ActivityType::COURSE_STARTED,
            'points_earned' => null, // No points for course start
        ]);
    }

    /**
     * Create a learning activity for course completion.
     */
    public static function createCourseCompleted(int $userId, int $enrollmentId, float $bonusPoints = 0): self
    {
        return self::create([
            'user_id' => $userId,
            'enrollment_id' => $enrollmentId,
            'lesson_id' => null,
            'activity_type' => ActivityType::COURSE_COMPLETED,
            'points_earned' => (int) $bonusPoints,
        ]);
    }
}
