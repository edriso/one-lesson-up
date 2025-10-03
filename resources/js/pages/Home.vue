<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import LessonCompletionModal from '@/components/LessonCompletionModal.vue';
import CourseReflectionModal from '@/components/CourseReflectionModal.vue';
import UserInfo from '@/components/UserInfo.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { Clock, Medal, TrendingUp, CheckCircle, BadgeCheck, NotebookPen, BookOpen, GraduationCap } from 'lucide-vue-next';
import { ref, computed, onMounted } from 'vue';
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
  course_name?: string;
  course_is_public?: boolean;
  course_id?: number;
  lessons_completed?: number;
  points_earned: number;
  created_at: string;
  user: {
    id: number;
    full_name: string;
    username: string;
    avatar?: string;
  };
}

interface LastCompletedLesson {
  id: number;
  title: string;
  summary: string;
  link?: string;
  completed_at: string;
  module_title: string;
  class_title: string;
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
  last_completed_lesson?: LastCompletedLesson;
}

const props = withDefaults(defineProps<Props>(), {
  upcoming_lessons: () => [],
  recent_activities: () => [],
});

const { formatShortDateTime } = useDateFormatter();

// Modal state
const isModalOpen = ref(false);
const selectedLesson = ref<Lesson | null>(null);

// Course reflection modal state
const isReflectionModalOpen = ref(false);

// Infinite scroll state
const allActivities = ref<Activity[]>([]);
const currentPage = ref(1);
const isLoading = ref(false);
const hasMore = ref(true);

const openCompleteModal = (lesson: Lesson) => {
  selectedLesson.value = lesson;
  isModalOpen.value = true;
};

// Initialize activities with the initial data
allActivities.value = props.recent_activities || [];

// Load more activities function
const loadMoreActivities = async () => {
  if (isLoading.value || !hasMore.value) return;
  
  isLoading.value = true;
  currentPage.value += 1;
  
  try {
    const response = await fetch(`/api/activities/load-more?page=${currentPage.value}`);
    const data = await response.json();
    
    allActivities.value.push(...data.activities);
    hasMore.value = data.hasMore;
  } catch (error) {
    console.error('Failed to load more activities:', error);
    currentPage.value -= 1; // Revert page increment on error
  } finally {
    isLoading.value = false;
  }
};

// Intersection Observer for infinite scroll
const loadMoreTrigger = ref<HTMLElement | null>(null);

onMounted(() => {
  if (loadMoreTrigger.value) {
    const observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting && hasMore.value && !isLoading.value) {
        loadMoreActivities();
      }
    });
    
    observer.observe(loadMoreTrigger.value);
  }
});

const onLessonCompleted = () => {
  // This will be called when the lesson is successfully completed
  // The page will already be refreshed by Inertia
};

const openReflectionModal = () => {
  isReflectionModalOpen.value = true;
};

const closeReflectionModal = () => {
  isReflectionModalOpen.value = false;
};

const onCourseCompleted = () => {
  // Redirect to the course page after completion
  if (props.user.current_class) {
    router.visit(`/classes/${props.user.current_class.id}`);
  }
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: '/',
  },
];

// Computed property for better readability
const hasActiveLessons = computed(() => props.upcoming_lessons.length > 0);
const hasActivities = computed(() => allActivities.value.length > 0);
const isEnrolled = computed(() => !!props.user.enrollment_id);
const hasLastCompletedLesson = computed(() => !!props.last_completed_lesson);

// Check if course is 100% complete and needs reflection
const isCourseComplete = computed(() => {
  if (!props.user.current_class?.modules) return false;
  
  return props.user.current_class.modules.every(module => 
    module.completion_percentage === 100
  );
});

const needsCourseReflection = computed(() => {
  return isEnrolled.value && isCourseComplete.value;
});

// Map activity types to icons
const activityIconMap: Record<string, Component> = {
  lesson_completed: CheckCircle,
  course_completed: Medal,
  course_started: GraduationCap,
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
                    <Card v-if="user.enrollment_id && user.current_class" class="border-primary/20 shadow-sm hover:shadow-md transition-shadow">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <GraduationCap class="h-5 w-5 text-primary" />
                                Current Class: 
                                <span 
                                    class="text-primary hover:text-primary/80 cursor-pointer underline"
                                    @click="router.visit(`/classes/${user.current_class.id}`)"
                                >
                                    {{ user.current_class.title }}
                                </span>
                            </CardTitle>
                            <CardDescription>
                                Continue your learning journey
                            </CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <div v-for="module in user.current_class?.modules" :key="module.id" class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <BookOpen class="h-4 w-4 text-primary" />
                                        <h3 class="font-semibold text-foreground">{{ module.title }}</h3>
                                    </div>
                                    <Badge variant="secondary" class="text-secondary-foreground bg-secondary">
                                        {{ module.completion_percentage }}% Complete
                                    </Badge>
                                </div>
                                <Progress :model-value="module.completion_percentage" class="h-2" />
                            </div>
                            
                            <!-- Course Reflection Prompt when all modules are 100% complete -->
                            <div v-if="needsCourseReflection" class="mt-4 p-4 bg-primary/5 border border-primary/20 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <p class="text-sm text-muted-foreground mb-2">
                                            All lessons completed! Write a quick reflection to finish the class.
                                        </p>
                                        <Button @click="openReflectionModal" size="sm" class="bg-primary hover:bg-primary/90">
                                            <BadgeCheck class="h-4 w-4" />
                                            Complete Class
                                        </Button>
                                    </div>
                                </div>
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
                                <GraduationCap class="h-16 w-16 mx-auto mb-4 opacity-30" />
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
                                            <NotebookPen v-else class="h-5 w-5 text-muted-foreground" />
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

                    <!-- What I Learned Last Lesson -->
                    <Card v-if="hasLastCompletedLesson" class="shadow-sm">
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Notebook class="h-5 w-5 text-primary" />
                                What I Learned Last Lesson
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-foreground mb-1">
                                            {{ last_completed_lesson?.title }}
                                        </h4>
                                        <p class="text-sm text-muted-foreground mb-3">
                                            {{ last_completed_lesson?.module_title }} • {{ last_completed_lesson?.class_title }}
                                        </p>
                                        <p class="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">
                                            {{ last_completed_lesson?.summary }}
                                        </p>
                                        
                                        <!-- Link to work if available -->
                                        <div v-if="last_completed_lesson?.link" class="mt-3">
                                            <a 
                                                :href="last_completed_lesson.link" 
                                                target="_blank" 
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center gap-2 text-sm text-primary hover:text-primary/80 transition-colors"
                                            >
                                                <Notebook class="h-4 w-4" />
                                                View your work
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between pt-2 border-t border-border">
                                    <span class="text-xs text-muted-foreground">
                                        Completed {{ formatShortDateTime(last_completed_lesson?.completed_at || '') }}
                                    </span>
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
                            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
                                <div v-for="activity in allActivities" :key="activity.id" 
                                     class="flex items-start gap-3 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors">
                                    <component :is="getActivityIcon(activity.type)" class="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-foreground">
                                            <span v-if="activity.type === 'lessons_completed'">
                                                Completed {{ activity.lessons_completed || 1 }} lesson(s) in 
                                                <Link v-if="activity.course_is_public && activity.course_id" 
                                                      :href="`/classes/${activity.course_id}`" 
                                                      class="text-primary hover:underline">
                                                    {{ activity.course_name }}
                                                </Link>
                                                <span v-else class="text-foreground">{{ activity.course_name }}</span>
                                            </span>
                                            <span v-else>{{ activity.description }}</span>
                                        </p>
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
                                
                                <!-- Infinite scroll trigger and loading indicator -->
                                <div v-if="hasMore" ref="loadMoreTrigger" class="flex justify-center py-4">
                                    <div v-if="isLoading" class="flex items-center gap-2 text-muted-foreground">
                                        <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                                        <span class="text-sm">Loading more activities...</span>
                                    </div>
                                    <div v-else class="text-sm text-muted-foreground">
                                        Scroll to load more
                                    </div>
                                </div>
                                
                                <div v-else class="flex justify-center py-4">
                                    <span class="text-sm text-muted-foreground">No more activities to load</span>
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

        <!-- Course Reflection Modal -->
        <CourseReflectionModal
          v-if="user.current_class"
          :is-open="isReflectionModalOpen"
          :course="user.current_class ? {
            id: user.current_class.id,
            title: user.current_class.title
          } : undefined"
          @close="closeReflectionModal"
          @success="onCourseCompleted"
        />
    </AppLayout>
</template>