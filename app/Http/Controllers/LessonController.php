<?php

namespace App\Http\Controllers;

use App\Models\CompletedLesson;
use App\Models\Lesson;
use App\Models\Enrollment;
use App\Events\AllLessonsCompleted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonController extends Controller
{
    /**
     * Mark a lesson as completed.
     */
    public function complete(Request $request, Lesson $lesson)
    {
        $user = auth()->user()->load('enrollment');
        
        // Validate that user is enrolled
        if (!$user->enrollment_id) {
            return back()->with('error', 'You must be enrolled in a class to complete lessons.');
        }
        
        // Validate that the lesson belongs to the user's enrolled course
        $lesson->load('module.course');
        if ($lesson->module->course_id !== $user->enrollment->course_id) {
            return back()->with('error', 'This lesson does not belong to your enrolled class.');
        }
        
        // Check if lesson is already completed
        $existingCompletion = CompletedLesson::where('enrollment_id', $user->enrollment_id)
            ->where('lesson_id', $lesson->id)
            ->first();
        
        if ($existingCompletion) {
            return back()->with('error', 'You have already completed this lesson.');
        }
        
        // Validate request data
        $validated = $request->validate([
            'summary' => 'required|string|max:1000',
            'link' => 'nullable|url|max:500',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Create completed lesson record
            CompletedLesson::create([
                'enrollment_id' => $user->enrollment_id,
                'lesson_id' => $lesson->id,
                'summary' => $validated['summary'],
                'link' => $validated['link'] ?? null,
            ]);
            
            // Check if all lessons are now completed
            $enrollment = $user->enrollment;
            $completedLessonsCount = $enrollment->completedLessons()->count();
            $totalLessonsCount = $lesson->module->course->lessons_count;
            
            // If all lessons are completed, dispatch event but don't auto-complete course
            if ($completedLessonsCount >= $totalLessonsCount) {
                // Dispatch event for all lessons completed
                event(new AllLessonsCompleted($enrollment));
            }
            
            DB::commit();
            
            $message = 'Lesson completed! Points awarded.';
            if ($completedLessonsCount >= $totalLessonsCount) {
                $message .= ' All lessons completed! You can now submit your course reflection.';
            }
            
            return back()->with('success', $message);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to complete lesson: ' . $e->getMessage());
        }
    }

    /**
     * Show the form to complete a lesson.
     */
    public function showCompleteForm(Lesson $lesson)
    {
        $user = auth()->user()->load('enrollment.course');
        
        // Validate that user is enrolled
        if (!$user->enrollment_id) {
            return redirect()->route('classes')
                ->with('error', 'You must be enrolled in a class to complete lessons.');
        }
        
        // Load lesson with module and course
        $lesson->load('module.course');
        
        // Validate that the lesson belongs to the user's enrolled course
        if ($lesson->module->course_id !== $user->enrollment->course_id) {
            return redirect()->route('classes')
                ->with('error', 'This lesson does not belong to your enrolled class.');
        }
        
        // Check if already completed
        $isCompleted = CompletedLesson::where('enrollment_id', $user->enrollment_id)
            ->where('lesson_id', $lesson->id)
            ->exists();
        
        if ($isCompleted) {
            return redirect()->route('classes.show', $lesson->module->course_id)
                ->with('info', 'You have already completed this lesson.');
        }
        
        return inertia('CompleteLesson', [
            'lesson' => [
                'id' => $lesson->id,
                'name' => $lesson->name,
                'description' => $lesson->description,
                'module' => [
                    'name' => $lesson->module->name,
                ],
                'course' => [
                    'id' => $lesson->module->course->id,
                    'name' => $lesson->module->course->name,
                ],
            ],
        ]);
    }
}
