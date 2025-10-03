<?php

namespace App\Http\Controllers;

use App\Models\CompletedLesson;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedsController extends Controller
{
    /**
     * Display the learning feeds page with optimized queries.
     */
    public function index(Request $request)
    {
        // Get lesson summaries from public courses only
        $lessonSummaries = CompletedLesson::with([
                'enrollment.user:id,username,full_name',
                'lesson:id,name,module_id',
                'lesson.module:id,name,course_id',
                'lesson.module.course:id,name,link'
            ])
            ->select('id', 'enrollment_id', 'lesson_id', 'summary', 'link', 'created_at')
            ->whereNotNull('summary')
            ->where('summary', '!=', '')
            ->whereHas('lesson.module.course', function ($query) {
                $query->where('is_public', true);
            })
            ->latest()
            ->limit(50)
            ->get()
            ->map(function ($completedLesson) {
                $user = $completedLesson->enrollment?->user;
                $lesson = $completedLesson->lesson;
                $module = $lesson?->module;
                $course = $module?->course;
                
                return [
                    'id' => $completedLesson->id,
                    'type' => 'lesson',
                    'summary' => $completedLesson->summary,
                    'link' => $completedLesson->link,
                    'created_at' => $completedLesson->created_at->toISOString(),
                    'user' => [
                        'id' => $user?->id,
                        'username' => $user?->username ?? 'unknown',
                        'full_name' => $user?->full_name ?? $user?->username ?? 'Unknown User',
                    ],
                    'lesson' => [
                        'id' => $lesson?->id,
                        'title' => $lesson?->name ?? 'Unknown Lesson',
                        'module' => [
                            'id' => $module?->id,
                            'title' => $module?->name ?? 'Unknown Module',
                            'course' => [
                                'id' => $course?->id,
                                'title' => $course?->name ?? 'Unknown Course',
                                'link' => $course?->link,
                            ],
                        ],
                    ],
                ];
            });

        // Get course reflections for public courses only
        $courseReflections = Enrollment::with([
                'user:id,username,full_name',
                'course:id,name,link'
            ])
            ->select('id', 'user_id', 'course_id', 'course_reflection', 'course_reflection_link', 'completed_at')
            ->whereNotNull('course_reflection')
            ->whereNotNull('completed_at')
            ->where('course_reflection', '!=', '')
            ->whereHas('course', function ($query) {
                $query->where('is_public', true);
            })
            ->latest('completed_at')
            ->limit(50)
            ->get()
            ->map(function ($enrollment) {
                $user = $enrollment->user;
                $course = $enrollment->course;
                
                return [
                    'id' => $enrollment->id,
                    'type' => 'course',
                    'summary' => $enrollment->course_reflection,
                    'link' => $enrollment->course_reflection_link,
                    'created_at' => $enrollment->completed_at->toISOString(),
                    'user' => [
                        'id' => $user?->id,
                        'username' => $user?->username ?? 'unknown',
                        'full_name' => $user?->full_name ?? $user?->username ?? 'Unknown User',
                    ],
                    'course' => [
                        'id' => $course?->id,
                        'title' => $course?->name ?? 'Unknown Course',
                        'link' => $course?->link,
                    ],
                ];
            });

        // Combine and sort by created_at
        $allFeeds = collect([...$lessonSummaries, ...$courseReflections])
            ->sortByDesc('created_at')
            ->take(50)
            ->values()
            ->toArray();

        return Inertia::render('Feeds', [
            'feeds' => $allFeeds,
        ]);
    }

    /**
     * Load more feeds for infinite scroll
     */
    public function loadMoreFeeds(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        // Get lesson summaries from public courses only
        $lessonSummaries = CompletedLesson::with([
                'enrollment.user:id,username,full_name',
                'lesson:id,name,module_id',
                'lesson.module:id,name,course_id',
                'lesson.module.course:id,name,link'
            ])
            ->select('id', 'enrollment_id', 'lesson_id', 'summary', 'link', 'created_at')
            ->whereNotNull('summary')
            ->where('summary', '!=', '')
            ->whereHas('lesson.module.course', function ($query) {
                $query->where('is_public', true);
            })
            ->latest()
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($completedLesson) {
                $user = $completedLesson->enrollment?->user;
                $lesson = $completedLesson->lesson;
                $module = $lesson?->module;
                $course = $module?->course;
                
                return [
                    'id' => $completedLesson->id,
                    'type' => 'lesson',
                    'summary' => $completedLesson->summary,
                    'link' => $completedLesson->link,
                    'created_at' => $completedLesson->created_at->toISOString(),
                    'user' => [
                        'id' => $user?->id,
                        'username' => $user?->username ?? 'unknown',
                        'full_name' => $user?->full_name ?? $user?->username ?? 'Unknown User',
                    ],
                    'lesson' => [
                        'id' => $lesson?->id,
                        'title' => $lesson?->name ?? 'Unknown Lesson',
                        'module' => [
                            'id' => $module?->id,
                            'title' => $module?->name ?? 'Unknown Module',
                            'course' => [
                                'id' => $course?->id,
                                'title' => $course?->name ?? 'Unknown Course',
                                'link' => $course?->link,
                            ],
                        ],
                    ],
                ];
            });

        // Get course reflections for public courses only
        $courseReflections = Enrollment::with([
                'user:id,username,full_name',
                'course:id,name,link'
            ])
            ->select('id', 'user_id', 'course_id', 'course_reflection', 'course_reflection_link', 'completed_at')
            ->whereNotNull('course_reflection')
            ->whereNotNull('completed_at')
            ->where('course_reflection', '!=', '')
            ->whereHas('course', function ($query) {
                $query->where('is_public', true);
            })
            ->latest('completed_at')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($enrollment) {
                $user = $enrollment->user;
                $course = $enrollment->course;
                
                return [
                    'id' => $enrollment->id,
                    'type' => 'course',
                    'summary' => $enrollment->course_reflection,
                    'link' => $enrollment->course_reflection_link,
                    'created_at' => $enrollment->completed_at->toISOString(),
                    'user' => [
                        'id' => $user?->id,
                        'username' => $user?->username ?? 'unknown',
                        'full_name' => $user?->full_name ?? $user?->username ?? 'Unknown User',
                    ],
                    'course' => [
                        'id' => $course?->id,
                        'title' => $course?->name ?? 'Unknown Course',
                        'link' => $course?->link,
                    ],
                ];
            });

        // Combine and sort by created_at
        $allFeeds = collect([...$lessonSummaries, ...$courseReflections])
            ->sortByDesc('created_at')
            ->take($perPage)
            ->values();

        $hasMore = $allFeeds->count() === $perPage;

        return response()->json([
            'feeds' => $allFeeds,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
        ]);
    }
}
