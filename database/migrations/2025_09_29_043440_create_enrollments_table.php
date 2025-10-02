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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->text('course_reflection')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('active_days_count')->default(0);
            $table->unsignedSmallInteger('time_bonuses_earned')->default(0);
            $table->unsignedSmallInteger('points_earned')->default(0);
            $table->date('bonus_deadline');
            $table->timestamps();

            // Unique constraint to prevent duplicate enrollments
            $table->unique(['user_id', 'course_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
