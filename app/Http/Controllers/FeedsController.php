<?php

namespace App\Http\Controllers;

use App\Models\CompletedLesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedsController extends Controller
{
    /**
     * Display the learning feeds page with optimized queries.
     */
    public function index(Request $request)
    {
        // Optimized query with eager loading and specific field selection
        $lessonSummaries = CompletedLesson::with([
                'enrollment.user:id,username,full_name',
                'lesson:id,name,module_id',
                'lesson.module:id,name,course_id',
                'lesson.module.course:id,name,link'
            ])
            ->select('id', 'enrollment_id', 'lesson_id', 'summary', 'link', 'created_at')
            ->whereNotNull('summary')
            ->where('summary', '!=', '')
            ->latest()
            ->limit(50) // Increased from 20 for better feed experience
            ->get()
            ->map(function ($completedLesson) {
                // Safely access nested relationships
                $user = $completedLesson->enrollment?->user;
                $lesson = $completedLesson->lesson;
                $module = $lesson?->module;
                $course = $module?->course;
                
                return [
                    'id' => $completedLesson->id,
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
            })
            ->toArray();

        return Inertia::render('Feeds', [
            'lesson_summaries' => $lessonSummaries,
        ]);
    }

    /**
     * Load more lesson summaries for infinite scroll
     */
    public function loadMoreSummaries(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 10;
        $offset = ($page - 1) * $perPage;

        $lessonSummaries = CompletedLesson::with([
                'enrollment.user:id,username,full_name',
                'lesson:id,name,module_id',
                'lesson.module:id,name,course_id',
                'lesson.module.course:id,name,link'
            ])
            ->select('id', 'enrollment_id', 'lesson_id', 'summary', 'link', 'created_at')
            ->whereNotNull('summary')
            ->where('summary', '!=', '')
            ->latest()
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($completedLesson) {
                // Safely access nested relationships
                $user = $completedLesson->enrollment?->user;
                $lesson = $completedLesson->lesson;
                $module = $lesson?->module;
                $course = $module?->course;
                
                return [
                    'id' => $completedLesson->id,
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

        $hasMore = $lessonSummaries->count() === $perPage;

        return response()->json([
            'summaries' => $lessonSummaries,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
        ]);
    }
}
