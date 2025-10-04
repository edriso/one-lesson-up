<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\CompletedLesson;
use App\Models\DailyActivity;

test('user can be created with all required fields', function () {
    $user = User::factory()->create([
        'username' => 'testuser',
        'email' => 'test@example.com',
        'full_name' => 'Test User',
        'bio' => 'Test bio',
        'is_public' => true,
    ]);

    expect($user->username)->toBe('testuser');
    expect($user->email)->toBe('test@example.com');
    expect($user->full_name)->toBe('Test User');
    expect($user->bio)->toBe('Test bio');
    expect($user->is_public)->toBeTrue();
});

test('user display name returns full name when available', function () {
    $user = User::factory()->create(['full_name' => 'John Doe']);
    expect($user->display_name)->toBe('John Doe');
});

test('user display name returns username when full name is not available', function () {
    $user = User::factory()->create(['username' => 'johndoe', 'full_name' => null]);
    expect($user->display_name)->toBe('johndoe');
});

test('user joined days ago is calculated correctly', function () {
    $user = User::factory()->create(['created_at' => now()->subDays(5)]);
    expect((int) $user->joined_days_ago)->toBe(5);
});

test('user has relationships with other models', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    // Create daily activities by completing lessons
    CompletedLesson::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    expect($user->dailyActivities()->count())->toBe(1);
    expect($user->enrollments()->count())->toBe(1);
});

test('user daily activities relationship works', function () {
    $user = User::factory()->create();
    $course = Course::factory()->create();
    $module = Module::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['module_id' => $module->id]);
    $enrollment = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course->id]);
    
    // Create daily activity by completing lesson
    CompletedLesson::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    expect($user->dailyActivities()->count())->toBe(1);
    $activity = $user->dailyActivities()->first();
    expect($activity->lessons_completed)->toBe(1);
    expect($activity->enrollment_id)->toBe($enrollment->id);
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
    
    // Create daily activity by completing lesson
    CompletedLesson::create([
        'enrollment_id' => $enrollment->id,
        'lesson_id' => $lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    $calendar = $user->getLearningCalendar()->toArray();
    
    expect($calendar)->toBeArray();
    expect($calendar)->not->toBeEmpty();
    expect($calendar[0]['lessons_completed'])->toBe(1);
    expect($calendar[0]['courses'])->toHaveCount(1);
    expect($calendar[0]['courses'][0]['title'])->toBe($course->title);
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
    // User gets at least 1 point for active day, possibly 2 if in time bonus window
    expect($user->points)->toBeGreaterThanOrEqual($initialPoints + 1);
    expect($user->points)->toBeLessThanOrEqual($initialPoints + 2);
});

test('user can have multiple enrollments', function () {
    $user = User::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();
    
    $enrollment1 = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course1->id]);
    $enrollment2 = Enrollment::factory()->create(['user_id' => $user->id, 'course_id' => $course2->id]);
    
    expect($user->enrollments()->count())->toBe(2);
    expect($user->enrollments->contains($enrollment1))->toBeTrue();
    expect($user->enrollments->contains($enrollment2))->toBeTrue();
});