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
import { computed, ref, onMounted } from 'vue';

interface FeedItem {
  id: number;
  type: 'lesson' | 'course';
  summary: string;
  link?: string;
  created_at: string;
  user: {
    id: number;
    username: string;
    full_name: string;
  };
  lesson?: {
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
  course?: {
    id: number;
    title: string;
    link?: string;
  };
}

interface Props {
  feeds?: FeedItem[];
}

const props = withDefaults(defineProps<Props>(), {
  feeds: () => [],
});

const { formatRelativeDate } = useDateFormatter();

// Computed properties for better readability
const hasFeeds = computed(() => allFeeds.value.length > 0);

// Infinite scroll state
const allFeeds = ref<FeedItem[]>([]);
const currentPage = ref(1);
const isLoading = ref(false);
const hasMore = ref(true);

// Initialize feeds with the initial data
allFeeds.value = props.feeds || [];

// Helper to safely get course title
const getCourseTitle = (feed: FeedItem): string => {
  if (feed.type === 'lesson') {
    return feed.lesson?.module?.course?.title || 'Unknown Course';
  }
  return feed.course?.title || 'Unknown Course';
};

// Helper to safely get lesson title
const getLessonTitle = (feed: FeedItem): string => {
  return feed.lesson?.title || 'Unknown Lesson';
};

// Helper to safely get course ID
const getCourseId = (feed: FeedItem): number | null => {
  if (feed.type === 'lesson') {
    return feed.lesson?.module?.course?.id || null;
  }
  return feed.course?.id || null;
};

// Load more feeds function
const loadMoreFeeds = async () => {
  if (isLoading.value || !hasMore.value) return;
  
  isLoading.value = true;
  currentPage.value += 1;
  
  try {
    const response = await fetch(`/api/feeds/load-more?page=${currentPage.value}`);
    const data = await response.json();
    
    allFeeds.value.push(...data.feeds);
    hasMore.value = data.hasMore;
  } catch (error) {
    console.error('Failed to load more feeds:', error);
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
        loadMoreFeeds();
      }
    });
    
    observer.observe(loadMoreTrigger.value);
  }
});
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
      <div v-if="!hasFeeds" class="text-center py-16">
        <div class="mx-auto w-24 h-24 bg-primary/10 rounded-full flex items-center justify-center mb-6">
          <GraduationCap class="h-12 w-12 text-primary" />
        </div>
        <h3 class="text-xl font-semibold text-foreground mb-2">No learning content yet</h3>
        <p class="text-muted-foreground mb-6 max-w-md mx-auto">
          Be the first to share your learning experience! Complete lessons or courses and add summaries to inspire others.
        </p>
        <Button asChild>
          <Link href="/classes" class="cursor-pointer">
            Explore Classes
          </Link>
        </Button>
      </div>

      <!-- Feed Items -->
      <div v-else class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <Card v-for="feed in allFeeds" :key="feed.id" 
              class="overflow-hidden hover:shadow-md transition-all duration-200 border-l-4 border-l-primary/20">
          
          <!-- Header -->
          <CardHeader class="pb-4">
            <div class="flex items-start gap-4">
              <!-- Course Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <BookOpen class="h-5 w-5 text-primary flex-shrink-0" />
                  <Link 
                    v-if="getCourseId(feed)"
                    :href="`/classes/${getCourseId(feed)}`"
                    class="font-semibold text-lg text-foreground hover:text-primary transition-colors cursor-pointer truncate"
                  >
                    {{ getCourseTitle(feed) }}
                  </Link>
                  <span v-else class="font-semibold text-lg text-foreground truncate">
                    {{ getCourseTitle(feed) }}
                  </span>
                </div>
              </div>
              
              <!-- Badge -->
              <div class="flex items-center gap-2">
                <span v-if="feed.type === 'lesson'" 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary/10 text-primary">
                  Lesson
                </span>
                <span v-else 
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground">
                  Class Completion
                </span>
                <span v-if="feed.type === 'lesson'" class="text-sm text-muted-foreground">
                  {{ getLessonTitle(feed) }}
                </span>
              </div>
            </div>
          </CardHeader>

          <!-- Content -->
          <CardContent class="pt-0">
            <!-- Summary -->
            <div class="mb-4">
              <p class="text-foreground leading-relaxed">{{ feed.summary }}</p>
            </div>

            <!-- External Link -->
            <div v-if="feed.link" class="mb-4">
              <a 
                :href="feed.link" 
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
                :href="`/profile/${feed.user?.username || ''}`"
                class="flex items-center gap-2 text-sm text-muted-foreground hover:text-primary transition-colors cursor-pointer"
              >
                <span>@{{ feed.user?.username || 'unknown' }}</span>
              </Link>

              <!-- Time -->
              <div class="text-sm text-muted-foreground whitespace-nowrap">
                {{ formatRelativeDate(feed.created_at) }}
              </div>
            </div>
          </CardContent>
        </Card>
        
        <!-- Infinite scroll trigger and loading indicator -->
        <div v-if="hasMore" ref="loadMoreTrigger" class="col-span-full flex justify-center py-8">
          <div v-if="isLoading" class="flex items-center gap-2 text-muted-foreground">
            <div class="w-4 h-4 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm">Loading more content...</span>
          </div>
          <div v-else class="text-sm text-muted-foreground">
            Scroll to load more
          </div>
        </div>
        
        <div v-else class="col-span-full flex justify-center py-8">
          <span class="text-sm text-muted-foreground">No more content to load</span>
        </div>
      </div>
    </div>
  </AppLayout>
</template>