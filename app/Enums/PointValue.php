<?php

namespace App\Enums;

enum PointValue: int
{
    case ACTIVE_DAY = 1;
    case TIME_BONUS = 2; // Different enum value but same point value  
    case COMPLETION_BONUS_MULTIPLIER = 100; // 100% of active days

    /**
     * Get the actual point value for this enum case
     */
    public function getPoints(): int
    {
        return match($this) {
            self::ACTIVE_DAY => 1,
            self::TIME_BONUS => 1, // Both worth 1 point
            self::COMPLETION_BONUS_MULTIPLIER => 100,
        };
    }

    /**
     * Check if current time is in bonus window (user's timezone)
     */
    public static function isInTimeBonusWindow(\DateTime $timestamp, string $timezone): bool
    {
        $userDateTime = new \DateTime('now', new \DateTimeZone($timezone));
        $hour = (int) $userDateTime->format('G');
        
        // 5-8 AM OR 8-11 PM
        return ($hour >= 5 && $hour < 8) || ($hour >= 20 && $hour < 23);
    }

    /**
     * Get bonus type based on current time
     */
    public static function getBonusType(\DateTime $timestamp, string $timezone): ?string
    {
        $userDateTime = new \DateTime('now', new \DateTimeZone($timezone));
        $hour = (int) $userDateTime->format('G');
        
        if ($hour >= 5 && $hour < 8) {
            return 'morning';
        }
        
        if ($hour >= 20 && $hour < 23) {
            return 'evening';
        }
        
        return null;
    }

    /**
     * Calculate course completion bonus
     */
    public static function calculateCompletionBonus(
        int $activeDays,
        bool $completedOnTime
    ): int {
        if (!$completedOnTime) {
            return 0; // No bonus for late completion
        }
        
        return (int) round($activeDays * (self::COMPLETION_BONUS_MULTIPLIER->value / 100));
    }

    /**
     * Calculate time bonus penalty for late completion
     */
    public static function calculateTimeBonusPenalty(
        int $timeBonusesEarned,
        bool $completedOnTime
    ): int {
        if ($completedOnTime) {
            return 0; // No penalty
        }
        
        // Lose 50% of time bonuses if completed late
        return (int) floor($timeBonusesEarned * 0.5);
    }

    public function description(): string
    {
        return match($this) {
            self::ACTIVE_DAY => 'Active learning day',
            self::TIME_BONUS => 'Optimal time bonus (5-8 AM or 8-11 PM)',
            self::COMPLETION_BONUS_MULTIPLIER => 'Course completed on time (100% of active days)',
        };
    }
}