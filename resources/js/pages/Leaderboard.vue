<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Trophy, Medal, Award, Crown, TrendingUp, Calendar } from 'lucide-vue-next';

interface LeaderboardEntry {
  id: number;
  rank: number;
  user: {
    id: number;
    full_name: string;
    username: string;
    profile_picture_url?: string;
  };
  points: number;
  activities_count: number;
}

interface Props {
  leaderboards?: {
    today: LeaderboardEntry[];
    yesterday: LeaderboardEntry[];
    this_month: LeaderboardEntry[];
    last_month: LeaderboardEntry[];
    year: LeaderboardEntry[];
    overall: LeaderboardEntry[];
  };
  current_user_rank?: {
    today: number;
    yesterday: number;
    this_month: number;
    last_month: number;
    year: number;
    overall: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  leaderboards: () => ({
    today: [],
    yesterday: [],
    this_month: [],
    last_month: [],
    year: [],
    overall: [],
  }),
  current_user_rank: () => ({
    today: 0,
    yesterday: 0,
    this_month: 0,
    last_month: 0,
    year: 0,
    overall: 0,
  }),
});

const getRankIcon = (rank: number) => {
  switch (rank) {
    case 1:
      return Crown;
    case 2:
      return Trophy;
    case 3:
      return Medal;
    default:
      return Award;
  }
};

const getRankColor = (rank: number) => {
  switch (rank) {
    case 1:
      return 'text-yellow-500';
    case 2:
      return 'text-gray-400';
    case 3:
      return 'text-amber-600';
    default:
      return 'text-muted-foreground';
  }
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  });
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

      <!-- Time Period Tabs -->
      <Tabs default-value="today" class="w-full">
        <TabsList class="grid w-full grid-cols-6">
          <TabsTrigger value="today">Today</TabsTrigger>
          <TabsTrigger value="yesterday">Yesterday</TabsTrigger>
          <TabsTrigger value="this_month">This Month</TabsTrigger>
          <TabsTrigger value="last_month">Last Month</TabsTrigger>
          <TabsTrigger value="year">Year</TabsTrigger>
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
              <div v-if="leaderboards.today.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities today</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.today" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
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
                Points earned yesterday
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="leaderboards.yesterday.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities yesterday</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.yesterday" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
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
              <div v-if="leaderboards.this_month.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities this month</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.this_month" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Last Month Leaderboard -->
        <TabsContent value="last_month" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <TrendingUp class="h-5 w-5 text-primary" />
                Last Month's Leaders
              </CardTitle>
              <CardDescription>
                Points earned last month
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="leaderboards.last_month.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities last month</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.last_month" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Year Leaderboard -->
        <TabsContent value="year" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Award class="h-5 w-5 text-primary" />
                Year's Top Performers
              </CardTitle>
              <CardDescription>
                Points earned this year
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="leaderboards.year.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities this year</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.year" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
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
              <div v-if="leaderboards.overall.length === 0" class="text-center py-8 text-muted-foreground">
                <Trophy class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities yet</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="entry in leaderboards.overall" :key="entry.id" 
                     class="flex items-center justify-between p-4 rounded-lg border"
                     :class="entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border'">
                  <div class="flex items-center gap-4">
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
                      <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
                    </div>
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
                        <span class="text-sm font-semibold text-primary-foreground">
                          {{ entry.user.full_name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                      <div>
                        <h4 class="font-semibold text-foreground">{{ entry.user.full_name }}</h4>
                        <p class="text-sm text-muted-foreground">@{{ entry.user.username }}</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ entry.points }} pts</p>
                      <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
                    </div>
                    <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
                      #{{ entry.rank }}
                    </Badge>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  </AppLayout>
</template>
