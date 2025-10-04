<?php

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\CompletedLesson;
use App\Models\DailyActivity;
use App\Enums\TimeBonusType;
use Carbon\Carbon;

test('user can set timezone during registration', function () {
    $userData = [
        'full_name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'username' => 'johndoe',
        'timezone' => 'America/New_York',
    ];

    $response = $this->post('/register', $userData);

    $response->assertRedirect('/');
    
    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->timezone)->toBe('America/New_York');
    expect($user->timezone_updated_at)->not->toBeNull();
});

test('user can update timezone in profile settings', function () {
    $user = User::factory()->create([
        'username' => 'testuser123',
        'full_name' => 'Test User',
        'timezone' => 'UTC',
        'timezone_updated_at' => now()->subDays(35), // More than 30 days ago
    ]);

    $this->actingAs($user);

    $response = $this->patch('/settings/profile', [
        'username' => $user->username,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'timezone' => 'Europe/London',
    ]);

    $response->assertRedirect('/settings/profile');
    
    $user->refresh();
    expect($user->timezone)->toBe('Europe/London');
    expect($user->timezone_updated_at)->not->toBeNull();
});

test('user cannot update timezone more than once every 30 days', function () {
    $user = User::factory()->create([
        'timezone' => 'UTC',
        'timezone_updated_at' => now()->subDays(15), // Less than 30 days ago
    ]);

    $this->actingAs($user);

    $response = $this->patch('/settings/profile', [
        'username' => $user->username,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'timezone' => 'Europe/London',
    ]);

    $response->assertSessionHasErrors(['timezone']);
    
    $user->refresh();
    expect($user->timezone)->toBe('UTC'); // Should not change
});

test('timezone affects time bonus calculation', function () {
    $user = User::factory()->create([
        'timezone' => 'America/New_York',
    ]);

    $course = Course::factory()->create();
    $module = $course->modules()->create([
        'title' => 'Test Module',
        'description' => 'Test Description',
        'module_order' => 1,
    ]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    
    // Associate enrollment with user
    $user->update(['enrollment_id' => $enrollment->id]);

    $lesson = $module->lessons()->create([
        'name' => 'Test Lesson',
        'description' => 'Test Description',
        'lesson_order' => 1,
    ]);

    // Mock time to be in the morning bonus window for Eastern Time (6:30 AM)
    $morningTime = Carbon::now('America/New_York')->setHour(6)->setMinute(30)->setSecond(0);
    Carbon::setTestNow($morningTime);

    $response = $this->actingAs($user)->post("/lessons/{$lesson->id}/complete", [
        'summary' => 'Test summary',
        'link' => 'https://example.com',
    ]);

    $response->assertRedirect();

    // Check that time bonus was awarded
    $dailyActivity = DailyActivity::where('user_id', $user->id)
        ->where('enrollment_id', $enrollment->id)
        ->whereDate('activity_date', Carbon::now($user->timezone)->format('Y-m-d'))
        ->first();

    expect($dailyActivity)->not->toBeNull();
    expect($dailyActivity->time_bonus_earned)->toBe(true);
});

test('timezone affects daily activity date calculation', function () {
    $user = User::factory()->create([
        'timezone' => 'Asia/Tokyo',
    ]);

    $course = Course::factory()->create();
    $module = $course->modules()->create([
        'title' => 'Test Module',
        'description' => 'Test Description',
        'module_order' => 1,
    ]);
    $enrollment = Enrollment::factory()->create([
        'user_id' => $user->id,
        'course_id' => $course->id,
    ]);
    
    // Associate enrollment with user
    $user->update(['enrollment_id' => $enrollment->id]);

    $lesson = $module->lessons()->create([
        'name' => 'Test Lesson',
        'description' => 'Test Description',
        'lesson_order' => 1,
    ]);

    // Set time to be in Tokyo timezone (morning bonus window)
    $morningTime = Carbon::now('Asia/Tokyo')->setHour(6)->setMinute(30)->setSecond(0);
    Carbon::setTestNow($morningTime);

    $response = $this->actingAs($user)->post("/lessons/{$lesson->id}/complete", [
        'summary' => 'Test summary',
        'link' => 'https://example.com',
    ]);

    $response->assertRedirect();

    // Check that daily activity was created with correct date in user's timezone
    $dailyActivity = DailyActivity::where('user_id', $user->id)
        ->where('enrollment_id', $enrollment->id)
        ->whereDate('activity_date', Carbon::now($user->timezone)->format('Y-m-d'))
        ->first();

    expect($dailyActivity)->not->toBeNull();
    expect($dailyActivity->activity_date->format('Y-m-d'))->toBe(Carbon::now($user->timezone)->format('Y-m-d'));
});

test('user can update timezone after 30 days', function () {
    $user = User::factory()->create([
        'username' => 'testuser456',
        'full_name' => 'Test User 2',
        'timezone' => 'UTC',
        'timezone_updated_at' => now()->subDays(30), // Exactly 30 days ago
    ]);

    $this->actingAs($user);

    $response = $this->patch('/settings/profile', [
        'username' => $user->username,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'timezone' => 'Europe/Paris',
    ]);

    $response->assertRedirect('/settings/profile');
    
    $user->refresh();
    expect($user->timezone)->toBe('Europe/Paris');
    expect($user->timezone_updated_at)->not->toBeNull();
});

test('timezone validation works correctly', function () {
    $user = User::factory()->create([
        'timezone' => 'UTC',
        'timezone_updated_at' => now()->subDays(35),
    ]);

    $this->actingAs($user);

    // Test invalid timezone
    $response = $this->patch('/settings/profile', [
        'username' => $user->username,
        'full_name' => $user->full_name,
        'email' => $user->email,
        'timezone' => 'Invalid/Timezone',
    ]);

    $response->assertSessionHasErrors(['timezone']);
});

test('timezone affects user current date calculation', function () {
    $user = User::factory()->create([
        'timezone' => 'Europe/London',
    ]);

    // Mock current time
    Carbon::setTestNow(Carbon::parse('2024-01-15 12:00:00', 'UTC'));

    $currentDate = $user->getCurrentDateInTimezone();
    
    // Should be 1 PM in London (GMT+0 in January)
    expect($currentDate->format('H:i'))->toBe('12:00');
    expect($currentDate->timezone->getName())->toBe('Europe/London');
});
