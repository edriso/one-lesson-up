<?php

namespace App\Enums;

enum PointThreshold: int
{
    case PROFILE_PICTURE_UNLOCK = 100;    
    case CUSTOM_TITLE = 100;
    case LEADERBOARD_VISIBILITY = 10;
    
    public function description(): string
    {
        return match($this) {
            self::PROFILE_PICTURE_UNLOCK => 'Upload custom profile picture',
            self::CUSTOM_TITLE => 'Set custom profile title',
            self::LEADERBOARD_VISIBILITY => 'Appear on leaderboard',
        };
    }
    
    public function isUnlocked(int $userPoints): bool
    {
        return $userPoints >= $this->value;
    }
}