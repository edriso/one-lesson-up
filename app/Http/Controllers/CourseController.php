<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\CompletedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $user = auth()->user()->load(['enrollment.course', 'enrollments']);
        
        // Get user's active enrollments - optimized using enrollment_id
        $userEnrollments = [];
        if ($user->enrollment_id) {
            $userEnrollments = [$user->enrollment->course_id];
        }
        
        // Get user's completed enrollments - optimized query
        $userCompletedEnrollments = $user->completedEnrollments()
            ->pluck('course_id')
            ->toArray();
        
        // For completed courses, they should also be considered "enrolled" (user was enrolled in them)
        $userAllEnrollments = array_merge($userEnrollments, $userCompletedEnrollments);
        
        // Get all active courses with counts - take more to ensure current course is included
        // Show public courses OR courses created by the user
        $courses = Course::withCount([
                'enrollments as students_count', // Total students (active + completed)
                'enrollments as active_students_count' => function ($query) {
                    $query->whereNull('completed_at');
                },
                'enrollments as completed_students_count' => function ($query) {
$query->whereNotNull('completed_at');
                }
            ])
            ->withCount('lessons')
            ->with('tags')
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('is_public', true)
                      ->orWhere('creator_id', $user->id);
            })
            ->orderBy('students_count', 'desc')
            ->take(50) // Take more courses to ensure current course is included
            ->get()
            ->map(function ($course) use ($user, $userEnrollments, $userCompletedEnrollments, $userAllEnrollments) {
                return [
                    'id' => $course->id,
                    'title' => $course->name,
                    'description' => $course->description,
                    'students_count' => $course->students_count,
                    'active_students_count' => $course->active_students_count,
                    'completed_students_count' => $course->completed_students_count,
                    'lessons_count' => $course->lessons_count,
                    'can_join' => $user->canCreateCourse(),
                    'is_enrolled' => in_array($course->id, $userAllEnrollments),
                    'is_completed' => in_array($course->id, $userCompletedEnrollments),
                    'is_creator' => $course->creator_id === $user->id,
                    'is_featured' => $course->is_featured,
                    'is_public' => $course->is_public,
                    'tags' => $course->tags->filter(function ($tag) use ($course, $user) {
                        // Show all tags for course creator, only public tags for others
                        return $course->creator_id === $user->id || $tag->is_public;
                    })->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    }),
                ];
            })
            ->sortBy(function ($course) use ($user) {
                // Sort order: Current class → Completed classes → Created classes → Featured classes → Others by popularity
                $currentCourseId = $user->enrollment ? $user->enrollment->course_id : null;
                
                // Priority 0: Current class
                if ($course['id'] === $currentCourseId) {
                    return 0;
                }
                
                // Priority 1: Completed classes
                if ($course['is_completed']) {
                    return 1;
                }
                
                // Priority 2: Created classes
                if ($course['is_creator']) {
                    return 2;
                }
                
                // Priority 3: Featured classes (if we had a featured flag)
                // For now, we'll use is_featured from the course model
                if (isset($course['is_featured']) && $course['is_featured']) {
                    return 3;
                }
                
                // Priority 4: Other classes (already sorted by students_count)
                return 4;
            })
            ->values(); // Reset array keys
        
        return Inertia::render('Courses', [
            'courses' => $courses,
            'can_create_class' => $user->canCreateCourse(),
            'user' => [
                'id' => $user->id,
                'enrollment_id' => $user->enrollment_id,
                'current_class' => $user->enrollment ? [
                    'id' => $user->enrollment->course->id,
                    'title' => $user->enrollment->course->name,
                ] : null,
            ],
        ]);
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        $user = auth()->user()->load('enrollment');
        
        // Check if user can create a course
        if (!$user->canCreateCourse()) {
            return redirect()->route('classes')
                ->with('error', 'You cannot create a class while enrolled in another class.');
        }
        
        // Get available tags for selection
        $availableTags = \App\Models\Tag::where('is_public', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        
        return Inertia::render('CreateCourse', [
            'available_tags' => $availableTags
        ]);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user()->load('enrollment');
        
        // Final check: user cannot create if already enrolled
        if (!$user->canCreateCourse()) {
            return back()->withErrors(['error' => 'You cannot create a class while enrolled in another class.']);
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'link' => 'nullable|url|max:500',
            'is_public' => 'boolean',
            'tags' => 'nullable|array|max:3',
            'tags.*' => 'required|string|max:50',
            'modules' => 'required|array|min:1',
            'modules.*.name' => 'required|string|max:255',
            'modules.*.description' => 'nullable|string|max:500',
            'modules.*.lessons' => 'required|array|min:1',
            'modules.*.lessons.*.name' => 'required|string|max:255',
            'modules.*.lessons.*.description' => 'nullable|string|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create the course
            $course = Course::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'link' => $validated['link'] ?? null,
                'creator_id' => $user->id,
                'is_active' => true,
                'is_featured' => false,
                'is_public' => $validated['is_public'] ?? true, // Default to true if not provided
            ]);
            
            // Handle tags
            if (!empty($validated['tags'])) {
                $tagIds = [];
                foreach ($validated['tags'] as $tagName) {
                    $tagName = trim($tagName);
                    if (empty($tagName)) continue;
                    
                    // Check for existing tag (case-insensitive)
                    $existingTag = \App\Models\Tag::whereRaw('LOWER(name) = ?', [strtolower($tagName)])->first();
                    
                    if ($existingTag) {
                        // Use existing tag
                        $tagIds[] = $existingTag->id;
                    } else {
                        // Create new tag
                        $tag = \App\Models\Tag::create([
                            'name' => $tagName,
                            'is_public' => false, // User-created tags are private by default
                        ]);
                        $tagIds[] = $tag->id;
                    }
                }
                
                if (!empty($tagIds)) {
                    $course->tags()->attach($tagIds);
                }
            }
            
            // Create modules and lessons
            foreach ($validated['modules'] as $moduleIndex => $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'name' => $moduleData['name'],
                    'description' => $moduleData['description'] ?? null,
                    'module_order' => $moduleIndex + 1,
                ]);
                
                foreach ($moduleData['lessons'] as $lessonIndex => $lessonData) {
                    Lesson::create([
                        'module_id' => $module->id,
                        'name' => $lessonData['name'],
                        'description' => $lessonData['description'] ?? null,
                        'lesson_order' => $lessonIndex + 1,
                    ]);
                }
            }
            
            // Automatically enroll the creator in the course
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'bonus_deadline' => $course->smart_deadline, // Learning deadline based on lesson count
            ]);
            
            // Update user's enrollment_id
            $user->update(['enrollment_id' => $enrollment->id]);
            
            DB::commit();
            
            return redirect()->route('classes.show', $course->id)
                ->with('success', 'Class created successfully! You have been automatically enrolled.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create class: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified course.
     */
    public function show(Course $course)
    {
        $user = auth()->user()->load('enrollment');
        $course->load(['modules.lessons']);
        
        // Get user's enrollment for this course with optimized queries
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNull('completed_at')
            ->first();
        
        // Check if user has completed this course (completed_at only required)
        $completedEnrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->first();
        
        // Get completed lesson IDs and check completion status
        $completedLessonIds = [];
        $allLessonsCompleted = false;
        if ($enrollment) {
            $completedLessonIds = CompletedLesson::where('enrollment_id', $enrollment->id)
                ->pluck('lesson_id')
                ->toArray();
            $allLessonsCompleted = count($completedLessonIds) >= $course->lessons_count;
        }
        
        return Inertia::render('ViewCourse', [
            'course' => [
                'id' => $course->id,
                'title' => $course->name,
                'description' => $course->description,
                'link' => $course->link,
                'is_public' => $course->is_public,
                'modules' => $course->modules->map(function ($module) use ($completedLessonIds) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'description' => $module->description,
                        'order' => $module->module_order,
                        'lessons' => $module->lessons->map(function ($lesson) use ($completedLessonIds) {
                            return [
                                'id' => $lesson->id,
                                'name' => $lesson->name,
                                'description' => $lesson->description,
                                'order' => $lesson->lesson_order,
                                'is_completed' => in_array($lesson->id, $completedLessonIds),
                            ];
                        }),
                    ];
                }),
                'course_reflection' => $completedEnrollment ? $completedEnrollment->course_reflection : null,
                'course_reflection_link' => $completedEnrollment ? $completedEnrollment->course_reflection_link : null,
                'total_lessons' => $course->lessons_count,
                'total_modules' => $course->modules_count,
                'created_at' => $course->created_at->toISOString(),
            ],
            'is_enrolled' => (bool) $enrollment,
            'is_completed' => (bool) $completedEnrollment,
            'all_lessons_completed' => $allLessonsCompleted,
            'can_join' => $user->canCreateCourse() && !$completedEnrollment,
            'completed_lessons_count' => count($completedLessonIds),
            'completion_date' => $completedEnrollment ? $completedEnrollment->completed_at->toISOString() : null,
            'bonus_deadline' => $enrollment ? $enrollment->bonus_deadline->toISOString() : null,
            'is_bonus_eligible' => $enrollment ? now()->lte($enrollment->bonus_deadline) : false,
            'is_course_creator' => $course->creator_id === $user->id,
            'enrollment_start_date' => $enrollment ? $enrollment->created_at->toISOString() : null,
            'was_completed_on_time' => $completedEnrollment ? $completedEnrollment->isCompletedOnTime() : null,
            'points_earned' => $completedEnrollment ? $this->calculatePointsEarned($completedEnrollment) : null,
        ]);
    }

    /**
     * Join a course (enroll user).
     */
    public function join(Course $course)
    {
        $user = auth()->user()->load('enrollment');
        
        // Check if user can join
        if (!$user->canCreateCourse()) {
            return back()->with('error', 'You must complete or leave your current class before joining another one.');
        }
        
        // Check if already enrolled (active enrollment = completed_at is NULL)
        $existingEnrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNull('completed_at')
            ->first();
        
        if ($existingEnrollment) {
            return back()->with('error', 'You are already enrolled in this class.');
        }
        
        try {
            DB::beginTransaction();
            
            // Create enrollment (start_date = created_at, no end_date needed)
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'bonus_deadline' => $course->smart_deadline, // Learning deadline based on lesson count
            ]);
            
            // Update user's enrollment_id
            $user->update(['enrollment_id' => $enrollment->id]);
            
            DB::commit();
            
            return redirect()->route('classes.show', $course->id)
                ->with('success', 'Successfully joined ' . $course->name . '!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to join class: ' . $e->getMessage());
        }
    }

    /**
     * Leave a course (delete enrollment).
     */
    public function leave(Course $course)
    {
        $user = auth()->user()->load('enrollment');
        
        // Find active enrollment for this course (completed_at is NULL)
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNull('completed_at')
            ->first();
        
        if (!$enrollment) {
            return back()->with('error', 'You are not enrolled in this class.');
        }
        
        try {
            DB::beginTransaction();
            
            // Calculate points to deduct (points earned from this course)
            $pointsToDeduct = $this->calculatePointsToDeduct($enrollment);
            
            // Deduct points from user
            if ($pointsToDeduct > 0) {
                $user->decrement('points', $pointsToDeduct);
            }
            
            // Check if user is the course creator and if there are no other enrollments
            // BEFORE deleting the current enrollment
            $isCourseCreator = $course->creator_id === $user->id;
            $hasOtherEnrollments = $course->enrollments()->where('id', '!=', $enrollment->id)->exists();
            
            
            // Clear user's enrollment_id if it matches this enrollment
            if ($user->enrollment_id === $enrollment->id) {
                $user->update(['enrollment_id' => null]);
            }
            
            // Delete the enrollment record
            $enrollment->delete();
            
            if ($isCourseCreator && !$hasOtherEnrollments) {
                // Store course name before deletion
                $courseName = $course->name;
                $courseId = $course->id;
                
                // Force delete all related records first to ensure proper cleanup
                // Delete completed lessons
                \App\Models\CompletedLesson::whereHas('enrollment', function($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->delete();
                
                // Delete daily activities
                \App\Models\DailyActivity::whereHas('enrollment', function($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->delete();
                
                // Delete enrollments
                \App\Models\Enrollment::where('course_id', $courseId)->delete();
                
                // Delete course tags
                DB::table('course_tags')->where('course_id', $courseId)->delete();
                
                // Delete lessons
                \App\Models\Lesson::whereHas('module', function($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->delete();
                
                // Delete modules
                \App\Models\Module::where('course_id', $courseId)->delete();
                
                // Finally delete the course
                $deleted = $course->delete();
                
                if (!$deleted) {
                    throw new \Exception('Course deletion failed - delete() returned false');
                }
                
                // Clear any caches that might contain this course
                Cache::flush();
                
                // Verify the course was actually deleted
                $courseExists = Course::find($courseId);
                if ($courseExists) {
                    throw new \Exception('Course deletion failed - course still exists after deletion');
                }
                
                DB::commit();
                
                return redirect()->route('classes')
                    ->with('success', 'You have left and deleted ' . $courseName . ' since no other users were enrolled.');
            }
            
            DB::commit();
            
            return redirect()->route('classes')
                ->with('success', 'You have left ' . $course->name . '.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to leave class: ' . $e->getMessage());
        }
    }
    
    /**
     * Clean up any orphaned courses (courses with no enrollments).
     * This is a utility method to fix any data inconsistencies.
     */
    public function cleanupOrphanedCourses()
    {
        $orphanedCourses = Course::whereDoesntHave('enrollments')->get();
        
        foreach ($orphanedCourses as $course) {
            // Delete all related records
            \App\Models\CompletedLesson::whereHas('enrollment', function($query) use ($course) {
                $query->where('course_id', $course->id);
            })->delete();
            
            \App\Models\DailyActivity::whereHas('enrollment', function($query) use ($course) {
                $query->where('course_id', $course->id);
            })->delete();
            
            \App\Models\Enrollment::where('course_id', $course->id)->delete();
            DB::table('course_tags')->where('course_id', $course->id)->delete();
            
            \App\Models\Lesson::whereHas('module', function($query) use ($course) {
                $query->where('course_id', $course->id);
            })->delete();
            
            \App\Models\Module::where('course_id', $course->id)->delete();
            
            $course->delete();
        }
        
        return $orphanedCourses->count();
    }

    /**
     * Calculate points earned from a completed course.
     */
    private function calculatePointsEarned($enrollment)
    {
        $pointsEarned = 0;
        
        // Count completed lessons (1 point each)
        $completedLessonsCount = $enrollment->completedLessons()->count();
        $pointsEarned += $completedLessonsCount;
        
        // Add course completion bonus points
        $course = $enrollment->course;
        $lessonCount = $course->lessons_count;
        $isCompletedOnTime = $enrollment->isCompletedOnTime();
        
        // Calculate bonus points using PointSystemValue enum
        $bonusPoints = \App\Enums\PointSystemValue::calculateCourseBonus($lessonCount, $isCompletedOnTime);
        $pointsEarned += round($bonusPoints);
        
        return $pointsEarned;
    }

    /**
     * Calculate points to deduct when leaving a course.
     */
    private function calculatePointsToDeduct($enrollment)
    {
        $pointsToDeduct = 0;
        
        // Count completed lessons (1 point each)
        $completedLessonsCount = $enrollment->completedLessons()->count();
        $pointsToDeduct += $completedLessonsCount;
        
        // Check if course was completed (bonus points)
        if ($enrollment->completed_at) {
            // Calculate course completion bonus that was awarded
            $course = $enrollment->course;
            $lessonCount = $course->lessons_count;
            $deadline = $course->deadline_days;
            
            // Check if completed within deadline
            $deadlineDate = $enrollment->created_at->addDays($deadline);
            $isCompletedOnTime = $enrollment->completed_at->lte($deadlineDate);
            
            // Calculate bonus points using PointSystemValue enum
            $bonusPoints = \App\Enums\PointSystemValue::calculateCourseBonus($lessonCount, $isCompletedOnTime);
            $pointsToDeduct += round($bonusPoints);
        }
        
        return $pointsToDeduct;
    }

    /**
     * Complete a course with reflection.
     */
    public function complete(Course $course, Request $request)
    {
        $request->validate([
            'reflection' => 'required|string|min:50|max:2000',
            'reflection_link' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        
        // Find the user's active enrollment for this course
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNull('completed_at')
            ->first();
        
        if (!$enrollment) {
            return back()->with('error', 'You are not currently enrolled in this course or have already completed it.');
        }

        // Check if all lessons are completed
        $completedLessonsCount = $enrollment->completedLessons()->count();
        $totalLessonsCount = $course->lessons_count;
        
        if ($completedLessonsCount < $totalLessonsCount) {
            return back()->with('error', 'You must complete all lessons before submitting your reflection.');
        }

        try {
            DB::beginTransaction();
            
            // Use the enrollment's completeWithReflection method
            $success = $enrollment->completeWithReflection($request->reflection, $request->reflection_link);
            
            if (!$success) {
                DB::rollBack();
                return back()->with('error', 'Failed to complete the course. Please ensure all lessons are completed.');
            }

            DB::commit();
            
            return redirect()->route('classes.show', $course->id)
                ->with('success', 'Congratulations! You have successfully completed the course with your reflection.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete the course. Please try again.');
        }
    }

    /**
     * Update course reflection for a completed course.
     */
    public function updateReflection(Course $course, Request $request)
    {
        $request->validate([
            'reflection' => 'required|string|min:50|max:2000',
            'reflection_link' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        
        if (!$user) {
            return back()->with('error', 'You must be logged in to update your reflection.');
        }
        
        // Find the user's completed enrollment for this course
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->first();
        
        if (!$enrollment) {
            return back()->with('error', 'You have not completed this course yet.');
        }

        try {
            $enrollment->update([
                'course_reflection' => $request->reflection,
                'course_reflection_link' => $request->reflection_link,
            ]);

            return redirect()->route('classes.show', $course->id)
                ->with('success', 'Your course reflection has been updated successfully.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update reflection. Please try again.');
        }
    }

    /**
     * Load more courses for infinite scroll
     */
    public function loadMoreCourses(Request $request)
    {
        $user = auth()->user()->load(['enrollment.course', 'enrollments']);
        $page = $request->get('page', 1);
        $perPage = 12;
        $offset = ($page - 1) * $perPage;
        
        // Get user's active enrollments - optimized using enrollment_id
        $userEnrollments = [];
        if ($user->enrollment_id) {
            $userEnrollments = [$user->enrollment->course_id];
        }
        
        // Get user's completed enrollments - optimized query
        $userCompletedEnrollments = $user->completedEnrollments()
            ->pluck('course_id')
            ->toArray();
        
        // For completed courses, they should also be considered "enrolled" (user was enrolled in them)
        $userAllEnrollments = array_merge($userEnrollments, $userCompletedEnrollments);
        
        // Get courses with pagination
        $courses = Course::withCount([
                'enrollments as students_count', // Total students (active + completed)
                'enrollments as active_students_count' => function ($query) {
                    $query->whereNull('completed_at');
                },
                'enrollments as completed_students_count' => function ($query) {
                    $query->whereNotNull('completed_at');
                }
            ])
            ->withCount('lessons')
            ->with('tags')
            ->where('is_active', true)
            ->where(function ($query) use ($user) {
                $query->where('is_public', true)
                      ->orWhere('creator_id', $user->id);
            })
            ->orderBy('students_count', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($course) use ($user, $userEnrollments, $userCompletedEnrollments, $userAllEnrollments) {
                return [
                    'id' => $course->id,
                    'title' => $course->name,
                    'description' => $course->description,
                    'students_count' => $course->students_count,
                    'active_students_count' => $course->active_students_count,
                    'completed_students_count' => $course->completed_students_count,
                    'lessons_count' => $course->lessons_count,
                    'can_join' => $user->canCreateCourse(),
                    'is_enrolled' => in_array($course->id, $userAllEnrollments),
                    'is_completed' => in_array($course->id, $userCompletedEnrollments),
                    'is_creator' => $course->creator_id === $user->id,
                    'is_featured' => $course->is_featured,
                    'is_public' => $course->is_public,
                    'tags' => $course->tags->filter(function ($tag) use ($course, $user) {
                        // Show all tags for course creator, only public tags for others
                        return $course->creator_id === $user->id || $tag->is_public;
                    })->map(function ($tag) {
                        return [
                            'id' => $tag->id,
                            'name' => $tag->name,
                        ];
                    }),
                ];
            })
            ->sortBy(function ($course) use ($user) {
                // Sort order: Current class → Completed classes → Created classes → Featured classes → Others by popularity
                $currentCourseId = $user->enrollment ? $user->enrollment->course_id : null;
                
                // Priority 0: Current class
                if ($course['id'] === $currentCourseId) {
                    return 0;
                }
                
                // Priority 1: Completed classes
                if ($course['is_completed']) {
                    return 1;
                }
                
                // Priority 2: Created classes
                if ($course['is_creator']) {
                    return 2;
                }
                
                // Priority 3: Featured classes (if we had a featured flag)
                // For now, we'll use is_featured from the course model
                if (isset($course['is_featured']) && $course['is_featured']) {
                    return 3;
                }
                
                // Priority 4: Other classes (already sorted by students_count)
                return 4;
            })
            ->values(); // Reset array keys

        $hasMore = $courses->count() === $perPage;

        return response()->json([
            'courses' => $courses,
            'hasMore' => $hasMore,
            'nextPage' => $hasMore ? $page + 1 : null,
        ]);
    }
}
