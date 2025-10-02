<?php

namespace App\Enums;

enum ActivityType: string
{
    case LESSON_COMPLETED = 'lesson_completed';
    case LESSONS_COMPLETED = 'lessons_completed';
    case COURSE_STARTED = 'course_started';
    case COURSE_COMPLETED = 'course_completed';

    /**
     * Get all activity type values.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get activity type by value.
     */
    public static function fromValue(string $value): self
    {
        return self::from($value);
    }

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        return match($this) {
            self::LESSON_COMPLETED => 'Completed a lesson',
            self::LESSONS_COMPLETED => 'Completed all lessons',
            self::COURSE_STARTED => 'Started a new course',
            self::COURSE_COMPLETED => 'Completed a course',
        };
    }
}
