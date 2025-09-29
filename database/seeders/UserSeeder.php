<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        \App\Models\User::firstOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@onelessonup.com',
                'password' => bcrypt('password'),
                'full_name' => 'Admin User',
                'title' => 'Platform Administrator',
                'bio' => 'Administrator of One Lesson Up platform',
                'is_public' => true,
                'is_active' => true,
            ]
        );

        // Create demo users
        \App\Models\User::factory()
            ->count(20)
            ->create();

        // Create private profile users
        \App\Models\User::factory()
            ->count(5)
            ->create()
            ->each(function ($user) {
                $user->update([
                    'is_public' => false,
                ]);
            });
    }
}
