<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = \App\Models\Module::all();

        foreach ($modules as $module) {
            // Create 4-12 lessons per module
            $lessonCount = fake()->numberBetween(4, 12);
            
            for ($i = 1; $i <= $lessonCount; $i++) {
                \App\Models\Lesson::create([
                    'module_id' => $module->id,
                    'name' => fake()->sentence(2),
                    'description' => fake()->optional(0.8)->paragraph(),
                    'lesson_order' => $i,
                ]);
            }
        }
    }
}
