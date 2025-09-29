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
        'profile_picture_url',
        'title',
        'bio',
        'linkedin_url',
        'website_url',
        'is_public',
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
     * Get user's latest completed enrollment.
     */
    public function latestCompletedEnrollment()
    {
        return $this->hasOne(Enrollment::class, 'enrollment_id')->where('completed_at')->latest();
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

    public function learningActivities()
    {
        return $this->hasMany(LearningActivity::class);
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
     * Get user's current enrollment (most recent active).
     */
    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)->whereNull('completed_at')->latest();
    }

    /**
     * Get user's learning statistics.
     */
    public function getLearningStatsAttribute(): array
    {
        $totalLessons = $this->completedLessons()->count();
        $totalCourses = $this->enrollments()->whereNotNull('completed_at')->count();
        $totalPoints = $this->points;

        // Calculate average lessons per day
        $firstActivity = $this->learningActivities()
            ->where('activity_type', \App\Enums\ActivityType::LESSON_COMPLETED->value)
            ->orderBy('created_at', 'asc')
            ->first();

        $daysActive = 0;
        if ($firstActivity) {
            $daysActive = $firstActivity->created_at->diffInDays(now()) + 1;
        }

        $avgLessonsPerDay = $daysActive > 0 ? round($totalLessons / $daysActive, 2) : 0;

        return [
            'total_lessons' => $totalLessons,
            'total_courses' => $totalCourses,
            'total_points' => $totalPoints,
            'days_active' => $daysActive,
            'avg_lessons_per_day' => $avgLessonsPerDay,
        ];
    }

    /**
     * Get user's learning streak (consecutive days with activity).
     */
    public function getLearningStreakAttribute(): int
    {
        $activities = $this->learningActivities()
            ->where('activity_type', \App\Enums\ActivityType::LESSON_COMPLETED->value)
            ->orderBy('created_at', 'desc')
            ->get();

        $streak = 0;
        $currentDate = now()->startOfDay();

        foreach ($activities as $activity) {
            $activityDate = $activity->created_at->startOfDay();
            $daysDiff = $currentDate->diffInDays($activityDate);

            if ($daysDiff === $streak) {
                $streak++;
                $currentDate = $activityDate;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get user's learning calendar data for a specific month.
     */
    public function getLearningCalendar(int $year, int $month): array
    {
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $activities = $this->learningActivities()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('activity_type', \App\Enums\ActivityType::LESSON_COMPLETED->value)
            ->get()
            ->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            });

        $calendar = [];
        for ($day = 1; $day <= $endDate->day; $day++) {
            $date = $startDate->copy()->setDay($day);
            $dateKey = $date->format('Y-m-d');
            
            $calendar[$day] = [
                'date' => $date,
                'has_activity' => $activities->has($dateKey),
                'lessons_completed' => $activities->get($dateKey, collect())->count(),
            ];
        }

        return $calendar;
    }
}
