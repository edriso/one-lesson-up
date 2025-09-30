<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import { 
  User, 
  Trophy, 
  Calendar, 
  BookOpen, 
  TrendingUp, 
  Award, 
  Clock, 
  Target,
  Github,
  ExternalLink,
  Mail,
  MapPin,
  Briefcase
} from 'lucide-vue-next';

interface ProfileUser {
  id: number;
  full_name: string;
  username: string;
  bio?: string;
  title?: string;
  linkedin_url?: string;
  website_url?: string;
  profile_picture_url?: string;
  points: number;
  joined_at: string;
  is_public: boolean;
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
    total_activities: number;
    total_lessons_completed: number;
    total_classes_completed: number;
    current_streak: number;
    longest_streak: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  user: undefined,
  activities: () => [],
  completed_classes: () => [],
  calendar_data: () => [],
  stats: () => ({
    total_points: 0,
    total_activities: 0,
    total_lessons_completed: 0,
    total_classes_completed: 0,
    current_streak: 0,
    longest_streak: 0,
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
      return BookOpen;
    case 'course_completed':
      return Trophy;
    case 'course_started':
      return Target;
    default:
      return TrendingUp;
  }
};

const getCalendarIntensity = (activitiesCount: number) => {
  if (activitiesCount === 0) return 'bg-muted/30';
  if (activitiesCount <= 2) return 'bg-primary/40';
  if (activitiesCount <= 5) return 'bg-primary/60';
  return 'bg-primary/80';
};

// Generate calendar grid (simplified version)
const generateCalendarGrid = () => {
  const today = new Date();
  const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
  const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  
  const days = [];
  const startDay = startOfMonth.getDay();
  
  // Add empty cells for days before the first day of the month
  for (let i = 0; i < startDay; i++) {
    days.push(null);
  }
  
  // Add days of the month
  for (let day = 1; day <= endOfMonth.getDate(); day++) {
    const date = new Date(today.getFullYear(), today.getMonth(), day);
    const dateString = date.toISOString().split('T')[0];
    const dayData = props.calendar_data.find(d => d.date === dateString);
    
    days.push({
      date: dateString,
      day: day,
      activities_count: dayData?.activities_count || 0,
      points_earned: dayData?.points_earned || 0,
      lessons_completed: dayData?.lessons_completed || 0
    });
  }
  
  return days;
};

const calendarGrid = generateCalendarGrid();
</script>

<template>
  <Head :title="user ? `${user.full_name} (@${user.username})` : 'Profile'" />
  
  <AppLayout>
    <div v-if="!user" class="text-center py-12">
      <p class="text-muted-foreground">User not found</p>
    </div>
    
    <div v-else class="space-y-6">
      <!-- Profile Header -->
      <Card class="mb-8 border-primary/20">
        <CardContent class="p-8">
          <div class="flex flex-col md:flex-row gap-6">
            <!-- Profile Picture -->
            <div class="flex-shrink-0">
              <div class="w-24 h-24 rounded-full bg-primary/20 flex items-center justify-center">
                <img v-if="user.profile_picture_url" 
                     :src="user.profile_picture_url" 
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
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ stats.total_activities }}</div>
                    <div class="text-sm text-muted-foreground">Activities</div>
                  </div>
                  <div class="text-center">
                    <div class="text-2xl font-bold text-primary">{{ stats.current_streak }}</div>
                    <div class="text-sm text-muted-foreground">Day Streak</div>
                  </div>
                </div>
              </div>
              
              <!-- Social Links -->
              <div class="flex flex-wrap gap-4 mt-4">
                <Button v-if="user.linkedin_url" variant="outline" size="sm" as-child>
                  <a :href="user.linkedin_url" target="_blank" class="flex items-center gap-2">
                    <Github class="h-4 w-4" />
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

      <!-- Tabs -->
      <Tabs default-value="overview" class="w-full">
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
                    <Trophy class="h-5 w-5 text-primary" />
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
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Current Streak</span>
                    <span class="font-semibold">{{ stats.current_streak }} days</span>
                  </div>
                  <div class="flex justify-between items-center">
                    <span class="text-sm text-muted-foreground">Longest Streak</span>
                    <span class="font-semibold">{{ stats.longest_streak }} days</span>
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
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <Calendar class="h-5 w-5 text-primary" />
                Learning Calendar
              </CardTitle>
              <CardDescription>
                Your learning activity over time
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div class="space-y-4">
                <!-- Calendar Grid -->
                <div class="grid grid-cols-7 gap-1">
                  <!-- Day headers -->
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Sun</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Mon</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Tue</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Wed</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Thu</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Fri</div>
                  <div class="text-center text-sm font-medium text-muted-foreground py-2">Sat</div>
                  
                  <!-- Calendar days -->
                  <div v-for="(day, index) in calendarGrid" :key="index" 
                       class="aspect-square flex items-center justify-center text-sm rounded"
                       :class="day ? getCalendarIntensity(day.activities_count) : 'bg-transparent'">
                    <span v-if="day" class="text-xs font-medium">{{ day.day }}</span>
                  </div>
                </div>
                
                <!-- Legend -->
                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                  <span>Less</span>
                  <div class="flex gap-1">
                    <div class="w-3 h-3 rounded bg-muted/30"></div>
                    <div class="w-3 h-3 rounded bg-primary/40"></div>
                    <div class="w-3 h-3 rounded bg-primary/60"></div>
                    <div class="w-3 h-3 rounded bg-primary/80"></div>
                  </div>
                  <span>More</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <!-- Classes Tab -->
        <TabsContent value="classes" class="mt-6">
          <Card>
            <CardHeader>
              <CardTitle class="flex items-center gap-2">
                <BookOpen class="h-5 w-5 text-primary" />
                Completed Classes
              </CardTitle>
              <CardDescription>
                Classes you've successfully completed
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="completed_classes.length === 0" class="text-center py-8 text-muted-foreground">
                <BookOpen class="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>No completed classes yet</p>
                <p class="text-sm">Start learning to see your progress here!</p>
              </div>
              <div v-else class="space-y-4">
                <div v-for="classItem in completed_classes" :key="classItem.id" 
                     class="flex items-center justify-between p-4 rounded-lg border bg-background">
                  <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-primary/20 flex items-center justify-center">
                      <BookOpen class="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h4 class="font-semibold text-foreground">{{ classItem.title }}</h4>
                      <p class="text-sm text-muted-foreground">
                        Completed {{ formatDate(classItem.completed_at) }} • {{ classItem.lessons_count }} lessons
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
