<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { 
  BookOpen, 
  ExternalLink, 
  GraduationCap,
} from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

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

const { lesson_summaries } = props;

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffTime = Math.abs(now.getTime() - date.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 1) return 'Yesterday';
  if (diffDays < 7) return `${diffDays} days ago`;
  if (diffDays < 30) return `${Math.ceil(diffDays / 7)} weeks ago`;
  
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};
</script>

<template>
  <Head title="Learning Feeds" />

  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-foreground mb-3">
          Learning Feeds
        </h1>
        <p class="text-lg text-muted-foreground max-w-2xl mx-auto">
          Discover insights and learnings shared by the community
        </p>
      </div>

      <!-- Empty State -->
      <div v-if="props.lesson_summaries.length === 0" class="text-center py-16">
        <div class="mx-auto w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mb-6">
          <GraduationCap class="h-12 w-12 text-primary" />
        </div>
        <h3 class="text-xl font-semibold text-foreground mb-2">No learning summaries yet</h3>
        <p class="text-muted-foreground mb-6 max-w-md mx-auto">
          Be the first to share your learning experience! Complete a lesson and add a summary to inspire others.
        </p>
        <Button asChild>
          <Link href="/classes">
            Explore Classes
          </Link>
        </Button>
      </div>

      <!-- Feed Items -->
      <div v-else class="max-w-2xl mx-auto space-y-6">
        <Card v-for="summary in lesson_summaries" :key="summary.id" 
              class="overflow-hidden hover:shadow-md transition-all duration-200 border-l-4 border-l-primary/20">
          
          <!-- Header -->
          <CardHeader class="pb-4">
            <div class="flex items-start gap-4">
              <!-- Course Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-2">
                  <BookOpen class="h-5 w-5 text-primary" />
                  <Link 
                    v-if="summary.lesson?.module?.course?.id"
                    :href="`/classes/${summary.lesson.module.course.id}`"
                    class="font-semibold text-lg text-foreground hover:text-primary transition-colors"
                  >
                    {{ summary.lesson?.module?.course?.title || 'Unknown Course' }}
                  </Link>
                  <span v-else class="font-semibold text-lg text-foreground">
                    {{ summary.lesson?.module?.course?.title || 'Unknown Course' }}
                  </span>
                </div>
                
                <div class="text-sm text-muted-foreground">
                  <span class="font-medium">{{ summary.lesson?.title || 'Unknown Lesson' }}</span>
                </div>
              </div>
              
              <!-- Time -->
              <div class="text-sm text-muted-foreground">
                {{ formatDate(summary.created_at) }}
              </div>
            </div>
          </CardHeader>

          <!-- Content -->
          <CardContent class="pt-0">
            <!-- Summary -->
            <div class="mb-4">
              <p class="text-foreground leading-relaxed">{{ summary.summary || '' }}</p>
            </div>

            <!-- External Link -->
            <div v-if="summary.link" class="mb-4">
              <a 
                :href="summary.link" 
                target="_blank" 
                class="inline-flex items-center gap-2 px-3 py-2 text-sm bg-muted rounded-lg hover:bg-muted/80 transition-colors group"
              >
                <ExternalLink class="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                <span class="font-medium">View Resource</span>
              </a>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center gap-4 pt-4 border-t border-border/50">
              <Link 
                :href="`/profile/${summary.user?.username || ''}`"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors"
              >
                <span>@{{ summary.user?.username || 'unknown' }}</span>
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </AppLayout>
</template>