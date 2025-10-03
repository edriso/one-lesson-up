<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Show Landing page for guests, Home page for authenticated users
    if (!auth()->check()) {
        return app(\App\Http\Controllers\HomeController::class)->landing();
    }
    
    return app(\App\Http\Controllers\HomeController::class)->index(request());
})->name('home');

Route::get('leaderboard', [\App\Http\Controllers\LeaderboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('leaderboard');

Route::get('api/leaderboard/load-more', [\App\Http\Controllers\LeaderboardController::class, 'loadMore'])
    ->middleware(['auth', 'verified'])
    ->name('leaderboard.load-more');

Route::get('api/activities/load-more', [\App\Http\Controllers\HomeController::class, 'loadMoreActivities'])
    ->middleware(['auth', 'verified'])
    ->name('activities.load-more');

Route::get('api/feeds/load-more', [\App\Http\Controllers\FeedsController::class, 'loadMoreSummaries'])
    ->middleware(['auth', 'verified'])
    ->name('feeds.load-more');

Route::get('api/courses/load-more', [\App\Http\Controllers\CourseController::class, 'loadMoreCourses'])
    ->middleware(['auth', 'verified'])
    ->name('courses.load-more');

Route::get('feeds', [\App\Http\Controllers\FeedsController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('feeds');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('classes', [App\Http\Controllers\CourseController::class, 'index'])->name('classes');
    Route::get('classes/create', [App\Http\Controllers\CourseController::class, 'create'])->name('classes.create');
    Route::post('classes', [App\Http\Controllers\CourseController::class, 'store'])->name('classes.store');
    Route::get('classes/{course}', [App\Http\Controllers\CourseController::class, 'show'])->name('classes.show');
    Route::post('classes/{course}/join', [App\Http\Controllers\CourseController::class, 'join'])->name('classes.join');
    Route::post('classes/{course}/leave', [App\Http\Controllers\CourseController::class, 'leave'])->name('classes.leave');
    Route::post('classes/{course}/complete', [App\Http\Controllers\CourseController::class, 'complete'])->name('classes.complete');
    Route::post('classes/{course}/reflection', [App\Http\Controllers\CourseController::class, 'updateReflection'])->name('classes.reflection.update');
    
    Route::get('lessons/{lesson}/complete', [App\Http\Controllers\LessonController::class, 'showCompleteForm'])->name('lessons.complete');
    Route::post('lessons/{lesson}/complete', [App\Http\Controllers\LessonController::class, 'complete'])->name('lessons.complete.store');
});

// Lesson summary routes
Route::get('lessons/{lesson}/summary', function ($lessonId) {
    $user = auth()->user();
    $lesson = \App\Models\Lesson::findOrFail($lessonId);
    
    // Check if user has an enrollment
    if (!$user->enrollment_id) {
        return response()->json([
            'error' => 'Not enrolled in any class',
            'message' => 'You must be enrolled in a class to view lesson summaries.'
        ], 403);
    }
    
    // Check if user has completed this lesson
    $completedLesson = \App\Models\CompletedLesson::where('enrollment_id', $user->enrollment_id)
        ->where('lesson_id', $lessonId)
        ->first();
    
    if (!$completedLesson) {
        return response()->json([
            'error' => 'Lesson not completed yet',
            'message' => 'You must complete this lesson before viewing or editing its summary.'
        ], 404);
    }
    
    return response()->json([
        'summary' => $completedLesson->summary,
        'link' => $completedLesson->link,
        'completed_at' => $completedLesson->created_at,
    ]);
})->middleware(['auth', 'verified'])->name('lessons.summary');

Route::put('lessons/{lesson}/summary', function ($lessonId, \Illuminate\Http\Request $request) {
    $user = auth()->user();
    $lesson = \App\Models\Lesson::findOrFail($lessonId);
    
    // Check if user has an enrollment
    if (!$user->enrollment_id) {
        return response()->json([
            'error' => 'Not enrolled in any class',
            'message' => 'You must be enrolled in a class to edit lesson summaries.'
        ], 403);
    }
    
    // Check if user has completed this lesson
    $completedLesson = \App\Models\CompletedLesson::where('enrollment_id', $user->enrollment_id)
        ->where('lesson_id', $lessonId)
        ->first();
    
    if (!$completedLesson) {
        return response()->json([
            'error' => 'Lesson not completed yet',
            'message' => 'You must complete this lesson before editing its summary.'
        ], 404);
    }
    
    try {
        $validated = $request->validate([
            'summary' => 'required|string|max:2000',
            'link' => 'nullable|url|max:500',
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'error' => 'Validation failed',
            'message' => 'Please check your input and try again.',
            'errors' => $e->errors()
        ], 422);
    }
    
    $completedLesson->update([
        'summary' => $validated['summary'],
        'link' => $validated['link'],
    ]);
    
    return response()->json([
        'success' => true,
        'message' => 'Lesson summary updated successfully.',
        'data' => [
            'summary' => $completedLesson->summary,
            'link' => $completedLesson->link,
        ]
    ]);
})->middleware(['auth', 'verified'])->name('lessons.summary.update');

Route::get('profile/{username}', function ($username) {
    $user = \App\Models\User::where('username', $username)->firstOrFail();
    
    // Get completed classes (optimized query)
    $completedClasses = $user->completedEnrollments()
        ->with('course')
        ->get()
        ->map(function ($enrollment) {
            return [
                'id' => $enrollment->course->id,
                'title' => $enrollment->course->name,
                'completed_at' => $enrollment->completed_at->toISOString(),
                'points_earned' => $enrollment->course->lessons_count + \App\Enums\PointSystemValue::calculateCourseBonus($enrollment->course->lessons_count, true),
                'lessons_count' => $enrollment->course->lessons_count,
            ];
        })
        ->toArray();
    
    // Get recent activities from daily activities
    $activities = \App\Models\DailyActivity::where('user_id', $user->id)
        ->with(['enrollment.course'])
        ->where('lessons_completed', '>', 0)
        ->latest('activity_date')
        ->take(10)
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => 'lessons_completed',
                'description' => "Completed {$activity->lessons_completed} lesson(s) in {$activity->enrollment->course->name}",
                'points_earned' => $activity->getPointsEarned(),
                'created_at' => $activity->activity_date->toISOString(),
            ];
        })
        ->toArray();
    
    // Get calendar data (last 30 days of activities)
    $calendarData = \App\Models\DailyActivity::where('user_id', $user->id)
        ->where('activity_date', '>=', now()->subDays(30))
        ->get()
        ->mapWithKeys(function ($activity) {
            return [$activity->activity_date->format('Y-m-d') => $activity->lessons_completed];
        })
        ->toArray();
    
    // Convert to array format expected by frontend
    $calendarArray = [];
    foreach ($calendarData as $date => $count) {
        $calendarArray[] = [
            'date' => $date,
            'count' => $count
        ];
    }
    
    return Inertia::render('Profile', [
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name ?? $user->username,
            'username' => $user->username,
            'bio' => $user->bio ?? null,
            'title' => $user->title ?? null,
            'website_url' => $user->website_url ?? null,
            'avatar' => $user->avatar ?? null,
            'points' => $user->points ?? 0,
            'joined_at' => $user->created_at->toISOString(),
            'is_public' => $user->is_public ?? true,
            'week_starts_on' => $user->week_starts_on ?? 0,
        ],
        'activities' => $activities,
        'completed_classes' => $completedClasses,
        'calendar_data' => $calendarArray,
        'stats' => [
            'total_points' => $user->points ?? 0,
            'total_lessons_completed' => \App\Models\CompletedLesson::whereHas('enrollment', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->count(),
            'total_classes_completed' => count($completedClasses),
        ],
    ]);
})->middleware(['auth', 'verified'])->name('profile');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
