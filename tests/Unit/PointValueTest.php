<?php

use App\Enums\PointSystemValue;

test('point value enum has correct values', function () {
    expect(PointSystemValue::LESSON_COMPLETED->value)->toBe(1);
    expect(PointSystemValue::COURSE_ON_TIME_BONUS_MULTIPLIER->value)->toBe(50);
    expect(PointSystemValue::COURSE_LATE_BONUS_MULTIPLIER->value)->toBe(25);
});

test('point value enum has correct descriptions', function () {
    expect(PointSystemValue::LESSON_COMPLETED->description())->toBe('Lesson completed');
    expect(PointSystemValue::COURSE_ON_TIME_BONUS_MULTIPLIER->description())->toBe('Course completed on time (50% bonus)');
    expect(PointSystemValue::COURSE_LATE_BONUS_MULTIPLIER->description())->toBe('Course completed late (25% bonus)');
});

test('point value enum has correct percentages', function () {
    expect(PointSystemValue::LESSON_COMPLETED->percentage())->toBeNull();
    expect(PointSystemValue::COURSE_ON_TIME_BONUS_MULTIPLIER->percentage())->toBe('50%');
    expect(PointSystemValue::COURSE_LATE_BONUS_MULTIPLIER->percentage())->toBe('25%');
});

test('calculate course bonus returns correct values', function () {
    $lessonCount = 30;
    
    $onTimeBonus = PointSystemValue::calculateCourseBonus($lessonCount, true);
    $lateBonus = PointSystemValue::calculateCourseBonus($lessonCount, false);
    
    expect($onTimeBonus)->toBe(15.0); // 30 * 50 / 100
    expect($lateBonus)->toBe(7.5); // 30 * 25 / 100
});

test('calculate total course points returns correct values', function () {
    $lessonCount = 30;
    
    $totalOnTime = PointSystemValue::calculateTotalCoursePoints($lessonCount, true);
    $totalLate = PointSystemValue::calculateTotalCoursePoints($lessonCount, false);
    
    expect($totalOnTime)->toBe(45.0); // 30 + 15
    expect($totalLate)->toBe(37.5); // 30 + 7.5
});

test('calculate course bonus with different lesson counts', function () {
    $testCases = [
        [10, true, 5.0],   // 10 * 50 / 100
        [10, false, 2.5],  // 10 * 25 / 100
        [20, true, 10.0],  // 20 * 50 / 100
        [20, false, 5.0],  // 20 * 25 / 100
        [50, true, 25.0],  // 50 * 50 / 100
        [50, false, 12.5], // 50 * 25 / 100
    ];
    
    foreach ($testCases as [$lessonCount, $isOnTime, $expectedBonus]) {
        $bonus = PointSystemValue::calculateCourseBonus($lessonCount, $isOnTime);
        expect($bonus)->toBe($expectedBonus);
    }
});
