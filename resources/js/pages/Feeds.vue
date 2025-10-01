<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { 
  BookOpen, 
  ExternalLink, 
  GraduationCap,
} from 'lucide-vue-next';
import { computed } from 'vue';

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
    title: string; // Receives lesson.name from backend
    module: {
      id: number;
      title: string; // Receives module.name from backend
      course: {
        id: number;
        title: string; // Receives course.name from backend
        link?: string;
      };
    };
  };
}

interface Props {
  lesson_summaries?: LessonSummary[];
}

const props = withDefaults(defineProps<Props>(), {
  lesson_summaries: () => [],
});

const { formatRelativeDate } = useDateFormatter();

// Computed properties for better readability
const hasSummaries = computed(() => props.lesson_summaries.length > 0);

// Helper to safely get course title
const getCourseTitle = (summary: LessonSummary): string => {
  return summary.lesson?.module?.course?.title || 'Unknown Course';
};

// Helper to safely get lesson title
const getLessonTitle = (summary: LessonSummary): string => {
  return summary.lesson?.title || 'Unknown Lesson';
};

// Helper to safely get course ID
const getCourseId = (summary: LessonSummary): number | null => {
  return summary.lesson?.module?.course?.id || null;
};
</script>

<template>
  <Head title="Learning Feeds" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div>
        <h1 class="text-4xl font-bold text-foreground mb-3">
          Learning Feeds
        </h1>
        <p class="text-muted-foreground">
          Discover insights and learnings shared by the community
        </p>
      </div>

      <!-- Empty State -->
      <div v-if="!hasSummaries" class="text-center py-16">
        <div class="mx-auto w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mb-6">
          <GraduationCap class="h-12 w-12 text-primary" />
        </div>
        <h3 class="text-xl font-semibold text-foreground mb-2">No learning summaries yet</h3>
        <p class="text-muted-foreground mb-6 max-w-md mx-auto">
          Be the first to share your learning experience! Complete a lesson and add a summary to inspire others.
        </p>
        <Button asChild>
          <Link href="/classes" class="cursor-pointer">
            Explore Classes
          </Link>
        </Button>
      </div>

      <!-- Feed Items -->
      <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card v-for="summary in lesson_summaries" :key="summary.id" 
              class="overflow-hidden hover:shadow-md transition-all duration-200 border-l-4 border-l-primary/20">
          
          <!-- Header -->
          <CardHeader class="pb-4">
            <div class="flex items-start gap-4">
              <!-- Course Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <BookOpen class="h-5 w-5 text-primary flex-shrink-0" />
                  <Link 
                    v-if="getCourseId(summary)"
                    :href="`/classes/${getCourseId(summary)}`"
                    class="font-semibold text-lg text-foreground hover:text-primary transition-colors cursor-pointer truncate"
                  >
                    {{ getCourseTitle(summary) }}
                  </Link>
                  <span v-else class="font-semibold text-lg text-foreground truncate">
                    {{ getCourseTitle(summary) }}
                  </span>
                </div>
              </div>
              
              <div class="text-sm text-muted-foreground whitespace-nowrap">
                Lesson: <span class="font-medium">{{ getLessonTitle(summary) }}</span>
              </div>
            </div>
          </CardHeader>

          <!-- Content -->
          <CardContent class="pt-0">
            <!-- Summary -->
            <div class="mb-4">
              <p class="text-foreground leading-relaxed">{{ summary.summary }}</p>
            </div>

            <!-- External Link -->
            <div v-if="summary.link" class="mb-4">
              <a 
                :href="summary.link" 
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-accent text-muted-foreground hover:bg-primary hover:text-accent-foreground rounded-lg transition-colors cursor-pointer"
              >
                <ExternalLink class="h-4 w-4" />
                <span class="font-medium">View Resource</span>
              </a>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between gap-4 pt-4 border-t border-border/50">
              <Link 
                :href="`/profile/${summary.user?.username || ''}`"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors cursor-pointer"
              >
                <span>@{{ summary.user?.username || 'unknown' }}</span>
              </Link>

              <!-- Time -->
              <div class="text-sm text-muted-foreground whitespace-nowrap">
                {{ formatRelativeDate(summary.created_at) }}
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>