<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import LessonCompletionModal from '@/components/LessonCompletionModal.vue';
import UserInfo from '@/components/UserInfo.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { Clock, BookOpen, Medal, TrendingUp, CheckCircle, Circle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import type { Component } from 'vue';

interface Lesson {
  id: number;
  title: string;
  completed: boolean;
  due_date?: string;
  module_title: string;
  class_title: string;
}

interface Activity {
  id: number;
  type: string;
  description: string;
  points_earned: number;
  created_at: string;
  user: {
    id: number;
    full_name: string;
    username: string;
    avatar?: string;
  };
}

interface Props {
  user: {
    id: number;
    full_name: string;
    username: string;
    points: number;
    avatar?: string;
    enrollment_id?: number;
    current_class?: {
      id: number;
      title: string;
      modules: Array<{
        id: number;
        title: string;
        lessons: Lesson[];
        completion_percentage: number;
      }>;
    };
  };
  upcoming_lessons?: Lesson[];
  recent_activities?: Activity[];
}

const props = withDefaults(defineProps<Props>(), {
  upcoming_lessons: () => [],
  recent_activities: () => [],
});

const { formatShortDateTime } = useDateFormatter();

// Modal state
const isModalOpen = ref(false);
const selectedLesson = ref<Lesson | null>(null);

const openCompleteModal = (lesson: Lesson) => {
  selectedLesson.value = lesson;
  isModalOpen.value = true;
};

const onLessonCompleted = () => {
  // This will be called when the lesson is successfully completed
  // The page will already be refreshed by Inertia
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: '/',
  },
];

// Computed property for better readability
const hasActiveLessons = computed(() => props.upcoming_lessons.length > 0);
const hasActivities = computed(() => props.recent_activities.length > 0);
const isEnrolled = computed(() => !!props.user.enrollment_id);

// Map activity types to icons
const activityIconMap: Record<string, Component> = {
  lesson_completed: CheckCircle,
  course_completed: Medal,
  course_started: BookOpen,
};

const getActivityIcon = (type: string): Component => {
  return activityIconMap[type] ?? TrendingUp;
};
</script>

<template>
    <Head title="Home" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 bg-gradient-to-br from-primary/5 to-secondary/5">
            <!-- Welcome Section -->
            <div class="mb-4">
                <h1 class="text-4xl font-bold text-foreground mb-2">
                    Welcome back, {{ user.full_name }}!
                </h1>
                <div class="flex items-center gap-4 text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <Medal class="h-5 w-5 text-secondary" />
                        <span class="font-semibold">{{ user.points }} points</span>
                    </div>
                    <div class="h-4 w-px bg-border"></div>
                    <span>{{ recent_activities.length }} activities this week</span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Class Progress -->
                    <Card v-if="user.enrollment_id && user.current_class" class="border-primary/20 shadow-sm hover:shadow-md transition-shadow cursor-pointer" @click="router.visit(`/classes/${user.current_class.id}`)">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <BookOpen class="h-5 w-5 text-primary" />
                                Current Class: {{ user.current_class.title }}
                            </CardTitle>
                            <CardDescription>
                                Continue your learning journey
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div v-for="module in user.current_class?.modules" :key="module.id" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-foreground">{{ module.title }}</h3>
                                    <Badge variant="secondary" class="text-secondary-foreground bg-secondary">
                                        {{ module.completion_percentage }}% Complete
                                    </Badge>
                                </div>
                                <Progress :value="module.completion_percentage" class="h-2" />
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Upcoming Lessons -->
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5 text-primary" />
                                Upcoming Lessons
                            </CardTitle>
                            <CardDescription>
                                Your next learning sessions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div v-if="!hasActiveLessons" class="text-center py-12 text-muted-foreground">
                                <BookOpen class="h-16 w-16 mx-auto mb-4 opacity-30" />
                                <p class="text-lg font-medium">No upcoming lessons</p>
                                <p class="text-sm mt-1">Join a class to start learning!</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="lesson in upcoming_lessons" :key="lesson.id" 
                                     class="flex flex-wrap gap-y-4 items-center justify-between p-4 rounded-lg border transition-all hover:shadow-md"
                                     :class="[
                                        lesson.completed ? 'bg-muted/50 border-muted' : 'bg-background border-border hover:border-primary/30',
                                        !isEnrolled ? 'opacity-50' : ''
                                     ]">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0">
                                            <CheckCircle v-if="lesson.completed" class="h-5 w-5 text-green-500" />
                                            <Circle v-else class="h-5 w-5 text-muted-foreground" />
                                        </div>
                                        <div>
                                            <h4 class="font-medium" :class="lesson.completed ? 'line-through text-muted-foreground' : 'text-foreground'">
                                                {{ lesson.title }}
                                            </h4>
                                            <p class="text-sm text-muted-foreground">
                                                {{ lesson.module_title }} • {{ lesson.class_title }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Badge v-if="lesson.due_date" variant="outline" class="text-xs">
                                            Due {{ formatShortDateTime(lesson.due_date) }}
                                        </Badge>
                                        <Button 
                                            v-if="!lesson.completed && isEnrolled" 
                                            size="sm" 
                                            class="bg-primary text-primary-foreground hover:bg-primary/90" 
                                            @click="openCompleteModal(lesson)"
                                        >
                                            Complete Lesson
                                        </Button>
                                        <div v-else-if="!lesson.completed && !isEnrolled" class="text-sm text-muted-foreground">
                                            Join a class to complete lessons
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Recent Activities -->
                    <Card class="shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <TrendingUp class="h-5 w-5 text-primary" />
                                Recent Activities
                            </CardTitle>
                            <CardDescription>
                                Your learning progress
                            </CardDescription>
                        </CardHeader>
                        <CardContent>                            
                            <div v-if="!hasActivities" class="text-center py-8 text-muted-foreground">
                                <TrendingUp class="h-12 w-12 mx-auto mb-3 opacity-30" />
                                <p class="text-sm">No recent activities</p>
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="activity in recent_activities.slice(0, 5)" :key="activity.id" 
                                     class="flex items-start gap-3 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors">
                                    <component :is="getActivityIcon(activity.type)" class="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground">{{ activity.description }}</p>
                                        <div class="flex items-center justify-between mt-2">
                                            <div class="flex items-center gap-2">
                                                <Link :href="`/profile/${activity.user.username}`" class="flex items-center gap-2 hover:bg-muted/50 rounded-md p-1 -m-1 transition-colors">
                                                    <UserInfo :user="{ 
                                                        id: activity.user.id, 
                                                        avatar: activity.user.avatar, 
                                                        full_name: activity.user.full_name,
                                                    }" />
                                                </Link>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs text-muted-foreground">{{ formatShortDateTime(activity.created_at) }}</span>
                                                <span v-if="activity.points_earned > 0" class="text-xs font-medium text-secondary-foreground bg-secondary/20 px-1.5 py-0.5 rounded">
                                                    +{{ activity.points_earned }} pts
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>

        <!-- Complete Lesson Modal -->
        <LessonCompletionModal 
          :is-open="isModalOpen"
          :lesson="selectedLesson"
          @update:is-open="isModalOpen = $event"
          @completed="onLessonCompleted"
        />
    </AppLayout>
</template>