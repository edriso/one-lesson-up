<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\CompletedLesson;
use App\Models\DailyActivity;
use App\Enums\PointValue;

beforeEach(function () {
    $this->user = User::factory()->create(['points' => 0]);
    $this->course = Course::factory()->create();
    $this->module = Module::factory()->create(['course_id' => $this->course->id]);
    $this->lesson = Lesson::factory()->create(['module_id' => $this->module->id]);
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->user->id,
        'course_id' => $this->course->id,
    ]);
});

test('user earns points when completing a lesson', function () {
    // Create additional lessons so completing one doesn't complete the course
    $additionalLessons = Lesson::factory()->count(2)->create(['module_id' => $this->module->id]);
    
    $initialPoints = $this->user->points;
    
    $completedLesson = CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    $this->user->refresh();
    
    // User should get at least 1 point for active day
    // May get additional point for time bonus if in window
    $expectedMinPoints = $initialPoints + PointValue::ACTIVE_DAY->getPoints();
    expect($this->user->points)->toBeGreaterThanOrEqual($expectedMinPoints);
    
    // Check that daily activity was created
    $activity = DailyActivity::where('user_id', $this->user->id)->latest('activity_date')->first();
    expect($activity)->not->toBeNull();
    expect($activity->lessons_completed)->toBe(1);
});

test('daily activity is created when completing a lesson', function () {
    // Create additional lessons so completing one doesn't complete the course
    $additionalLessons = Lesson::factory()->count(2)->create(['module_id' => $this->module->id]);
    
    $initialActivityCount = DailyActivity::count();
    
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    expect(DailyActivity::count())->toBe($initialActivityCount + 1);
    
    $activity = DailyActivity::latest('activity_date')->first();
    expect($activity->lessons_completed)->toBe(1);
    expect($activity->user_id)->toBe($this->user->id);
    expect($activity->enrollment_id)->toBe($this->enrollment->id);
});

test('course completion awards bonus points on time', function () {
    $this->user->update(['points' => 0]);
    
    // Complete the lesson
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    // Complete the course with reflection (on time)
    $success = $this->enrollment->completeWithReflection('Test reflection');
    
    expect($success)->toBeTrue();
    
    $this->user->refresh();
    $expectedPoints = PointValue::ACTIVE_DAY->getPoints() + PointValue::calculateCompletionBonus(1, true);
    expect($this->user->points)->toBe($expectedPoints);
});

test('multiple enrollments same day awards single active day point', function () {
    // Create second course and enrollment
    $secondCourse = Course::factory()->create();
    $secondModule = Module::factory()->create(['course_id' => $secondCourse->id]);
    $secondLesson = Lesson::factory()->create(['module_id' => $secondModule->id]);
    $secondEnrollment = Enrollment::factory()->create([
        'user_id' => $this->user->id,
        'course_id' => $secondCourse->id,
    ]);

    $this->user->update(['points' => 0]);

    // Complete lesson in first course
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion 1',
    ]);

    $pointsAfterFirst = $this->user->fresh()->points;

    // Complete lesson in second course on same day
    CompletedLesson::create([
        'enrollment_id' => $secondEnrollment->id,
        'lesson_id' => $secondLesson->id,
        'summary' => 'Test lesson completion 2',
    ]);

    $this->user->refresh();
    
    // Second lesson should not award any additional points since user already active today
    expect($this->user->points)->toBe($pointsAfterFirst);
    
    // But should have 2 separate daily activities
    $activities = DailyActivity::where('user_id', $this->user->id)->get();
    expect($activities->count())->toBe(2);
    expect($activities->sum('lessons_completed'))->toBe(2);
});

test('enrollment active days count works correctly', function () {
    // Complete lessons on different days for this enrollment
    
    // Day 1
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Day 1 lesson',
    ]);
    
    // Create activity for day 2 (simulate different day)
    DailyActivity::create([
        'user_id' => $this->user->id,
        'enrollment_id' => $this->enrollment->id,
        'activity_date' => now()->subDay()->format('Y-m-d'),
        'lessons_completed' => 2,
        'time_bonus_earned' => false,
    ]);
    
    expect($this->enrollment->active_days_count)->toBe(2);
});
