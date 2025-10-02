
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Enrollments table indexes
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['user_id', 'course_id']); // For checking existing enrollments
            $table->index('completed_at'); // For filtering active vs completed
            $table->index(['course_id', 'completed_at']); // For course stats queries
            $table->index('course_reflection'); // For filtering completed courses with reflection
            $table->index(['completed_at', 'course_reflection']); // For completed course queries
        });

        // Completed lessons table indexes
        Schema::table('completed_lessons', function (Blueprint $table) {
            $table->index('enrollment_id'); // Already has foreign key, but explicit index helps
            $table->index(['enrollment_id', 'lesson_id']); // For checking if lesson is completed
            $table->index('created_at'); // For feeds ordering
            $table->index('summary'); // For filtering non-null summaries (feeds)
        });

        // Learning activities table indexes
        Schema::table('learning_activities', function (Blueprint $table) {
            $table->index('user_id'); // Already has foreign key, but explicit for leaderboard
            $table->index('created_at'); // For time-based leaderboards
            $table->index(['user_id', 'created_at']); // Combined for recent activities
            $table->index('points_earned'); // For leaderboard aggregations
        });

        // Courses table indexes
        Schema::table('courses', function (Blueprint $table) {
            $table->index('is_active'); // For filtering active courses
            $table->index('created_at'); // For ordering
            $table->index(['is_active', 'created_at']); // Combined for active courses list
        });

        // Users table indexes
        Schema::table('users', function (Blueprint $table) {
            $table->index('points'); // For leaderboard ordering
            $table->index('enrollment_id'); // Already has foreign key, but explicit index helps
            $table->index(['is_active', 'points']); // For active user leaderboards
        });

        // Lessons table indexes
        Schema::table('lessons', function (Blueprint $table) {
            $table->index('module_id'); // Already has foreign key, but explicit for joins
            $table->index(['module_id', 'lesson_order']); // For ordered lesson retrieval
        });

        // Modules table indexes
        Schema::table('modules', function (Blueprint $table) {
            $table->index('course_id'); // Already has foreign key, but explicit for joins
            $table->index(['course_id', 'module_order']); // For ordered module retrieval
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'course_id']);
            $table->dropIndex(['completed_at']);
            $table->dropIndex(['course_id', 'completed_at']);
        });

        Schema::table('completed_lessons', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id']);
            $table->dropIndex(['enrollment_id', 'lesson_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['summary']);
        });

        Schema::table('learning_activities', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['points_earned']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['is_active', 'created_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['points']);
            $table->dropIndex(['enrollment_id']);
            $table->dropIndex(['is_active', 'points']);
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['module_id']);
            $table->dropIndex(['module_id', 'lesson_order']);
        });

        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['course_id']);
            $table->dropIndex(['course_id', 'module_order']);
        });
    }
};
