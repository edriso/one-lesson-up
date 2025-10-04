<?php

namespace App\Enums;

enum PointSystemValue: int
{
    case LESSON_COMPLETED = 1;
    case COURSE_COMPLETED = 5; // Base points for completing a course
    case COURSE_ON_TIME_BONUS_MULTIPLIER = 50; // 50% bonus
    case COURSE_LATE_BONUS_MULTIPLIER = 25; // 25% bonus

    /**
     * Calculate course completion bonus points.
     */
    public static function calculateCourseBonus(int $lessonCount, bool $completedOnTime): int
    {
        $multiplier = $completedOnTime 
            ? self::COURSE_ON_TIME_BONUS_MULTIPLIER->value 
            : self::COURSE_LATE_BONUS_MULTIPLIER->value;
        
        // Calculate bonus as integer: for 50% bonus, give 1 point for every 2 lessons
        // For 25% bonus, give 1 point for every 4 lessons
        if ($multiplier == 50) {
            return (int) floor($lessonCount / 2);
        } elseif ($multiplier == 25) {
            return (int) floor($lessonCount / 4);
        }
        
        return 0;
    }

    /**
     * Calculate total points for a course completion.
     */
    public static function calculateTotalCoursePoints(int $lessonCount, bool $completedOnTime): int
    {
        $lessonPoints = $lessonCount * self::LESSON_COMPLETED->value;
        $bonusPoints = self::calculateCourseBonus($lessonCount, $completedOnTime);
        
        return $lessonPoints + $bonusPoints;
    }

    /**
     * Get human-readable description.
     */
    public function description(): string
    {
        return match($this) {
            self::LESSON_COMPLETED => 'Lesson completed',
            self::COURSE_COMPLETED => 'Course completed',
            self::COURSE_ON_TIME_BONUS_MULTIPLIER => 'Course completed on time (50% bonus)',
            self::COURSE_LATE_BONUS_MULTIPLIER => 'Course completed late (25% bonus)',
        };
    }

    /**
     * Get the bonus percentage as a formatted string.
     */
    public function percentage(): ?string
    {
        return match($this) {
            self::COURSE_ON_TIME_BONUS_MULTIPLIER => '50%',
            self::COURSE_LATE_BONUS_MULTIPLIER => '25%',
            default => null,
        };
    }
}