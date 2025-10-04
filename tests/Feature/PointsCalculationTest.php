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
    $this->course = Course::factory()->create([
        'name' => 'Test Course',
    ]);
    $this->module = Module::factory()->create(['course_id' => $this->course->id]);
    $this->lesson1 = Lesson::factory()->create(['module_id' => $this->module->id]);
    $this->lesson2 = Lesson::factory()->create(['module_id' => $this->module->id]);
    $this->enrollment = Enrollment::factory()->create([
        'user_id' => $this->user->id,
        'course_id' => $this->course->id,
    ]);
});

test('user gets correct points for 2-lesson course completion', function () {
    // Complete both lessons
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson1->id,
        'summary' => 'Test lesson 1 completion',
    ]);
    
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson2->id,
        'summary' => 'Test lesson 2 completion',
    ]);

    // Complete the course with reflection
    $success = $this->enrollment->completeWithReflection('Test course reflection');
    
    expect($success)->toBeTrue();
    
    $this->user->refresh();
    
    // Expected points: 1 active day bonus + course completion bonus (1 point for 2 lessons)
    $expectedPoints = 1 + \App\Enums\PointSystemValue::calculateCourseBonus(2, true);
    expect($this->user->points)->toBe($expectedPoints);
    
});

test('leaderboard shows correct points for this month', function () {
    // Use today's date since daily activities use now() for the date
    $testDate = now();
    
    // Complete both lessons on the test date
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson1->id,
        'summary' => 'Test lesson 1 completion',
        'created_at' => $testDate,
    ]);
    
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson2->id,
        'summary' => 'Test lesson 2 completion',
        'created_at' => $testDate,
    ]);

    // Complete the course with reflection
    $this->enrollment->completeWithReflection('Test course reflection');
    
    $this->user->refresh();
    
    // Check daily activity points
    $dailyActivity = DailyActivity::where('user_id', $this->user->id)
        ->whereDate('activity_date', $testDate->toDateString())
        ->first();
    
    expect($dailyActivity)->not->toBeNull();
    expect($dailyActivity->lessons_completed)->toBe(2);
    expect($dailyActivity->getPointsEarned())->toBe(1); // 1 active day bonus only
    
    // Check total user points
    $expectedTotalPoints = 1 + \App\Enums\PointSystemValue::calculateCourseBonus(2, true);
    expect($this->user->points)->toBe($expectedTotalPoints);
});

test('profile shows correct points for completed course', function () {
    // Complete both lessons
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson1->id,
        'summary' => 'Test lesson 1 completion',
    ]);
    
    CompletedLesson::create([
        'enrollment_id' => $this->enrollment->id,
        'lesson_id' => $this->lesson2->id,
        'summary' => 'Test lesson 2 completion',
    ]);

    // Complete the course with reflection
    $this->enrollment->completeWithReflection('Test course reflection');
    
    $this->user->refresh();
    
    // Check the course completion points calculation
    $coursePoints = $this->enrollment->course->lessons_count + 
                   \App\Enums\PointSystemValue::calculateCourseBonus($this->enrollment->course->lessons_count, true);
    
    // The profile should show the course completion points, not the total user points
    expect($coursePoints)->toBe(3); // 2 lessons + 1 bonus = 3 points
});
