<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'avatar',
        'title',
        'bio',
        'website_url',
        'is_public',
        'enrollment_id',
        'timezone',
        'timezone_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_public' => 'boolean',
            'points' => 'integer',
            'timezone_updated_at' => 'datetime',
        ];
    }

    /**
     * Get all enrollments for the user.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get user's current enrollment (most recent active).
     */
    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)->whereNull('completed_at')->latest('created_at');
    }

    /**
     * Get user's active enrollment (via enrollment_id foreign key).
     */
    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    /**
     * Check if user can create a new course.
     * Users can only create/join if they don't have an active enrollment.
     * Active enrollment = enrollment_id is set (completed_at is NULL in that enrollment)
     */
    public function canCreateCourse(): bool
    {
        // If no enrollment_id, user can create/join
        return !$this->enrollment_id;
    }

    /**
     * Get user's latest completed enrollment (requires completed_at only).
     */
    public function latestCompletedEnrollment()
    {
        return $this->hasOne(Enrollment::class, 'user_id')->whereNotNull('completed_at')->latest();
    }

    /**
     * Get user's completed enrollments (optimized query).
     */
    public function completedEnrollments()
    {
        return $this->hasMany(Enrollment::class)->whereNotNull('completed_at');
    }

    /**
     * Get user's active enrollments (optimized using enrollment_id).
     */
    public function getActiveEnrollments()
    {
        if (!$this->enrollment_id) {
            return collect();
        }
        
        return collect([$this->enrollment]);
    }

    /**
     * Get all courses created by the user.
     */
    public function createdCourses()
    {
        return $this->hasMany(Course::class, 'creator_id');
    }

    /**
     * Get all completed lessons for the user.
     */
    public function completedLessons()
    {
        return $this->hasManyThrough(CompletedLesson::class, Enrollment::class);
    }

    /**
     * Get the user's display name (full_name or username).
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: $this->username;
    }

    /**
     * Get the user's joined days ago.
     */
    public function getJoinedDaysAgoAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    public function totalCompletedEnrollments()
    {
        return $this->hasMany(Enrollment::class)->where('is_completed', true);
    }

    /**
     * Get the calendar of user's learning activities with points and time bonus information.
     * Groups activities by date since there can be multiple enrollments per day.
     */
    public function getLearningCalendar()
    {
        $activities = $this->dailyActivities()
            ->with(['enrollment.course'])
            ->latest('activity_date')
            ->get()
            ->groupBy(function ($activity) {
                return $activity->activity_date->format('Y-m-d');
            })
            ->map(function ($dayActivities, $date) {
                $totalLessons = $dayActivities->sum('lessons_completed');
                $hasTimeBonus = $dayActivities->where('time_bonus_earned', true)->isNotEmpty();
                $timeBonusType = $dayActivities->where('time_bonus_earned', true)->first()?->time_bonus_type;
                
                // Calculate points: 1 for active day + 1 for time bonus if earned
                $pointsEarned = ($totalLessons > 0 ? 1 : 0) + ($hasTimeBonus ? 1 : 0);
                
                return [
                    'date' => \Carbon\Carbon::parse($date),
                    'iso_date' => \Carbon\Carbon::parse($date)->toISOString(),
                    'lessons_completed' => $totalLessons,
                    'points_earned' => $pointsEarned,
                    'time_bonus_earned' => $hasTimeBonus,
                    'time_bonus_type' => $timeBonusType,
                    'courses' => $dayActivities->map(function ($activity) {
                        return [
                            'id' => $activity->enrollment->course->id,
                            'title' => $activity->enrollment->course->title,
                            'lessons_completed' => $activity->lessons_completed,
                        ];
                    })->values(),
                ];
            })
            ->values();

        return $activities;
    }

    /**
     * Check if user can update timezone (must wait 30 days)
     */
    public function canUpdateTimezone(): bool
    {
        if (!$this->timezone_updated_at) {
            return true; // First time setting timezone
        }

        return $this->timezone_updated_at->diffInDays(now()) >= 30;
    }

    /**
     * Update user timezone with timestamp
     */
    public function updateTimezone(string $timezone): bool
    {
        if (!$this->canUpdateTimezone()) {
            return false;
        }

        return $this->update([
            'timezone' => $timezone,
            'timezone_updated_at' => now(),
        ]);
    }

    /**
     * Get current date in user's timezone
     */
    public function getCurrentDateInTimezone(): \Carbon\Carbon
    {
        return now()->setTimezone($this->timezone ?? 'UTC');
    }

    /**
     * Get daily activities relationship
     */
    public function dailyActivities()
    {
        return $this->hasMany(DailyActivity::class);
    }

    /**
     * Get today's daily activities (in user's timezone) - can be multiple enrollments
     */
    public function getTodayActivities()
    {
        $todayInUserTz = $this->getCurrentDateInTimezone()->format('Y-m-d');
        
        return $this->dailyActivities()
            ->where('activity_date', $todayInUserTz)
            ->get();
    }

    /**
     * Check if user has any activity today
     */
    public function hasActivityToday(): bool
    {
        return $this->getTodayActivities()->isNotEmpty();
    }

    /**
     * Get total lessons completed today across all enrollments
     */
    public function getTodayLessonsCount(): int
    {
        return $this->getTodayActivities()->sum('lessons_completed');
    }

    /**
     * Check if user earned time bonus today
     */
    public function hasTimeBonusToday(): bool
    {
        return $this->getTodayActivities()->where('time_bonus_earned', true)->isNotEmpty();
    }
}
