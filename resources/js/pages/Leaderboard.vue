<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Star, TrendingUp, Calendar, Crown } from 'lucide-vue-next';
import LeaderboardList from '@/components/LeaderboardList.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface LeaderboardEntry {
  id: number;
  rank: number;
  user: {
    id: number;
    full_name: string;
    username: string;
    avatar?: string;
  };
  points?: number;
  activities_count?: number;
  lessons_completed?: number;
  has_time_bonus?: boolean;
  bonus_type?: string;
  activity_date?: string;
}

interface Props {
  leaderboards?: {
    today: LeaderboardEntry[];
    yesterday: LeaderboardEntry[];
    this_month: LeaderboardEntry[];
    overall: LeaderboardEntry[];
  };
  current_user_rank?: {
    today: number;
    yesterday: number;
    this_month: number;
    overall: number;
  };
  user?: {
    id: number;
    full_name: string;
    username: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  leaderboards: () => ({
    today: [],
    yesterday: [],
    this_month: [],
    overall: [],
  }),
  current_user_rank: () => ({
    today: 0,
    yesterday: 0,
    this_month: 0,
    overall: 0,
  }),
  user: undefined,
});

const getCurrentUserRankText = (period: keyof typeof props.current_user_rank) => {
  const rank = props.current_user_rank?.[period];
  if (!rank || rank === 0) return '-';
  return `#${rank}`;
};

// Infinite scroll state
const allLeaderboards = ref<{
  today: LeaderboardEntry[];
  yesterday: LeaderboardEntry[];
  this_month: LeaderboardEntry[];
  overall: LeaderboardEntry[];
}>({
  today: props.leaderboards?.today || [],
  yesterday: props.leaderboards?.yesterday || [],
  this_month: props.leaderboards?.this_month || [],
  overall: props.leaderboards?.overall || [],
});

const currentPage = ref<{
  today: number;
  yesterday: number;
  this_month: number;
  overall: number;
}>({
  today: 1,
  yesterday: 1,
  this_month: 1,
  overall: 1,
});

const isLoading = ref<{
  today: boolean;
  yesterday: boolean;
  this_month: boolean;
  overall: boolean;
}>({
  today: false,
  yesterday: false,
  this_month: false,
  overall: false,
});

const hasMore = ref<{
  today: boolean;
  yesterday: boolean;
  this_month: boolean;
  overall: boolean;
}>({
  today: true,
  yesterday: true,
  this_month: true,
  overall: true,
});

const activeTab = ref('today');

const loadMoreEntries = async (period: keyof typeof allLeaderboards.value) => {
  if (isLoading.value[period] || !hasMore.value[period]) return;

  isLoading.value[period] = true;
  currentPage.value[period]++;

  try {
    const response = await fetch(`/api/leaderboard/load-more?period=${period}&page=${currentPage.value[period]}`);
    const data = await response.json();

    allLeaderboards.value[period].push(...data.leaderboard);
    hasMore.value[period] = data.has_more;
  } catch (error) {
    console.error('Failed to load more entries:', error);
  } finally {
    isLoading.value[period] = false;
  }
};

const setupIntersectionObserver = () => {
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        loadMoreEntries(activeTab.value as keyof typeof allLeaderboards.value);
      }
    });
  });

  // Observe all load-more-trigger elements
  const loadMoreTriggers = document.querySelectorAll('#load-more-trigger');
  loadMoreTriggers.forEach(trigger => {
    observer.observe(trigger);
  });

  return observer;
};

let observer: IntersectionObserver | null = null;

onMounted(() => {
  observer = setupIntersectionObserver();
});

onUnmounted(() => {
  if (observer) {
    observer.disconnect();
  }
});
</script>

<template>
  <Head title="Leaderboard" />
  
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-foreground mb-2">
          Leaderboard
        </h1>
        <p class="text-muted-foreground">
          See how you stack up against other learners
        </p>
      </div>

      <!-- Current User Ranking Summary -->
      <div v-if="user" class="mb-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Star class="h-5 w-5 text-primary" />
              Your Current Rankings
            </CardTitle>
            <CardDescription>
              Your position across different time periods
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div class="text-center p-3 rounded-lg bg-primary/10">
                <p class="text-sm text-muted-foreground">Today</p>
                <p class="text-lg font-bold text-foreground">{{ getCurrentUserRankText('today') }}</p>
              </div>
              <div class="text-center p-3 rounded-lg bg-primary/10">
                <p class="text-sm text-muted-foreground">Yesterday</p>
                <p class="text-lg font-bold text-foreground">{{ getCurrentUserRankText('yesterday') }}</p>
              </div>
              <div class="text-center p-3 rounded-lg bg-primary/10">
                <p class="text-sm text-muted-foreground">This Month</p>
                <p class="text-lg font-bold text-foreground">{{ getCurrentUserRankText('this_month') }}</p>
              </div>
              <div class="text-center p-3 rounded-lg bg-primary/10">
                <p class="text-sm text-muted-foreground">Overall</p>
                <p class="text-lg font-bold text-foreground">{{ getCurrentUserRankText('overall') }}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Time Period Tabs -->
      <Tabs v-model="activeTab" default-value="today" class="w-full">
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="today">Today</TabsTrigger>
          <TabsTrigger value="yesterday">Yesterday</TabsTrigger>
          <TabsTrigger value="this_month">This Month</TabsTrigger>
          <TabsTrigger value="overall">Overall</TabsTrigger>
        </TabsList>

        <!-- Today Leaderboard -->
        <TabsContent value="today" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Calendar class="h-5 w-5 text-primary" />
                Today's Top Performers
              </CardTitle>
              <CardDescription>
                Lessons completed today
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="allLeaderboards.today"
                :current-user-id="user?.id"
                empty-message="No activities today"
              />
              <div v-if="hasMore.today" id="load-more-trigger" class="h-10 flex items-center justify-center">
                <div v-if="isLoading.today" class="text-sm text-muted-foreground">Loading more...</div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Yesterday Leaderboard -->
        <TabsContent value="yesterday" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Calendar class="h-5 w-5 text-primary" />
                Yesterday's Top Performers
              </CardTitle>
              <CardDescription>
                Lessons completed yesterday
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="allLeaderboards.yesterday"
                :current-user-id="user?.id"
                empty-message="No activities yesterday"
              />
              <div v-if="hasMore.yesterday" id="load-more-trigger" class="h-10 flex items-center justify-center">
                <div v-if="isLoading.yesterday" class="text-sm text-muted-foreground">Loading more...</div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- This Month Leaderboard -->
        <TabsContent value="this_month" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <TrendingUp class="h-5 w-5 text-primary" />
                This Month's Leaders
              </CardTitle>
              <CardDescription>
                Points earned this month
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="allLeaderboards.this_month"
                :current-user-id="user?.id"
                empty-message="No activities this month"
              />
              <div v-if="hasMore.this_month" id="load-more-trigger" class="h-10 flex items-center justify-center">
                <div v-if="isLoading.this_month" class="text-sm text-muted-foreground">Loading more...</div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Overall Leaderboard -->
        <TabsContent value="overall" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Crown class="h-5 w-5 text-primary" />
                All-Time Leaders
              </CardTitle>
              <CardDescription>
                Total points earned
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="allLeaderboards.overall"
                :current-user-id="user?.id"
                empty-message="No activities yet"
              />
              <div v-if="hasMore.overall" id="load-more-trigger" class="h-10 flex items-center justify-center">
                <div v-if="isLoading.overall" class="text-sm text-muted-foreground">Loading more...</div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>