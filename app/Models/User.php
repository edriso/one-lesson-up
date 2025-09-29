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
     * Get the user's current enrollment.
     */
    public function currentEnrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id');
    }

    /**
     * Get all enrollments for the user.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
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
}
