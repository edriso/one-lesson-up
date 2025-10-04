<?php

namespace App\Models;

use App\Enums\PointValue;
use App\Enums\TimeBonusType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DailyActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'enrollment_id',
        'activity_date',
        'lessons_completed',
        'time_bonus_earned',
        'time_bonus_type',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'time_bonus_earned' => 'boolean',
            'lessons_completed' => 'integer',
            'time_bonus_type' => TimeBonusType::class,
        ];
    }

    /**
     * Get the user that owns the daily activity.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the enrollment for the daily activity.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Create or update daily activity for a user and specific enrollment
     * Returns: [activity, pointsAwarded]
     */
    public static function recordActivity(
        int $userId, 
        int $enrollmentId,
        string $timezone = 'UTC'
    ): array {
        $activityDate = now()->setTimezone($timezone)->format('Y-m-d');
        $pointsAwarded = 0;
        
        // Use database transaction to ensure consistency
        return DB::transaction(function () use ($userId, $enrollmentId, $activityDate, $timezone, &$pointsAwarded) {
            // Check if user already has ANY activity today (across all enrollments)
            $hasActivityToday = self::where('user_id', $userId)
                ->whereDate('activity_date', $activityDate)
                ->exists();
            
            // Check if user already earned time bonus today (across all enrollments) 
            $hasTimeBonusToday = self::where('user_id', $userId)
                ->whereDate('activity_date', $activityDate)
                ->where('time_bonus_earned', true)
                ->exists();
            
            // Find existing activity or create new one
            $activity = self::where('user_id', $userId)
                ->where('enrollment_id', $enrollmentId)
                ->whereDate('activity_date', $activityDate)
                ->first();
                
            if (!$activity) {
                // Create new activity
                $activity = self::create([
                    'user_id' => $userId,
                    'enrollment_id' => $enrollmentId,
                    'activity_date' => $activityDate,
                    'lessons_completed' => 0,
                    'time_bonus_earned' => false,
                    'time_bonus_type' => null,
                ]);
            }

            // Increment lessons completed for this enrollment
            $activity->increment('lessons_completed');

            // Award active day bonus (1 point) for the first lesson of the day
            if (!$hasActivityToday) {
                $pointsAwarded += PointValue::ACTIVE_DAY->getPoints();
            }

            // Check and award time bonus if not already earned today and in time window
            $currentTime = now();
            $timeBonusType = TimeBonusType::fromTime($currentTime, $timezone);
            
            if (!$hasTimeBonusToday && $timeBonusType) {
                // Update ALL activities for this user today to mark time bonus earned
                self::where('user_id', $userId)
                    ->whereDate('activity_date', $activityDate)
                    ->update([
                        'time_bonus_earned' => true,
                        'time_bonus_type' => $timeBonusType,
                    ]);
                
                $pointsAwarded += PointValue::TIME_BONUS->getPoints();
            }

            return [$activity, $pointsAwarded];
        });
    }

    /**
     * Get points earned from this daily activity
     */
    public function getPointsEarned(): int
    {
        $points = 0;
        
        // 1 point for active day bonus (first lesson of the day)
        $points += PointValue::ACTIVE_DAY->getPoints();
        
        // Time bonus point
        if ($this->time_bonus_earned) {
            $points += PointValue::TIME_BONUS->getPoints();
        }
        
        return $points;
    }

    /**
     * Get activity description
     */
    public function getActivityDescription(): string
    {
        $description = "Completed {$this->lessons_completed} lesson" . 
                      ($this->lessons_completed !== 1 ? 's' : '');
        
        if ($this->time_bonus_earned && $this->time_bonus_type) {
            $bonusTime = $this->time_bonus_type->getDisplayName();
            $description .= " with {$bonusTime} time bonus";
        }
        
        return $description;
    }
}
