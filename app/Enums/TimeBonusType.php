<?php

namespace App\Enums;

enum TimeBonusType: string
{
    case MORNING = 'morning';
    case EVENING = 'evening';

    /**
     * Get all available time bonus types.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the display name for the time bonus type.
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::MORNING => 'Morning',
            self::EVENING => 'Evening',
        };
    }

    /**
     * Get the description for the time bonus type.
     */
    public function getDescription(): string
    {
        return match($this) {
            self::MORNING => 'Early morning learning bonus (5 AM - 8 AM)',
            self::EVENING => 'Evening learning bonus (8 PM - 11 PM)',
        };
    }

    /**
     * Check if the current time falls within this bonus window.
     */
    public function isInTimeWindow(\DateTime $dateTime, string $timezone = 'UTC'): bool
    {
        $userTime = clone $dateTime;
        $userTime->setTimezone(new \DateTimeZone($timezone));
        $hour = (int) $userTime->format('H');

        return match($this) {
            self::MORNING => $hour >= 5 && $hour < 8,
            self::EVENING => $hour >= 20 && $hour < 23,
        };
    }

    /**
     * Get the time bonus type based on current time and timezone.
     */
    public static function fromTime(\DateTime $dateTime, string $timezone = 'UTC'): ?self
    {
        $userTime = clone $dateTime;
        $userTime->setTimezone(new \DateTimeZone($timezone));
        $hour = (int) $userTime->format('H');

        if ($hour >= 5 && $hour < 8) {
            return self::MORNING;
        }

        if ($hour >= 20 && $hour < 23) {
            return self::EVENING;
        }

        return null;
    }
}
