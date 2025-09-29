<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = \App\Models\Course::all();

        foreach ($courses as $course) {
            // Create 3-8 modules per course
            $moduleCount = fake()->numberBetween(3, 8);
            
            for ($i = 1; $i <= $moduleCount; $i++) {
                \App\Models\Module::create([
                    'module_order' => $i,
                    'course_id' => $course->id,
                    'name' => fake()->sentence(2),
                    'description' => fake()->optional(0.7)->paragraph(),
                ]);
            }
        }
    }
}
