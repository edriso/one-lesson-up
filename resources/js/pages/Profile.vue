<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  TrendingUp, 
  Clock, 
  Target,
  ExternalLink,
  Briefcase,
  GraduationCap,
  BadgeCheck,
  Linkedin,
} from 'lucide-vue-next';
import LearningCalendar from '@/components/LearningCalendar.vue';

interface ProfileUser {
  id: number;
  full_name: string;
  username: string;
  bio?: string;
  title?: string;
  linkedin_url?: string;
  website_url?: string;
  avatar?: string;
  points: number;
  joined_at: string;
  is_public: boolean;
  week_starts_on?: number;
}

interface Activity {
  id: number;
  type: string;
  description: string;
  points_earned: number;
  created_at: string;
}

interface CompletedClass {
  id: number;
  title: string;
  completed_at: string;
  points_earned: number;
  lessons_count: number;
}

interface CalendarDay {
  date: string;
  activities_count: number;
  points_earned: number;
  lessons_completed: number;
}

interface Props {
  user?: ProfileUser;
  activities?: Activity[];
  completed_classes?: CompletedClass[];
  calendar_data?: CalendarDay[];
  stats?: {
    total_points: number;
    total_lessons_completed: number;
    total_classes_completed: number;
  };
}

withDefaults(defineProps<Props>(), {
  user: undefined,
  activities: () => [],
  completed_classes: () => [],
  calendar_data: () => [],
  stats: () => ({
    total_points: 0,
    total_lessons_completed: 0,
    total_classes_completed: 0,
  }),
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const formatShortDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  });
};

const getActivityIcon = (type: string) => {
  switch (type) {
    case 'lesson_completed':
      return GraduationCap;
    case 'course_completed':
      return BadgeCheck;
    case 'course_started':
      return Target;
    default:
      return TrendingUp;
  }
};

</script>

<template>
  <Head :title="user ? `${user.full_name} (@${user.username})` : 'Profile'" />
  
  <AppLayout>
    <div v-if="!user" class="text-center py-12">
      <p class="text-muted-foreground">User not found</p>
    </div>
    
    <div v-else class="space-y-6">
      <!-- Private Profile Message -->
      <Card v-if="!user.is_public" class="mb-8 border-orange-200 bg-orange-50 dark:border-orange-800 dark:bg-orange-950">
        <CardContent class="p-6">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
              <span class="text-orange-600 dark:text-orange-400 text-lg">🔒</span>
            </div>
            <div>
              <h3 class="font-semibold text-orange-800 dark:text-orange-200">This account is private</h3>
              <p class="text-sm text-orange-700 dark:text-orange-300">
                This user has chosen to keep their profile private. Their activities and progress are not visible to other users.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Profile Header -->
      <Card class="mb-8 border-primary/20">
        <CardContent class="p-8">
          <div class="flex flex-col md:flex-row gap-6">
            <!-- Profile Picture -->
            <div class="flex-shrink-0">
              <div class="w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center">
                <img v-if="user.avatar" 
                     :src="user.avatar" 
                     :alt="user.full_name"
                     class="w-24 h-24 rounded-full object-cover" />
                <span v-else class="text-2xl font-bold text-primary-foreground">
                  {{ user.full_name.charAt(0).toUpperCase() }}
                </span>
              </div>
            </div>
            
            <!-- Profile Info -->
            <div class="flex-1">
              <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                <div>
                  <h1 class="text-3xl font-bold text-foreground">{{ user.full_name }}</h1>
                  <p class="text-muted-foreground">@{{ user.username }}</p>
                  <p v-if="user.bio" class="mt-2 text-foreground">{{ user.bio }}</p>
                  <div v-if="user.title" class="flex items-center gap-2 mt-2">
                    <Briefcase class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm text-muted-foreground">{{ user.title }}</span>
                  </div>
                </div>
                
                <!-- Stats -->
                <div class="flex flex-wrap gap-4">
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ user.points }}</div>
                    <div class="text-sm text-muted-foreground">Points</div>
                  </div>
                </div>
              </div>
              
              <!-- Social Links -->
              <div class="flex flex-wrap gap-4 mt-4">
                <Button v-if="user.linkedin_url" variant="outline" size="sm" as-child>
                  <a :href="user.linkedin_url" target="_blank" class="flex items-center gap-2">
                    <Linkedin class="h-4 w-4" />
                    LinkedIn
                    <ExternalLink class="h-3 w-3" />
                  </a>
                </Button>
                <Button v-if="user.website_url" variant="outline" size="sm" as-child>
                  <a :href="user.website_url" target="_blank" class="flex items-center gap-2">
                    <ExternalLink class="h-4 w-4" />
                    Website
                  </a>
                </Button>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Tabs (only show for public profiles) -->
      <Tabs v-if="user.is_public" default-value="overview" class="w-full">
        <TabsList class="grid w-full grid-cols-4">
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="calendar">Calendar</TabsTrigger>
          <TabsTrigger value="classes">Classes</TabsTrigger>
          <TabsTrigger value="activities">Activities</TabsTrigger>
        </TabsList>

        <!-- Overview Tab -->
        <TabsContent value="overview" class="mt-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Stats Cards -->
            <div class="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle class="flex items-center gap-2">
                    Learning Stats
                  </CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Total Points</span>
                    <span class="font-semibold">{{ stats.total_points }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Lessons Completed</span>
                    <span class="font-semibold">{{ stats.total_lessons_completed }}</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Classes Completed</span>
                    <span class="font-semibold">{{ stats.total_classes_completed }}</span>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Recent Activities -->
            <Card>
              <CardHeader>
                <CardTitle class="flex items-center gap-2">
                  <TrendingUp class="h-5 w-5 text-primary" />
                  Recent Activities
                </CardTitle>
                <CardDescription>
                  Latest learning progress
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div v-if="activities.length === 0" class="text-center py-4 text-muted-foreground">
                  <p class="text-sm">No recent activities</p>
                </div>
                <div v-else class="space-y-3">
                  <div v-for="activity in activities.slice(0, 5)" :key="activity.id" 
                       class="flex items-start gap-3 p-3 rounded-lg bg-muted/30">
                    <component :is="getActivityIcon(activity.type)" class="h-4 w-4 mt-0.5 text-primary flex-shrink-0" />
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-foreground">{{ activity.description }}</p>
                      <div class="flex items-center gap-2 mt-1">
                        <span class="text-xs text-muted-foreground">{{ formatShortDate(activity.created_at) }}</span>
                        <span v-if="activity.points_earned > 0" class="text-xs font-medium text-secondary">
                          +{{ activity.points_earned }} pts
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <!-- Calendar Tab -->
        <TabsContent value="calendar" class="mt-6">
          <LearningCalendar 
            :calendar_data="calendar_data" 
            :week_starts_on="user?.week_starts_on || 0"
          />
        </TabsContent>

        <!-- Classes Tab -->
        <TabsContent value="classes" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <GraduationCap class="h-5 w-5 text-primary" />
                Completed Classes
              </CardTitle>
              <CardDescription>
                Classes you've successfully completed
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="completed_classes.length === 0" class="text-center py-8 text-muted-foreground">
                <GraduationCap class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No completed classes yet</p>
                <p class="text-sm">Start learning to see your progress here!</p>
              </div>
              <div v-else class="space-y-4">
                <div v-for="classItem in completed_classes" :key="classItem.id" 
                     class="flex items-center justify-between p-4 rounded-lg border bg-background">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center">
                      <GraduationCap class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h4 class="font-semibold text-foreground">{{ classItem.title }}</h4>
                      <p class="text-sm text-muted-foreground">
                        Completed {{ formatDate(classItem.completed_at) }} • {{ classItem.lessons_count }} {{ classItem.lessons_count === 1 ? 'lesson' : 'lessons' }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center gap-4">
                    <div class="text-right">
                      <p class="font-bold text-foreground">{{ classItem.points_earned }} pts</p>
                      <Badge variant="secondary" class="text-secondary-foreground">
                        Completed
                      </Badge>
                    </div>
                    <Button variant="outline" size="sm">
                      View Class
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Activities Tab -->
        <TabsContent value="activities" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Clock class="h-5 w-5 text-primary" />
                All Activities
              </CardTitle>
              <CardDescription>
                Complete history of your learning activities
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="activities.length === 0" class="text-center py-8 text-muted-foreground">
                <Clock class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No activities yet</p>
                <p class="text-sm">Start learning to see your activities here!</p>
              </div>
              <div v-else class="space-y-3">
                <div v-for="activity in activities" :key="activity.id" 
                     class="flex items-start gap-3 p-4 rounded-lg border bg-background">
                  <component :is="getActivityIcon(activity.type)" class="h-5 w-5 mt-0.5 text-primary flex-shrink-0" />
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-foreground">{{ activity.description }}</p>
                    <div class="flex items-center gap-4 mt-2">
                      <span class="text-sm text-muted-foreground">{{ formatDate(activity.created_at) }}</span>
                      <span v-if="activity.points_earned > 0" class="text-sm font-medium text-secondary">
                        +{{ activity.points_earned }} points
                      </span>
                    </div>
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
