<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\CompletedLesson;
use App\Models\LearningActivity;
use App\Enums\ActivityType;
use App\Enums\PointSystemValue;

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
    $initialPoints = $this->user->points;
    
    $completedLesson = CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    $this->user->refresh();
    expect($this->user->points)->toBe($initialPoints + PointSystemValue::LESSON_COMPLETED->value);
});

test('learning activity is created when completing a lesson', function () {
    $initialActivityCount = LearningActivity::count();
    
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    expect(LearningActivity::count())->toBe($initialActivityCount + 1);
    
    $activity = LearningActivity::latest()->first();
    expect($activity->activity_type)->toBe(ActivityType::LESSON_COMPLETED);
    expect($activity->points_earned)->toBe(PointSystemValue::LESSON_COMPLETED->value);
});

test('user loses points when deleting a completed lesson', function () {
    // Complete a lesson first
    $completedLesson = CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);

    $this->user->refresh();
    $pointsAfterCompletion = $this->user->points;
    
    // Delete the completed lesson
    $completedLesson->delete();
    
    $this->user->refresh();
    expect($this->user->points)->toBe($pointsAfterCompletion - PointSystemValue::LESSON_COMPLETED->value);
});

test('course completion awards bonus points on time', function () {
    // Create a course with multiple lessons
    $lessons = Lesson::factory()->count(3)->create(['module_id' => $this->module->id]);
    
    // Complete all lessons
    foreach ($lessons as $lesson) {
        CompletedLesson::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson->id,
            'summary' => 'Test lesson completion',
        ]);
    }

    $this->user->refresh();
    
    // Calculate expected points: 3 lessons + bonus (on-time)
    $expectedBonus = PointSystemValue::calculateCourseBonus(3, true);
    $expectedTotal = (3 * PointSystemValue::LESSON_COMPLETED->value) + $expectedBonus;
    
    expect($this->user->points)->toBe((int) $expectedTotal);
});

test('course completion awards bonus points late', function () {
    // Create a course with multiple lessons
    $lessons = Lesson::factory()->count(3)->create(['module_id' => $this->module->id]);
    
    // Set enrollment to be past deadline
    $this->enrollment->update(['created_at' => now()->subDays(10)]);
    
    // Complete all lessons
    foreach ($lessons as $lesson) {
        CompletedLesson::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson->id,
            'summary' => 'Test lesson completion',
        ]);
    }

    $this->user->refresh();
    
    // Calculate expected points: 3 lessons + bonus (late)
    $expectedBonus = PointSystemValue::calculateCourseBonus(3, false);
    $expectedTotal = (3 * PointSystemValue::LESSON_COMPLETED->value) + $expectedBonus;
    
    expect($this->user->points)->toBe((int) $expectedTotal);
});

test('course completion creates learning activity', function () {
    // Create a course with multiple lessons
    $lessons = Lesson::factory()->count(2)->create(['module_id' => $this->module->id]);
    
    $initialActivityCount = LearningActivity::count();
    
    // Complete all lessons
    foreach ($lessons as $lesson) {
        CompletedLesson::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson->id,
            'summary' => 'Test lesson completion',
        ]);
    }

    // Should have created: 2 lesson activities + 1 course completion activity
    expect(LearningActivity::count())->toBe($initialActivityCount + 3);
    
    $courseCompletionActivity = LearningActivity::where('activity_type', ActivityType::COURSE_COMPLETED->value)->first();
    expect($courseCompletionActivity)->not->toBeNull();
    expect($courseCompletionActivity->points_earned)->toBeGreaterThan(0);
});

test('enrollment is marked as completed when all lessons are done', function () {
    // Create a course with multiple lessons
    $lessons = Lesson::factory()->count(2)->create(['module_id' => $this->module->id]);
    
    expect($this->enrollment->completed_at)->toBeNull();
    
    // Complete all lessons
    foreach ($lessons as $lesson) {
        CompletedLesson::create([
            'enrollment_id' => $this->enrollment->id,
            'lesson_id' => $lesson->id,
            'summary' => 'Test lesson completion',
        ]);
    }

    $this->enrollment->refresh();
    expect($this->enrollment->completed_at)->not->toBeNull();
});

test('user learning statistics are calculated correctly', function () {
    // Create some learning activities
    LearningActivity::createLessonCompleted($this->user->id, $this->enrollment->id, $this->lesson->id);
    LearningActivity::createCourseStarted($this->user->id, $this->enrollment->id);
    
    // Test learning statistics
    $stats = $this->user->learning_stats;
    
    expect($stats['total_lessons'])->toBe(1);
    expect($stats['total_points'])->toBe($this->user->points);
    expect($stats['days_active'])->toBeGreaterThan(0);
});

test('point system handles multiple courses correctly', function () {
    // Create another course and enrollment
    $course2 = Course::factory()->create();
    $module2 = Module::factory()->create(['course_id' => $course2->id]);
    $lesson2 = Lesson::factory()->create(['module_id' => $module2->id]);
    $enrollment2 = Enrollment::factory()->create([
        'user_id' => $this->user->id,
        'course_id' => $course2->id,
    ]);
    
    $initialPoints = $this->user->points;
    
    // Complete lessons in both courses
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson->id,
        'summary' => 'Test lesson completion',
    ]);
    
    CompletedLesson::create([
        'enrollment_id' => $enrollment2->id,
        'lesson_id' => $lesson2->id,
        'summary' => 'Test lesson completion',
    ]);

    $this->user->refresh();
    expect($this->user->points)->toBe($initialPoints + (2 * PointSystemValue::LESSON_COMPLETED->value));
});
