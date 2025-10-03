<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\CompletedLesson;
use App\Models\DailyActivity;
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
        
        // Get recent activities from daily activities (optimized)
        $recentActivities = DailyActivity::with([
                'user:id,username,full_name,avatar,is_public',
                'enrollment.course:id,name,is_public'
            ])
            ->select('id', 'user_id', 'enrollment_id', 'lessons_completed', 'is_bonus_earned', 'activity_date')
            ->where('lessons_completed', '>', 0)
            ->whereHas('user', function ($query) {
                $query->where('is_public', true);
            })
            ->latest('activity_date')
            ->limit(5)
            ->get()
            ->map(function ($activity) {
                $course = $activity->enrollment->course;
                $courseName = $course->name;
                $isPublicCourse = $course->is_public;
                
                return [
                    'id' => $activity->id,
                    'type' => 'lessons_completed',
                    'description' => "Completed {$activity->lessons_completed} lesson(s) in {$courseName}",
                    'course_name' => $courseName,
                    'course_is_public' => $isPublicCourse,
                    'course_id' => $isPublicCourse ? $course->id : null,
                    'points_earned' => $activity->getPointsEarned(),
                    'created_at' => $activity->activity_date->toISOString(),
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
        
        // Get last completed lesson with summary and link
        $lastCompletedLesson = null;
        if ($user->enrollment_id) {
            $lastCompletedLesson = CompletedLesson::with([
                    'lesson:id,name,module_id',
                    'lesson.module:id,name,course_id',
                    'lesson.module.course:id,name'
                ])
                ->where('enrollment_id', $user->enrollment_id)
                ->whereNotNull('summary')
                ->where('summary', '!=', '')
                ->latest('created_at')
                ->first();
                
            if ($lastCompletedLesson) {
                $lesson = $lastCompletedLesson->lesson;
                $module = $lesson?->module;
                $course = $module?->course;
                
                $lastCompletedLesson = [
                    'id' => $lastCompletedLesson->id,
                    'title' => $lesson?->name ?? 'Unknown Lesson',
                    'summary' => $lastCompletedLesson->summary,
                    'link' => $lastCompletedLesson->link,
                    'completed_at' => $lastCompletedLesson->created_at->toISOString(),
                    'module_title' => $module?->name ?? 'Unknown Module',
                    'class_title' => $course?->name ?? 'Unknown Course',
                ];
            }
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
            'last_completed_lesson' => $lastCompletedLesson,
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

    /**
     * Load more activities for infinite scroll
     */
    public function loadMoreActivities(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $activities = DailyActivity::with([
                'user:id,username,full_name,avatar,is_public',
                'enrollment.course:id,name,is_public'
            ])
            ->select('id', 'user_id', 'enrollment_id', 'lessons_completed', 'is_bonus_earned', 'activity_date')
            ->where('lessons_completed', '>', 0)
            ->whereHas('user', function ($query) {
                $query->where('is_public', true);
            })
            ->latest('activity_date')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($activity) {
                $course = $activity->enrollment->course;
                $courseName = $course->name;
                $isPublicCourse = $course->is_public;
                
                return [
                    'id' => $activity->id,
                    'type' => 'lessons_completed',
                    'description' => "Completed {$activity->lessons_completed} lesson(s) in {$courseName}",
                    'course_name' => $courseName,
                    'course_is_public' => $isPublicCourse,
                    'course_id' => $isPublicCourse ? $course->id : null,
                    'points_earned' => $activity->getPointsEarned(),
                    'created_at' => $activity->activity_date->toISOString(),
                    'user' => [
                        'id' => $activity->user->id,
                        'full_name' => $activity->user->full_name ?? $activity->user->username,
                        'username' => $activity->user->username,
                        'avatar' => $activity->user->avatar,
                    ],
                ];
            });

        $hasMore = $activities->count() === $perPage;

        return response()->json([
            'activities' => $activities,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
        ]);
    }
}
