<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { 
  BookOpen, 
  ArrowLeft, 
  ExternalLink,
  LogOut,
  CheckCircle,
  Circle,
  Trophy,
  Users
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Lesson {
  id: number;
  name: string;
  description: string;
  order: number;
}

interface Module {
  id: number;
  name: string;
  description: string;
  order: number;
  lessons: Lesson[];
}

interface Props {
  course: {
    id: number;
    title: string;
    description: string;
    link?: string;
    modules: Module[];
    total_lessons: number;
    total_modules: number;
    created_at: string;
  };
  is_enrolled: boolean;
  can_join: boolean;
}

const props = defineProps<Props>();

const leaveCourse = () => {
  if (confirm('Are you sure you want to leave this class? Your progress will be saved but you will need to rejoin to continue.')) {
    router.post(`/classes/${props.course.id}/leave`, {}, {
      preserveScroll: false,
    });
  }
};

const joinCourse = () => {
  router.post(`/classes/${props.course.id}/join`, {}, {
    preserveScroll: false,
  });
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
};
</script>

<template>
  <Head :title="course.title" />
  
  <AppLayout>
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <Button variant="ghost" size="sm" @click="router.visit('/classes')">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Classes
            </Button>
          </div>
          
          <h1 class="text-4xl font-bold text-foreground mb-2">
            {{ course.title }}
          </h1>
          <p class="text-lg text-muted-foreground">
            {{ course.description }}
          </p>
          
          <div class="flex items-center gap-4 mt-4">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <BookOpen class="h-4 w-4" />
              <span>{{ course.total_modules }} modules</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <Trophy class="h-4 w-4" />
              <span>{{ course.total_lessons }} lessons</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Created {{ formatDate(course.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-2">
          <Button 
            v-if="is_enrolled"
            variant="outline"
            @click="leaveCourse"
            class="text-destructive hover:text-destructive"
          >
            <LogOut class="h-4 w-4 mr-2" />
            Leave Class
          </Button>
          <Button 
            v-else-if="can_join"
            variant="default"
            @click="joinCourse"
            class="bg-primary text-primary-foreground hover:bg-primary/90"
          >
            Join Class
          </Button>
          <Button 
            v-if="course.link"
            variant="outline"
            as="a"
            :href="course.link"
            target="_blank"
            rel="noopener noreferrer"
          >
            <ExternalLink class="h-4 w-4 mr-2" />
            Resources
          </Button>
        </div>
      </div>

      <!-- Enrollment Status -->
      <Card v-if="is_enrolled" class="border-primary/30 bg-primary/5">
        <CardContent class="p-4">
          <div class="flex items-center gap-3">
            <CheckCircle class="h-5 w-5 text-primary flex-shrink-0" />
            <div>
              <p class="font-medium text-foreground">You're enrolled in this class</p>
              <p class="text-sm text-muted-foreground">Continue learning and complete lessons to earn points</p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Modules and Lessons -->
      <div class="space-y-4">
        <h2 class="text-2xl font-bold text-foreground">Course Content</h2>
        
        <div class="space-y-4">
          <Card 
            v-for="(module, moduleIndex) in course.modules" 
            :key="module.id"
            class="border-primary/20"
          >
            <CardHeader>
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Badge variant="secondary" class="text-secondary-foreground">
                      Module {{ moduleIndex + 1 }}
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                      {{ module.lessons.length }} lessons
                    </span>
                  </div>
                  <CardTitle class="text-xl">{{ module.name }}</CardTitle>
                  <CardDescription v-if="module.description" class="mt-1">
                    {{ module.description }}
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            
            <CardContent>
              <div class="space-y-2">
                <div 
                  v-for="(lesson, lessonIndex) in module.lessons" 
                  :key="lesson.id"
                  class="flex items-start gap-3 p-3 rounded-lg bg-muted/30 hover:bg-muted/50 transition-colors"
                >
                  <Circle class="h-5 w-5 text-muted-foreground mt-0.5 flex-shrink-0" />
                  <div class="flex-1">
                    <h4 class="font-medium text-foreground">
                      {{ lessonIndex + 1 }}. {{ lesson.name }}
                    </h4>
                    <p v-if="lesson.description" class="text-sm text-muted-foreground mt-1">
                      {{ lesson.description }}
                    </p>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Bottom Actions -->
      <Card class="border-primary/20">
        <CardContent class="p-6">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
              <p class="font-semibold text-foreground">
                Ready to {{ is_enrolled ? 'continue your' : 'start your' }} learning journey?
              </p>
              <p class="text-sm text-muted-foreground">
                {{ is_enrolled 
                  ? 'Keep going and complete all lessons to earn points!' 
                  : 'Join this class and start earning points today!' 
                }}
              </p>
            </div>
            <div class="flex gap-2">
              <Button 
                v-if="!is_enrolled && can_join"
                variant="default"
                size="lg"
                @click="joinCourse"
                class="bg-primary text-primary-foreground hover:bg-primary/90"
              >
                Join Class Now
              </Button>
              <Button 
                v-else-if="is_enrolled"
                variant="outline"
                @click="leaveCourse"
                class="text-destructive hover:text-destructive"
              >
                <LogOut class="h-4 w-4 mr-2" />
                Leave Class
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
