<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import { Button } from '@/components/ui/button';
import { BookOpen, Trophy, Users, TrendingUp } from 'lucide-vue-next';
</script>

<template>
  <Head title="Welcome to One Lesson Up" />
  
  <div class="min-h-screen bg-gradient-to-br from-primary/10 via-background to-secondary/10">
    <!-- Navigation -->
    <nav class="border-b border-border/40 bg-background/80 backdrop-blur-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <BookOpen class="h-8 w-8 text-primary" />
            <h1 class="text-2xl font-bold text-foreground">One Lesson Up</h1>
          </div>
          
          <div class="flex items-center gap-4">
            <Link :href="login()">
              <Button variant="outline">Log in</Button>
            </Link>
            <Link :href="register()">
              <Button variant="default" class="bg-primary text-primary-foreground hover:bg-primary/90">
                Get Started
              </Button>
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <div class="container mx-auto px-4 py-20">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-5xl md:text-6xl font-bold text-foreground mb-6">
          Level Up Your Learning,<br />
          <span class="text-primary">One Lesson at a Time</span>
        </h2>
        <p class="text-xl text-muted-foreground mb-8 max-w-2xl mx-auto">
          Join a community of learners, track your progress, earn points, and compete on the leaderboard. 
          Transform your learning journey into an engaging adventure.
        </p>
        
        <div class="flex items-center justify-center gap-4">
          <Link :href="register()">
            <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90 text-lg px-8 py-6">
              <BookOpen class="mr-2 h-5 w-5" />
              Start Learning Free
            </Button>
          </Link>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="container mx-auto px-4 py-16">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <!-- Feature 1 -->
        <div class="bg-card border border-border rounded-lg p-8 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4">
            <BookOpen class="h-6 w-6 text-primary" />
          </div>
          <h3 class="text-xl font-semibold text-foreground mb-2">Interactive Classes</h3>
          <p class="text-muted-foreground">
            Join engaging classes, complete lessons, and track your progress with detailed analytics.
          </p>
        </div>

        <!-- Feature 2 -->
        <div class="bg-card border border-border rounded-lg p-8 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-secondary/10 flex items-center justify-center mb-4">
            <Trophy class="h-6 w-6 text-secondary-foreground" />
          </div>
          <h3 class="text-xl font-semibold text-foreground mb-2">Earn Points & Badges</h3>
          <p class="text-muted-foreground">
            Complete lessons to earn points, unlock achievements, and customize your profile.
          </p>
        </div>

        <!-- Feature 3 -->
        <div class="bg-card border border-border rounded-lg p-8 shadow-sm hover:shadow-md transition-shadow">
          <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4">
            <TrendingUp class="h-6 w-6 text-primary" />
          </div>
          <h3 class="text-xl font-semibold text-foreground mb-2">Compete & Grow</h3>
          <p class="text-muted-foreground">
            Climb the leaderboard, compete with fellow ninjas, and showcase your learning journey.
          </p>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="container mx-auto px-4 py-16">
      <div class="max-w-4xl mx-auto bg-gradient-to-r from-primary/20 to-secondary/20 rounded-2xl p-12 text-center">
        <h3 class="text-3xl font-bold text-foreground mb-8">Join the Learning Revolution</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div>
            <div class="text-4xl font-bold text-primary mb-2">1000+</div>
            <div class="text-muted-foreground">Active Learners</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-primary mb-2">50+</div>
            <div class="text-muted-foreground">Classes</div>
          </div>
          <div>
            <div class="text-4xl font-bold text-primary mb-2">10K+</div>
            <div class="text-muted-foreground">Lessons Completed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="container mx-auto px-4 py-16">
      <div class="max-w-2xl mx-auto text-center">
        <h3 class="text-3xl font-bold text-foreground mb-4">
          Ready to Start Your Journey?
        </h3>
        <p class="text-muted-foreground mb-8">
          Join thousands of learners already leveling up their skills.
        </p>
        <Link :href="register()">
          <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90 text-lg px-8 py-6">
            Get Started for Free
          </Button>
        </Link>
      </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-border/40 bg-background/80 backdrop-blur-sm mt-20">
      <div class="container mx-auto px-4 py-8">
        <div class="text-center text-muted-foreground">
          <p>&copy; 2025 One Lesson Up. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>
