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
    
    $user = auth()->user();
    
    return Inertia::render('Home', [
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name ?? $user->name,
            'points' => $user->points ?? 0,
            'current_enrollment' => null, // TODO: Load from database
        ],
        'upcoming_lessons' => [], // TODO: Load from database
        'recent_activities' => [], // TODO: Load from database
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

Route::get('profile/{username}', function ($username) {
    // TODO: Load user data from database based on username
    $user = auth()->user();
    
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
        'activities' => [],
        'completed_classes' => [],
        'calendar_data' => [],
        'stats' => [
            'total_points' => $user->points ?? 0,
            'total_lessons_completed' => 0,
            'total_classes_completed' => 0,
        ],
    ]);
})->middleware(['auth', 'verified'])->name('profile');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
