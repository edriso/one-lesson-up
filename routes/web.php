<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    // Show Landing page for guests, Home page for authenticated users
    if (!auth()->check()) {
        // Get dynamic stats for landing page
        $stats = [
            'active_learners' => \App\Models\User::count(),
            'total_classes' => \App\Models\Course::where('is_active', true)->count(),
            'lessons_completed' => \App\Models\CompletedLesson::count(),
        ];
        
        return Inertia::render('Landing', [
            'stats' => $stats
        ]);
    }
    
    $user = auth()->user()->load(['enrollment.course.modules.lessons']);
    
    // Get upcoming lessons (next incomplete lesson only)
    $upcomingLessons = [];
    if ($user->enrollment) {
        // Get completed lesson IDs for this enrollment
        $completedLessonIds = \App\Models\CompletedLesson::where('enrollment_id', $user->enrollment->id)
            ->pluck('lesson_id')
            ->toArray();
        
        // Find the next incomplete lesson (sequential learning)
        $nextLesson = null;
        foreach ($user->enrollment->course->modules as $module) {
            foreach ($module->lessons as $lesson) {
                if (!in_array($lesson->id, $completedLessonIds)) {
                    $nextLesson = [
                        'id' => $lesson->id,
                        'title' => $lesson->name,
                        'completed' => false,
                        'module_title' => $module->name,
                        'class_title' => $module->course->name,
                        'due_date' => null,
                    ];
                    break 2; // Break out of both loops
                }
            }
        }
        
        // Only show the next lesson if there is one
        if ($nextLesson) {
            $upcomingLessons = [$nextLesson];
        }
    }
    
    // Get recent activities (last 5 learning activities)
    $recentActivities = \App\Models\LearningActivity::where('user_id', $user->id)
        ->with('enrollment.course')
        ->latest()
        ->take(5)
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'description' => $activity->description,
                'points_earned' => $activity->points_earned,
                'created_at' => $activity->created_at->toISOString(),
            ];
        })
        ->toArray();
    
    return Inertia::render('Home', [
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name ?? $user->name,
            'points' => $user->points ?? 0,
            'current_enrollment' => $user->enrollment ? [
                'id' => $user->enrollment->id,
                'class' => [
                    'id' => $user->enrollment->course->id,
                    'title' => $user->enrollment->course->name,
                ],
            ] : null,
        ],
        'upcoming_lessons' => $upcomingLessons,
        'recent_activities' => $recentActivities,
    ]);
})->name('home');

Route::get('leaderboard', function () {
    return Inertia::render('Leaderboard', [
        'leaderboards' => [
            'today' => [],
            'yesterday' => [],
            'this_month' => [],
            'last_month' => [],
            'year' => [],
            'overall' => [],
        ],
        'current_user_rank' => [
            'today' => 0,
            'yesterday' => 0,
            'this_month' => 0,
            'last_month' => 0,
            'year' => 0,
            'overall' => 0,
        ],
    ]);
})->middleware(['auth', 'verified'])->name('leaderboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('classes', [App\Http\Controllers\CourseController::class, 'index'])->name('classes');
    Route::get('classes/create', [App\Http\Controllers\CourseController::class, 'create'])->name('classes.create');
    Route::post('classes', [App\Http\Controllers\CourseController::class, 'store'])->name('classes.store');
    Route::get('classes/{course}', [App\Http\Controllers\CourseController::class, 'show'])->name('classes.show');
    Route::post('classes/{course}/join', [App\Http\Controllers\CourseController::class, 'join'])->name('classes.join');
    Route::post('classes/{course}/leave', [App\Http\Controllers\CourseController::class, 'leave'])->name('classes.leave');
    
    Route::get('lessons/{lesson}/complete', [App\Http\Controllers\LessonController::class, 'showCompleteForm'])->name('lessons.complete');
    Route::post('lessons/{lesson}/complete', [App\Http\Controllers\LessonController::class, 'complete'])->name('lessons.complete.store');
});

// Lesson summary routes
Route::get('lessons/{lesson}/summary', function ($lessonId) {
    $user = auth()->user();
    $lesson = \App\Models\Lesson::findOrFail($lessonId);
    
    // Check if user has completed this lesson
    $completedLesson = \App\Models\CompletedLesson::where('user_id', $user->id)
        ->where('lesson_id', $lessonId)
        ->first();
    
    if (!$completedLesson) {
        return response()->json(['error' => 'Lesson not completed'], 404);
    }
    
    return response()->json([
        'summary' => $completedLesson->summary,
        'link' => $completedLesson->link,
    ]);
})->middleware(['auth', 'verified'])->name('lessons.summary');

Route::put('lessons/{lesson}/summary', function ($lessonId, Request $request) {
    $user = auth()->user();
    $lesson = \App\Models\Lesson::findOrFail($lessonId);
    
    // Check if user has completed this lesson
    $completedLesson = \App\Models\CompletedLesson::where('user_id', $user->id)
        ->where('lesson_id', $lessonId)
        ->first();
    
    if (!$completedLesson) {
        return response()->json(['error' => 'Lesson not completed'], 404);
    }
    
    $request->validate([
        'summary' => 'required|string|max:2000',
        'link' => 'nullable|url|max:500',
    ]);
    
    $completedLesson->update([
        'summary' => $request->summary,
        'link' => $request->link,
    ]);
    
    return response()->json(['success' => true]);
})->middleware(['auth', 'verified'])->name('lessons.summary.update');

Route::get('profile/{username}', function ($username) {
    $user = \App\Models\User::where('username', $username)->firstOrFail();
    
    // Get completed classes
    $completedClasses = \App\Models\Enrollment::where('user_id', $user->id)
        ->whereNotNull('completed_at')
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
    
    // Get recent activities
    $activities = \App\Models\LearningActivity::where('user_id', $user->id)
        ->with('enrollment.course')
        ->latest()
        ->take(10)
        ->get()
        ->map(function ($activity) {
            return [
                'id' => $activity->id,
                'type' => $activity->activity_type,
                'description' => $activity->description,
                'points_earned' => $activity->points_earned,
                'created_at' => $activity->created_at->toISOString(),
            ];
        })
        ->toArray();
    
    // Get calendar data (last 30 days of activities)
    $calendarData = \App\Models\LearningActivity::where('user_id', $user->id)
        ->where('created_at', '>=', now()->subDays(30))
        ->get()
        ->groupBy(function ($activity) {
            return $activity->created_at->format('Y-m-d');
        })
        ->map(function ($dayActivities) {
            return $dayActivities->count();
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
            'full_name' => $user->full_name ?? $user->name,
            'username' => $user->username,
            'bio' => $user->bio ?? null,
            'title' => $user->title ?? null,
            'linkedin_url' => $user->linkedin_url ?? null,
            'website_url' => $user->website_url ?? null,
            'profile_picture_url' => $user->profile_picture_url ?? null,
            'points' => $user->points ?? 0,
            'joined_at' => $user->created_at->toISOString(),
            'is_public' => $user->is_public ?? true,
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
