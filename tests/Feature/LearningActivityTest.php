<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\LearningActivity;
use App\Enums\ActivityType;
use App\Enums\PointSystemValue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->course = Course::factory()->create();
    $this->module = Module::factory()->create(['course_id' => $this->course->id]);
    $this->lesson = Lesson::factory()->create(['module_id' => $this->module->id]);
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->user->id,
        'course_id' => $this->course->id,
    ]);
});

test('learning activity can be created for lesson completion', function () {
    $activity = LearningActivity::createLessonCompleted(
        $this->user->id,
        $this->enrollment->id,
        $this->lesson->id
    );

    expect($activity)->toBeInstanceOf(LearningActivity::class);
    expect($activity->user_id)->toBe($this->user->id);
    expect($activity->enrollment_id)->toBe($this->enrollment->id);
    expect($activity->lesson_id)->toBe($this->lesson->id);
    expect($activity->activity_type)->toBe(ActivityType::LESSON_COMPLETED);
    expect($activity->points_earned)->toBe(PointSystemValue::LESSON_COMPLETED->value);
});

test('learning activity can be created for course start', function () {
    $activity = LearningActivity::createCourseStarted(
        $this->user->id,
        $this->enrollment->id
    );

    expect($activity)->toBeInstanceOf(LearningActivity::class);
    expect($activity->user_id)->toBe($this->user->id);
    expect($activity->enrollment_id)->toBe($this->enrollment->id);
    expect($activity->lesson_id)->toBeNull();
    expect($activity->activity_type)->toBe(ActivityType::COURSE_STARTED);
    expect($activity->points_earned)->toBeNull();
});

test('learning activity can be created for course completion', function () {
    $bonusPoints = 15.0;
    $activity = LearningActivity::createCourseCompleted(
        $this->user->id,
        $this->enrollment->id,
        $bonusPoints
    );

    expect($activity)->toBeInstanceOf(LearningActivity::class);
    expect($activity->user_id)->toBe($this->user->id);
    expect($activity->enrollment_id)->toBe($this->enrollment->id);
    expect($activity->lesson_id)->toBeNull();
    expect($activity->activity_type)->toBe(ActivityType::COURSE_COMPLETED);
    expect($activity->points_earned)->toBe((int) $bonusPoints);
});

test('learning activity belongs to user', function () {
    $activity = LearningActivity::createLessonCompleted(
        $this->user->id,
        $this->enrollment->id,
        $this->lesson->id
    );

    expect($activity->user)->toBeInstanceOf(User::class);
    expect($activity->user->id)->toBe($this->user->id);
});

test('learning activity belongs to enrollment', function () {
    $activity = LearningActivity::createLessonCompleted(
        $this->user->id,
        $this->enrollment->id,
        $this->lesson->id
    );

    expect($activity->enrollment)->toBeInstanceOf(Enrollment::class);
    expect($activity->enrollment->id)->toBe($this->enrollment->id);
});

test('learning activity belongs to lesson when applicable', function () {
    $activity = LearningActivity::createLessonCompleted(
        $this->user->id,
        $this->enrollment->id,
        $this->lesson->id
    );

    expect($activity->lesson)->toBeInstanceOf(Lesson::class);
    expect($activity->lesson->id)->toBe($this->lesson->id);
});

test('learning activity scopes work correctly', function () {
    // Create different types of activities
    LearningActivity::createLessonCompleted($this->user->id, $this->enrollment->id, $this->lesson->id);
    LearningActivity::createCourseStarted($this->user->id, $this->enrollment->id);
    LearningActivity::createCourseCompleted($this->user->id, $this->enrollment->id, 10.0);

    // Test scopeForUser
    $userActivities = LearningActivity::forUser($this->user->id)->get();
    expect($userActivities)->toHaveCount(3);

    // Test scopeByActivityType
    $lessonActivities = LearningActivity::byActivityType(ActivityType::LESSON_COMPLETED)->get();
    expect($lessonActivities)->toHaveCount(1);
    expect($lessonActivities->first()->activity_type)->toBe(ActivityType::LESSON_COMPLETED);

    $courseActivities = LearningActivity::byActivityType(ActivityType::COURSE_STARTED)->get();
    expect($courseActivities)->toHaveCount(1);
    expect($courseActivities->first()->activity_type)->toBe(ActivityType::COURSE_STARTED);
});

test('learning activity for date range works correctly', function () {
    $today = now();
    $yesterday = $today->copy()->subDay();
    
    // Create activities on different dates
    $activity1 = LearningActivity::createLessonCompleted($this->user->id, $this->enrollment->id, $this->lesson->id);
    $activity1->update(['created_at' => $yesterday]);
    
    $activity2 = LearningActivity::createCourseStarted($this->user->id, $this->enrollment->id);
    $activity2->update(['created_at' => $today]);

    // Test scopeForDateRange
    $recentActivities = LearningActivity::forDateRange($yesterday, $today)->get();
    expect($recentActivities)->toHaveCount(2);

    // Test scopeForDate
    $todayActivities = LearningActivity::forDate($today)->get();
    expect($todayActivities)->toHaveCount(1);
    expect($todayActivities->first()->id)->toBe($activity2->id);
});
