<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login, register } from '@/routes';
import { Button } from '@/components/ui/button';
import { Medal, Target, Users, GraduationCap } from 'lucide-vue-next';

interface Props {
  stats?: {
    active_learners: number;
    total_classes: number;
    lessons_completed: number;
  };
}

withDefaults(defineProps<Props>(), {
  stats: () => ({
    active_learners: 0,
    total_classes: 0,
    lessons_completed: 0,
  }),
});

// Get app name from environment variable
const appName = import.meta.env.VITE_APP_NAME || 'One Lesson Up';

// Get current year dynamically
const currentYear = new Date().getFullYear();

// Format numbers with K, M suffixes
const formatNumber = (num: number): string => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M+';
  }
  if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K+';
  }
  return num.toString() + '+';
};
</script>

<template>
  <Head title="Welcome to One Lesson Up" />
  
  <div class="min-h-screen bg-gradient-to-br from-primary/10 via-background to-secondary/10">
    <!-- Navigation -->
    <nav class="border-b border-border/40 bg-background/80 backdrop-blur-sm">
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <GraduationCap class="h-8 w-8 text-primary" />
            <h1 class="text-2xl font-bold text-foreground">{{ appName }}</h1>
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
    <div class="container mx-auto px-4 py-16">
      <div class="max-w-4xl mx-auto text-center">
        <h2 class="text-4xl md:text-5xl font-bold text-foreground mb-6">
          Stop Abandoning Courses.<br />
          <span class="text-primary">Start Finishing Them.</span>
        </h2>
        <p class="text-lg text-muted-foreground mb-8 max-w-2xl mx-auto">
          Gamify your learning with points, deadlines, and community. Complete classes you actually start.
        </p>
        
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link :href="register()">
            <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90 text-lg px-8 py-4">
              <Target class="mr-2 h-5 w-5" />
              Start Learning Free
            </Button>
          </Link>
          <Link :href="login()">
            <Button variant="outline" size="lg" class="text-lg px-8 py-4">
              Sign In
            </Button>
          </Link>
        </div>
      </div>
    </div>

    <!-- Features Section -->
    <div class="container mx-auto px-4 py-12">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-5xl mx-auto">
        <!-- Feature 1 -->
        <div class="text-center p-6">
          <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
            <Target class="h-8 w-8 text-primary" />
          </div>
          <h3 class="text-lg font-semibold text-foreground mb-2">One Course at a Time</h3>
          <p class="text-muted-foreground text-sm">
            Focus on completing one course before starting another. No more scattered learning.
          </p>
        </div>

        <!-- Feature 2 -->
        <div class="text-center p-6">
          <div class="w-16 h-16 rounded-full bg-secondary/10 flex items-center justify-center mx-auto mb-4">
            <Medal class="h-8 w-8 text-secondary-foreground" />
          </div>
          <h3 class="text-lg font-semibold text-foreground mb-2">Earn Points & Bonuses</h3>
          <p class="text-muted-foreground text-sm">
            Get points for lessons, time bonuses for morning/evening learning, and course completion bonuses.
          </p>
        </div>

        <!-- Feature 3 -->
        <div class="text-center p-6">
          <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4">
            <Users class="h-8 w-8 text-primary" />
          </div>
          <h3 class="text-lg font-semibold text-foreground mb-2">Community & Competition</h3>
          <p class="text-muted-foreground text-sm">
            See what others are learning, compete on leaderboards, and stay motivated together.
          </p>
        </div>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-3xl mx-auto bg-gradient-to-r from-primary/10 to-secondary/10 rounded-xl p-8 text-center">
        <h3 class="text-2xl font-bold text-foreground mb-6">Join Active Learners</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div>
            <div class="text-3xl font-bold text-primary mb-1">{{ formatNumber(stats.active_learners) }}</div>
            <div class="text-sm text-muted-foreground">Active Learners</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-primary mb-1">{{ formatNumber(stats.total_classes) }}</div>
            <div class="text-sm text-muted-foreground">Classes</div>
          </div>
          <div>
            <div class="text-3xl font-bold text-primary mb-1">{{ formatNumber(stats.lessons_completed) }}</div>
            <div class="text-sm text-muted-foreground">Lessons Completed</div>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="container mx-auto px-4 py-12">
      <div class="max-w-2xl mx-auto text-center">
        <h3 class="text-2xl font-bold text-foreground mb-4">
          Ready to Finish What You Start?
        </h3>
        <p class="text-muted-foreground mb-6">
          Join learners who are actually completing their courses.
        </p>
        <Link :href="register()">
          <Button size="lg" class="bg-primary text-primary-foreground hover:bg-primary/90 text-lg px-8 py-4">
            <Medal class="mr-2 h-5 w-5" />
            Start Learning Free
          </Button>
        </Link>
      </div>
    </div>

    <!-- Footer -->
    <footer class="border-t border-border/40 bg-background/80 backdrop-blur-sm mt-12">
      <div class="container mx-auto px-4 py-6">
        <div class="text-center text-muted-foreground">
          <p>&copy; {{ currentYear }} {{ appName }}. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>
