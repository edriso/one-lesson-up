<?php

namespace App\Enums;

enum ActivityType: string
{
    case LESSON_COMPLETED = 'lesson_completed';
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
}
