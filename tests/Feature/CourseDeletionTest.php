<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_leaving_empty_course_deletes_course()
    {
        // Create a user who will be the course creator
        $creator = User::factory()->create();
        
        // Create a course
        $course = Course::factory()->create([
            'creator_id' => $creator->id,
            'is_public' => true,
        ]);
        
        // Create an enrollment for the creator
        $enrollment = Enrollment::factory()->create([
            'user_id' => $creator->id,
            'course_id' => $course->id,
        ]);
        
        // Set user's current enrollment
        $creator->update(['enrollment_id' => $enrollment->id]);
        
        // Verify course exists
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
        
        // Leave the course
        $response = $this->actingAs($creator)
            ->post(route('classes.leave', $course));
        
        // Should redirect to classes page
        $response->assertRedirect(route('classes'));
        
        // Course should be deleted because creator left and no other users
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
        
        // User's enrollment_id should be cleared
        $creator->refresh();
        $this->assertNull($creator->enrollment_id);
    }
    
    public function test_creator_leaving_course_with_other_users_keeps_course()
    {
        // Create a course creator
        $creator = User::factory()->create();
        
        // Create another user
        $otherUser = User::factory()->create();
        
        // Create a course
        $course = Course::factory()->create([
            'creator_id' => $creator->id,
            'is_public' => true,
        ]);
        
        // Create enrollments for both users
        $creatorEnrollment = Enrollment::factory()->create([
            'user_id' => $creator->id,
            'course_id' => $course->id,
        ]);
        
        $otherEnrollment = Enrollment::factory()->create([
            'user_id' => $otherUser->id,
            'course_id' => $course->id,
        ]);
        
        // Set creator's current enrollment
        $creator->update(['enrollment_id' => $creatorEnrollment->id]);
        
        // Verify course exists
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $creatorEnrollment->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $otherEnrollment->id]);
        
        // Leave the course
        $response = $this->actingAs($creator)
            ->post(route('classes.leave', $course));
        
        // Should redirect to classes page
        $response->assertRedirect(route('classes'));
        
        // Course should still exist because other user is enrolled
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        
        // Creator's enrollment should be deleted
        $this->assertDatabaseMissing('enrollments', ['id' => $creatorEnrollment->id]);
        
        // Other user's enrollment should still exist
        $this->assertDatabaseHas('enrollments', ['id' => $otherEnrollment->id]);
        
        // Creator's enrollment_id should be cleared
        $creator->refresh();
        $this->assertNull($creator->enrollment_id);
    }
    
    public function test_non_creator_leaving_course_keeps_course()
    {
        // Create a course creator
        $creator = User::factory()->create();
        
        // Create another user
        $user = User::factory()->create();
        
        // Create a course
        $course = Course::factory()->create([
            'creator_id' => $creator->id,
            'is_public' => true,
        ]);
        
        // Create enrollments for both users
        $creatorEnrollment = Enrollment::factory()->create([
            'user_id' => $creator->id,
            'course_id' => $course->id,
        ]);
        
        $userEnrollment = Enrollment::factory()->create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);
        
        // Set user's current enrollment
        $user->update(['enrollment_id' => $userEnrollment->id]);
        
        // Verify course exists
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $creatorEnrollment->id]);
        $this->assertDatabaseHas('enrollments', ['id' => $userEnrollment->id]);
        
        // Leave the course
        $response = $this->actingAs($user)
            ->post(route('classes.leave', $course));
        
        // Should redirect to classes page
        $response->assertRedirect(route('classes'));
        
        // Course should still exist
        $this->assertDatabaseHas('courses', ['id' => $course->id]);
        
        // Creator's enrollment should still exist
        $this->assertDatabaseHas('enrollments', ['id' => $creatorEnrollment->id]);
        
        // User's enrollment should be deleted
        $this->assertDatabaseMissing('enrollments', ['id' => $userEnrollment->id]);
        
        // User's enrollment_id should be cleared
        $user->refresh();
        $this->assertNull($user->enrollment_id);
    }
    
    public function test_creator_can_see_their_created_courses_even_after_leaving()
    {
        // Create a course creator
        $creator = User::factory()->create();
        
        // Create another user
        $otherUser = User::factory()->create();
        
        // Create a course
        $course = Course::factory()->create([
            'creator_id' => $creator->id,
            'is_public' => true,
        ]);
        
        // Create enrollments for both users
        $creatorEnrollment = Enrollment::factory()->create([
            'user_id' => $creator->id,
            'course_id' => $course->id,
        ]);
        
        $otherEnrollment = Enrollment::factory()->create([
            'user_id' => $otherUser->id,
            'course_id' => $course->id,
        ]);
        
        // Set creator's current enrollment
        $creator->update(['enrollment_id' => $creatorEnrollment->id]);
        
        // Leave the course
        $this->actingAs($creator)
            ->post(route('classes.leave', $course));
        
        // Creator should still see the course because they created it
        $response = $this->actingAs($creator)
            ->get(route('classes'));
        
        $response->assertStatus(200);
        $response->assertSee($course->name);
        
        // Other user should also see the course
        $response = $this->actingAs($otherUser)
            ->get(route('classes'));
        
        $response->assertStatus(200);
        $response->assertSee($course->name);
    }
}
