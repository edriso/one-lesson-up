<?php

namespace Database\Seeders;

use App\Models\DailyActivity;
use App\Models\User;
use App\Models\Enrollment;
use App\Enums\TimeBonusType;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DailyActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all active enrollments
        $enrollments = Enrollment::whereNull('completed_at')->with('user')->get();
        
        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            $timezone = $user->timezone ?? 'UTC';
            
            // Create daily activities for the last 30 days
            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::now($timezone)->subDays($i);
                
                // 70% chance of activity on any given day
                if (fake()->boolean(70)) {
                    $lessonsCompleted = fake()->numberBetween(1, 3);
                    
                    // Time bonus logic: 30% chance for morning/evening activities
                    $hasTimeBonus = fake()->boolean(30);
                    $bonusType = $hasTimeBonus ? fake()->randomElement(TimeBonusType::cases()) : null;
                    
                    DailyActivity::create([
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'activity_date' => $date->format('Y-m-d'),
                        'lessons_completed' => $lessonsCompleted,
                        'time_bonus_earned' => $hasTimeBonus,
                        'time_bonus_type' => $bonusType,
                    ]);
                }
            }
        }
        
        // Create some activities for today to populate today's leaderboard
        $todayUsers = User::inRandomOrder()->limit(10)->get();
        
        foreach ($todayUsers as $user) {
            $timezone = $user->timezone ?? 'UTC';
            $today = Carbon::now($timezone)->format('Y-m-d');
            
            // Skip if already has activity for today
            if (DailyActivity::where('user_id', $user->id)->where('activity_date', $today)->exists()) {
                continue;
            }
            
            $enrollment = $user->enrollment;
            if ($enrollment) {
                $lessonsCompleted = fake()->numberBetween(1, 4);
                $hasTimeBonus = fake()->boolean(50); // Higher chance for demo
                $bonusType = $hasTimeBonus ? fake()->randomElement(TimeBonusType::cases()) : null;
                
                DailyActivity::create([
                    'user_id' => $user->id,
                    'enrollment_id' => $enrollment->id,
                    'activity_date' => $today,
                    'lessons_completed' => $lessonsCompleted,
                    'time_bonus_earned' => $hasTimeBonus,
                    'time_bonus_type' => $bonusType,
                ]);
            }
        }
        
        $this->command->info('Created daily activities for existing enrollments and today\'s leaderboard');
    }
}
