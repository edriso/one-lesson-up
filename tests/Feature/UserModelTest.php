<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\CompletedLesson;
use App\Models\LearningActivity;
use App\Enums\ActivityType;

test('user can be created with all required fields', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'full_name' => 'Test User',
        'title' => 'Software Developer',
        'bio' => 'Test bio',
        'is_public' => true,
    ]);

    expect($user->username)->toBe('testuser');
    expect($user->email)->toBe('test@example.com');
    expect($user->full_name)->toBe('Test User');
    expect($user->title)->toBe('Software Developer');
    expect($user->bio)->toBe('Test bio');
    expect($user->is_public)->toBeTrue();
});

test('user display name returns full name when available', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'full_name' => 'Test User',
    ]);

    expect($user->display_name)->toBe('Test User');
});

test('user display name returns username when full name is not available', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'full_name' => null,
    ]);

    expect($user->display_name)->toBe('testuser');
});

test('user joined days ago is calculated correctly', function () {
    // Create user with specific created_at date
    $user = User::factory()->create([
        'created_at' => now()->subDays(5)
    ]);
    
    expect($user->joined_days_ago)->toBeGreaterThanOrEqual(4);
    expect($user->joined_days_ago)->toBeLessThanOrEqual(6);
});

test('user has relationships with other models', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create(['creator_id' => $user->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    expect($user->createdCourses()->count())->toBe(1);
    expect($user->enrollments()->count())->toBe(1);
    
    // Check if enrollment is active (not completed)
    expect($enrollment->completed_at)->toBeNull();
    
    // Refresh user to load relationships
    $user->refresh();
    $currentEnrollment = $user->currentEnrollment;
    
    // Debug: check what we're getting
    if ($currentEnrollment === null) {
        // Check if there are any enrollments for this user
        $allEnrollments = $user->enrollments()->get();
        expect($allEnrollments)->toHaveCount(1);
        expect($allEnrollments->first()->completed_at)->toBeNull();
    }
    
    expect($currentEnrollment)->toBeInstanceOf(Enrollment::class);
});

test('user learning activities relationship works', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    // Create learning activities
    LearningActivity::createLessonCompleted($user->id, $enrollment->id, $lesson->id);
    LearningActivity::createCourseStarted($user->id, $enrollment->id);
    
    expect($user->learningActivities()->count())->toBe(2);
});

test('user completed lessons relationship works', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    // Create completed lesson
    CompletedLesson::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    expect($user->completedLessons()->count())->toBe(1);
});

test('user learning calendar works correctly', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    // Create learning activities for current month
    $activity = LearningActivity::createLessonCompleted($user->id, $enrollment->id, $lesson->id);
    
    $currentYear = now()->year;
    $currentMonth = now()->month;
    $calendar = $user->getLearningCalendar($currentYear, $currentMonth);
    
    expect($calendar)->toBeArray();
    expect($calendar)->toHaveKey(now()->day);
    expect($calendar[now()->day]['has_activity'])->toBeTrue();
    expect($calendar[now()->day]['lessons_completed'])->toBe(1);
});


test('user points are updated when completing lessons', function () {
    $user = User::factory()->create(['points' => 0]);
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    $initialPoints = $user->points;
    
    // Complete a lesson
    CompletedLesson::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    $user->refresh();
    expect($user->points)->toBe($initialPoints + 1);
});

test('user can have multiple enrollments', function () {
    $user = User::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();
    
    $enrollment1 = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course1->id]);
    $enrollment2 = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course2->id]);
    
    expect($user->enrollments()->count())->toBe(2);
});

test('user current enrollment returns the most recent active enrollment', function () {
    $user = User::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();
    
    $enrollment1 = Enrollment::factory()->create([
        'user_id' => $user->id, 
        'course_id' => $course1->id,
        'created_at' => now()->subDay()
    ]);
    
    $enrollment2 = Enrollment::factory()->create([
        'user_id' => $user->id, 
        'course_id' => $course2->id,
        'created_at' => now()
    ]);
    
    expect($user->currentEnrollment->id)->toBe($enrollment2->id);
});
