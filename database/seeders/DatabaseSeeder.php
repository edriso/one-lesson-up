<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TagSeeder::class,           // Create tags first (no dependencies)
            UserSeeder::class,          // Create users
            CourseSeeder::class,        // Create courses (depends on users and tags)
            ModuleSeeder::class,        // Create modules (depends on courses)
            LessonSeeder::class,        // Create lessons (depends on modules)
            EnrollmentSeeder::class,    // Create enrollments (depends on users and courses)
            CompletedLessonSeeder::class, // Create completed lessons (depends on enrollments and lessons)
        ]);
    }
}
