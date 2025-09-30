<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
  BookOpen, 
  ExternalLink, 
  Calendar,
  User
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';

interface LessonSummary {
  id: number;
  summary: string;
  link?: string;
  created_at: string;
  user: {
    id: number;
    username: string;
    full_name: string;
  };
  lesson: {
    id: number;
    title: string;
    module: {
      id: number;
      title: string;
      course: {
        id: number;
        title: string;
        link?: string;
      };
    };
  };
}

interface Props {
  lesson_summaries?: LessonSummary[];
}

withDefaults(defineProps<Props>(), {
  lesson_summaries: () => [],
});

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: '/',
  },
  {
    title: 'Feeds',
    href: '/feeds',
  },
];

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getLessonNumber = (lesson: LessonSummary['lesson']) => {
  // For now, we'll use a simple approach
  // In a real implementation, this would be calculated based on the lesson's position in the module
  return `Lesson ${lesson.id}`;
};
</script>

<template>
  <Head title="Feeds" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 p-6 bg-gradient-to-br from-primary/5 to-secondary/5">
      <!-- Header -->
      <div class="mb-4">
        <h1 class="text-4xl font-bold text-foreground mb-2">
          Learning Feeds
        </h1>
        <p class="text-muted-foreground">
          Discover what others are learning from completed lessons
        </p>
      </div>

      <!-- Feeds Content -->
      <div v-if="lesson_summaries.length === 0" class="text-center py-12 text-muted-foreground">
        <BookOpen class="h-16 w-16 mx-auto mb-4 opacity-30" />
        <p class="text-lg font-medium">No lesson summaries yet</p>
        <p class="text-sm mt-1">Complete some lessons to see summaries here!</p>
      </div>

      <div v-else class="space-y-6">
        <Card v-for="summary in lesson_summaries" :key="summary.id" 
              class="hover:shadow-lg transition-shadow duration-200">
          <CardHeader>
            <!-- Course and Module Info -->
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <BookOpen class="h-4 w-4 text-primary" />
                  <Link 
                    :href="`/classes/${summary.lesson.module.course.id}`"
                    class="font-semibold text-foreground hover:text-primary transition-colors"
                  >
                    {{ summary.lesson.module.course.title }}
                  </Link>
                  <Badge variant="outline" class="text-xs">
                    {{ summary.lesson.module.title }}
                  </Badge>
                </div>
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                  <span>{{ getLessonNumber(summary.lesson) }}</span>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <a 
                  v-if="summary.link"
                  :href="summary.link"
                  target="_blank"
                  class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-foreground bg-background border border-input rounded-md hover:bg-accent hover:text-accent-foreground transition-colors"
                >
                  <ExternalLink class="h-4 w-4" />
                  External Link
                </a>
              </div>
            </div>
          </CardHeader>

          <CardContent>
            <!-- Lesson Summary -->
            <div class="mb-6">
              <h3 class="font-semibold text-foreground mb-2">What I Learned:</h3>
              <p class="text-foreground leading-relaxed">{{ summary.summary }}</p>
            </div>

            <!-- Lesson Link (if exists) -->
            <div v-if="summary.link" class="mb-6">
              <h4 class="font-medium text-foreground mb-2">External Link:</h4>
              <a 
                :href="summary.link" 
                target="_blank" 
                class="inline-flex items-center gap-2 text-primary hover:text-primary/80 transition-colors"
              >
                <ExternalLink class="h-4 w-4" />
                {{ summary.link }}
              </a>
            </div>

            <!-- Footer with User and Date -->
            <div class="flex items-center justify-between pt-4 border-t border-border/50">
              <div class="flex items-center gap-2">
                <User class="h-4 w-4 text-muted-foreground" />
                <Link 
                  :href="`/profile/${summary.user.username}`"
                  class="text-sm font-medium text-foreground hover:text-primary transition-colors"
                >
                  @{{ summary.user.username }}
                </Link>
              </div>
              <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Calendar class="h-4 w-4" />
                <span>{{ formatDate(summary.created_at) }}</span>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>
