<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useDateFormatter } from '@/composables/useDateFormatter';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ExternalLink, GraduationCap, Notebook } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

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
        return feed.lesson?.module?.course?.title || 'Unknown Class';
    }
    return feed.course?.title || 'Unknown Class';
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
        const response = await fetch(
            `/api/feeds/load-more?page=${currentPage.value}`,
        );
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
            if (
                entries[0].isIntersecting &&
                hasMore.value &&
                !isLoading.value
            ) {
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
                <h1 class="mb-3 text-4xl font-bold text-foreground">
                    Learning Feeds
                </h1>
                <p class="text-muted-foreground">
                    Discover insights and learnings shared by the community
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="!hasFeeds" class="py-16 text-center">
                <div
                    class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full bg-primary/10"
                >
                    <GraduationCap class="h-12 w-12 text-primary" />
                </div>
                <h3 class="mb-2 text-xl font-semibold text-foreground">
                    No learning content yet
                </h3>
                <p class="mx-auto mb-6 max-w-md text-muted-foreground">
                    Be the first to share your learning experience! Complete
                    lessons or courses and add summaries to inspire others.
                </p>
                <Button asChild>
                    <Link href="/classes" class="cursor-pointer">
                        Explore Classes
                    </Link>
                </Button>
            </div>

            <!-- Feed Items -->
            <div v-else class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                <Card
                    v-for="feed in allFeeds"
                    :key="feed.id"
                    class="overflow-hidden border-l-4 border-l-primary/20 transition-all duration-200 hover:shadow-md"
                >
                    <!-- Header -->
                    <CardHeader class="pb-4">
                        <div class="flex items-start gap-4">
                            <!-- Course Info -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <Notebook
                                        class="h-5 w-5 flex-shrink-0 text-primary"
                                    />
                                    <Link
                                        v-if="getCourseId(feed)"
                                        :href="`/classes/${getCourseId(feed)}`"
                                        class="cursor-pointer truncate text-lg font-semibold text-foreground transition-colors hover:text-primary"
                                    >
                                        {{ getCourseTitle(feed) }}
                                    </Link>
                                    <span
                                        v-else
                                        class="truncate text-lg font-semibold text-foreground"
                                    >
                                        {{ getCourseTitle(feed) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Badge -->
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="feed.type === 'lesson'"
                                    class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary"
                                >
                                    Lesson
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground"
                                >
                                    Class Completion
                                </span>
                                <span
                                    v-if="feed.type === 'lesson'"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ getLessonTitle(feed) }}
                                </span>
                            </div>
                        </div>
                    </CardHeader>

                    <!-- Content -->
                    <CardContent class="pt-0">
                        <!-- Summary -->
                        <div class="mb-4">
                            <p class="leading-relaxed text-foreground">
                                {{ feed.summary }}
                            </p>
                        </div>

                        <!-- External Link -->
                        <div v-if="feed.link" class="mb-4">
                            <a
                                :href="feed.link"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-accent px-3 py-2 text-sm text-muted-foreground transition-colors hover:bg-primary hover:text-accent-foreground"
                            >
                                <ExternalLink class="h-4 w-4" />
                                <span class="font-medium">View Resource</span>
                            </a>
                        </div>

                        <!-- Footer Actions -->
                        <div
                            class="flex items-center justify-between gap-4 border-t border-border/50 pt-4"
                        >
                            <Link
                                :href="`/profile/${feed.user?.username || ''}`"
                                class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-primary"
                            >
                                <span
                                    >@{{
                                        feed.user?.username || 'unknown'
                                    }}</span
                                >
                            </Link>

                            <!-- Time -->
                            <div
                                class="text-sm whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatRelativeDate(feed.created_at) }}
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Infinite scroll trigger and loading indicator -->
                <div
                    v-if="hasMore"
                    ref="loadMoreTrigger"
                    class="col-span-full flex justify-center py-8"
                >
                    <div
                        v-if="isLoading"
                        class="flex items-center gap-2 text-muted-foreground"
                    >
                        <div
                            class="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"
                        ></div>
                        <span class="text-sm">Loading more content...</span>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        Scroll to load more
                    </div>
                </div>

                <div v-else class="col-span-full flex justify-center py-8">
                    <span class="text-sm text-muted-foreground"
                        >No more content to load</span
                    >
                </div>
            </div>
        </div>
    </AppLayout>
</template>
