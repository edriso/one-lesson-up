<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CompletedLesson;
use App\Models\LearningActivity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display the home page with optimized queries.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Eager load user's enrollment with minimal data needed
        $user->load([
            'enrollment:id,user_id,course_id',
            'enrollment.course:id,name',
            'enrollment.course.modules:id,name,course_id,module_order',
            'enrollment.course.modules.lessons:id,name,module_id,lesson_order'
        ]);
        
        // Get upcoming lessons (optimized - only get next incomplete lesson)
        $upcomingLessons = [];
        if ($user->enrollment) {
            // Single query to get all completed lesson IDs for this enrollment
            $completedLessonIds = CompletedLesson::where('enrollment_id', $user->enrollment_id)
                ->pluck('lesson_id')
                ->toArray();
            
            // Find the next incomplete lesson using already loaded data
            $nextLesson = null;
            foreach ($user->enrollment->course->modules->sortBy('module_order') as $module) {
                foreach ($module->lessons->sortBy('lesson_order') as $lesson) {
                    if (!in_array($lesson->id, $completedLessonIds)) {
                        $nextLesson = [
                            'id' => $lesson->id,
                            'title' => $lesson->name,
                            'completed' => false,
                            'module_title' => $module->name,
                            'class_title' => $user->enrollment->course->name,
                            'due_date' => null,
                        ];
                        break 2; // Break out of both loops
                    }
                }
            }
            
            if ($nextLesson) {
                $upcomingLessons = [$nextLesson];
            }
        }
        
        // Get recent activities with eager loading (optimized)
        $recentActivities = LearningActivity::with([
                'user:id,username,full_name,avatar',
                'enrollment.course:id,name'
            ])
            ->select('id', 'user_id', 'enrollment_id', 'lesson_id', 'activity_type', 'points_earned', 'created_at')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->activity_type->value,
                    'description' => $activity->activity_type->description(),
                    'points_earned' => $activity->points_earned ?? 0,
                    'created_at' => $activity->created_at->toISOString(),
                    'user' => [
                        'id' => $activity->user->id,
                        'full_name' => $activity->user->full_name ?? $activity->user->username,
                        'username' => $activity->user->username,
                        'avatar' => $activity->user->avatar,
                    ],
                ];
            })
            ->toArray();
        
        // Prepare current class data with completion percentages
        $currentClass = null;
        if ($user->enrollment) {
            $modules = $user->enrollment->course->modules->map(function ($module) use ($completedLessonIds) {
                $totalLessons = $module->lessons->count();
                $completedLessons = $module->lessons->filter(function ($lesson) use ($completedLessonIds) {
                    return in_array($lesson->id, $completedLessonIds);
                })->count();
                
                return [
                    'id' => $module->id,
                    'title' => $module->name,
                    'lessons' => $module->lessons->toArray(),
                    'completion_percentage' => $totalLessons > 0 
                        ? round(($completedLessons / $totalLessons) * 100) 
                        : 0,
                ];
            })->toArray();
            
            $currentClass = [
                'id' => $user->enrollment->course->id,
                'title' => $user->enrollment->course->name,
                'modules' => $modules,
            ];
        }
        
        return Inertia::render('Home', [
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'username' => $user->username,
                'points' => $user->points,
                'avatar' => $user->avatar,
                'enrollment_id' => $user->enrollment_id,
                'current_class' => $currentClass,
            ],
            'upcoming_lessons' => $upcomingLessons,
            'recent_activities' => $recentActivities,
        ]);
    }
    
    /**
     * Display the landing page for guests.
     */
    public function landing()
    {
        // Optimized stats queries with single queries each
        $stats = [
            'active_learners' => User::count(),
            'total_classes' => Course::where('is_active', true)->count(),
            'lessons_completed' => CompletedLesson::count(),
        ];
        
        return Inertia::render('Landing', [
            'stats' => $stats
        ]);
    }
}
