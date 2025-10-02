<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Star, TrendingUp, Calendar, Crown } from 'lucide-vue-next';
import LeaderboardList from '@/components/LeaderboardList.vue';

interface LeaderboardEntry {
  id: number;
  rank: number;
  user: {
    id: number;
    full_name: string;
    username: string;
    avatar?: string;
  };
  points: number;
  activities_count: number;
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
  if (!rank || rank === 0) return 'Unranked';
  return `#${rank}`;
};
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
      <Tabs default-value="today" class="w-full">
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
                Points earned today
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="leaderboards.today"
                :current-user-id="user?.id"
                empty-message="No activities today"
              />
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
                Points earned yesterday
              </CardDescription>
            </CardHeader>
            <CardContent>
              <LeaderboardList 
                :entries="leaderboards.yesterday"
                :current-user-id="user?.id"
                empty-message="No activities yesterday"
              />
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
                :entries="leaderboards.this_month"
                :current-user-id="user?.id"
                empty-message="No activities this month"
              />
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
                :entries="leaderboards.overall"
                :current-user-id="user?.id"
                empty-message="No activities yet"
              />
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>