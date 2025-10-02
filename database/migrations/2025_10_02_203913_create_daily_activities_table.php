<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TimeBonusType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->unsignedTinyInteger('lessons_completed')->default(0);
            $table->boolean('time_bonus_earned')->default(false);
            $table->enum('time_bonus_type', TimeBonusType::values())->nullable();
            $table->timestamps();
            
            // Unique constraint for user + enrollment + date
            $table->unique(['user_id', 'enrollment_id', 'activity_date']);
            
            // Indexes for performance
            $table->index(['user_id', 'activity_date']);
            $table->index(['enrollment_id', 'activity_date']);
            $table->index(['time_bonus_earned', 'activity_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_activities');
    }
};
