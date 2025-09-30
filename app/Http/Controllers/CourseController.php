<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display a listing of courses.
     */
    public function index()
    {
        $user = auth()->user()->load(['enrollment.course', 'enrollments']);
        
        // Get all active courses with counts
        $courses = Course::withCount(['enrollments', 'lessons'])
            ->where('is_active', true)
            ->latest()
            ->get()
            ->map(function ($course) use ($user) {
                $enrollment = $user->enrollments()
                    ->where('course_id', $course->id)
                    ->where('is_active', true)
                    ->first();
                
                return [
                    'id' => $course->id,
                    'title' => $course->name,
                    'description' => $course->description,
                    'students_count' => $course->enrollments_count,
                    'lessons_count' => $course->lessons_count,
                    'created_at' => $course->created_at->toISOString(),
                    'can_join' => $user->canCreateCourse(), // Use method to check
                    'is_enrolled' => (bool) $enrollment,
                    'is_creator' => $course->creator_id === $user->id,
                ];
            });
        
        return Inertia::render('Courses', [
            'courses' => $courses,
            'can_create_class' => $user->canCreateCourse(),
            'user' => [
                'id' => $user->id,
                'current_enrollment' => $user->enrollment ? [
                    'id' => $user->enrollment->id,
                    'class' => [
                        'id' => $user->enrollment->course->id,
                        'title' => $user->enrollment->course->name,
                    ],
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
        
        return Inertia::render('CreateCourse');
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
            ]);
            
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
                'start_date' => now(),
                'end_date' => now()->addDays($course->deadline_days),
                'is_active' => true,
                'is_completed' => false,
            ]);
            
            // Update user's enrollment_id
            $user->update(['enrollment_id' => $enrollment->id]);
            
            DB::commit();
            
            return redirect()->route('classes')
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
        
        // Check if user is enrolled in this course
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->first();
        
        return Inertia::render('ViewCourse', [
            'course' => [
                'id' => $course->id,
                'title' => $course->name,
                'description' => $course->description,
                'link' => $course->link,
                'modules' => $course->modules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'description' => $module->description,
                        'order' => $module->module_order,
                        'lessons' => $module->lessons->map(function ($lesson) {
                            return [
                                'id' => $lesson->id,
                                'name' => $lesson->name,
                                'description' => $lesson->description,
                                'order' => $lesson->lesson_order,
                            ];
                        }),
                    ];
                }),
                'total_lessons' => $course->lessons_count,
                'total_modules' => $course->modules_count,
                'created_at' => $course->created_at->toISOString(),
            ],
            'is_enrolled' => (bool) $enrollment,
            'can_join' => $user->canCreateCourse(),
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
        
        // Check if already enrolled
        $existingEnrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->first();
        
        if ($existingEnrollment) {
            return back()->with('error', 'You are already enrolled in this class.');
        }
        
        try {
            DB::beginTransaction();
            
            // Create enrollment
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'start_date' => now(),
                'end_date' => now()->addDays($course->deadline_days),
                'is_active' => true,
                'is_completed' => false,
            ]);
            
            // Update user's enrollment_id
            $user->update(['enrollment_id' => $enrollment->id]);
            
            DB::commit();
            
            return redirect()->route('classes')
                ->with('success', 'Successfully joined ' . $course->name . '!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to join class: ' . $e->getMessage());
        }
    }

    /**
     * Leave a course (deactivate enrollment).
     */
    public function leave(Course $course)
    {
        $user = auth()->user()->load('enrollment');
        
        // Find active enrollment for this course
        $enrollment = $user->enrollments()
            ->where('course_id', $course->id)
            ->where('is_active', true)
            ->first();
        
        if (!$enrollment) {
            return back()->with('error', 'You are not enrolled in this class.');
        }
        
        try {
            DB::beginTransaction();
            
            // Deactivate enrollment
            $enrollment->update([
                'is_active' => false,
                'completed_at' => now(),
                'is_completed' => false, // Left, not completed
            ]);
            
            // Clear user's enrollment_id if it matches this enrollment
            if ($user->enrollment_id === $enrollment->id) {
                $user->update(['enrollment_id' => null]);
            }
            
            DB::commit();
            
            return redirect()->route('classes')
                ->with('success', 'You have left ' . $course->name . '.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to leave class: ' . $e->getMessage());
        }
    }
}
