<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
  BookOpen, 
  Plus, 
  Users, 
  Clock, 
  Trophy, 
  Search,
  Filter,
  MoreHorizontal,
  Trash2,
  Edit,
  Eye,
  Lock,
  Unlock
} from 'lucide-vue-next';

interface Course {
  id: number;
  title: string;
  description: string;
  created_by: {
    id: number;
    full_name: string;
    username: string;
  };
  students_count: number;
  lessons_count: number;
  created_at: string;
  is_public: boolean;
  can_join: boolean;
  is_enrolled: boolean;
  is_creator: boolean;
}

interface Props {
  courses?: Course[];
  can_create_class?: boolean;
  user?: {
    id: number;
    current_enrollment?: {
      id: number;
      class: {
        id: number;
        title: string;
      };
    };
  };
}

const props = withDefaults(defineProps<Props>(), {
  courses: () => [],
  can_create_class: false,
  user: undefined,
});

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric'
  });
};

const canCreateClass = props.can_create_class && !props.user?.current_enrollment;

const getJoinButtonText = (course: Course) => {
  if (course.is_enrolled) return 'Enrolled';
  if (course.is_creator) return 'Created by you';
  if (!course.can_join) return 'Full';
  return 'Join Class';
};

const getJoinButtonVariant = (course: Course) => {
  if (course.is_enrolled || course.is_creator) return 'secondary';
  if (!course.can_join) return 'outline';
  return 'primary';
};

const isJoinButtonDisabled = (course: Course) => {
  return course.is_enrolled || course.is_creator || !course.can_join;
};
</script>

<template>
  <Head title="Classes" />
  
  <AppLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
          <h1 class="text-4xl font-bold text-foreground mb-2">
            Classes
          </h1>
          <p class="text-muted-foreground">
            Discover and join learning communities
          </p>
        </div>
        
        <!-- Create Class Button -->
        <Link :href="'/classes/create'">
          <Button 
            variant="primary" 
            :disabled="!canCreateClass"
            class="w-full md:w-auto"
            :title="!canCreateClass ? 'You must leave your current class before creating a new one' : 'Create a new class'"
          >
            <Plus class="h-4 w-4 mr-2" />
            Create Class
          </Button>
        </Link>
      </div>

      <!-- Tooltip for disabled create button -->
      <div v-if="!canCreateClass" class="mb-4 p-3 bg-muted/50 rounded-lg border border-muted">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Lock class="h-4 w-4" />
          <span v-if="user.current_enrollment">
            You're already enrolled in "{{ user.current_enrollment.class.title }}". 
            Complete it or leave to create a new class.
          </span>
          <span v-else>
            You need to meet certain requirements to create a class.
          </span>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input 
            placeholder="Search classes..." 
            class="pl-10"
          />
        </div>
        <Button variant="outline" class="w-full md:w-auto">
          <Filter class="h-4 w-4 mr-2" />
          Filter
        </Button>
      </div>

      <!-- Classes Grid -->
      <div v-if="courses.length === 0" class="text-center py-12">
        <BookOpen class="h-16 w-16 mx-auto mb-4 text-muted-foreground opacity-50" />
        <h3 class="text-lg font-semibold text-foreground mb-2">No Classes Found</h3>
        <p class="text-muted-foreground mb-4">
          Be the first to create a class or check back later for new classes.
        </p>
        <Button v-if="canCreateClass" variant="primary">
          <Plus class="h-4 w-4 mr-2" />
          Create First Class
        </Button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card v-for="course in courses" :key="course.id" 
              class="hover:shadow-lg transition-shadow duration-200">
          <CardHeader>
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <CardTitle class="text-lg line-clamp-2">{{ course.title }}</CardTitle>
                <CardDescription class="mt-1 line-clamp-2">
                  {{ course.description }}
                </CardDescription>
              </div>
              <div class="flex items-center gap-2 ml-4">
                <Badge v-if="course.is_public" variant="secondary" class="text-secondary-foreground">
                  <Unlock class="h-3 w-3 mr-1" />
                  Public
                </Badge>
                <Badge v-else variant="outline">
                  <Lock class="h-3 w-3 mr-1" />
                  Private
                </Badge>
                <Button variant="ghost" size="sm" class="h-8 w-8 p-0">
                  <MoreHorizontal class="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
          
          <CardContent class="space-y-4">
            <!-- Class Stats -->
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <div class="flex items-center gap-1">
                <Users class="h-4 w-4" />
                <span>{{ course.students_count }} students</span>
              </div>
              <div class="flex items-center gap-1">
                <BookOpen class="h-4 w-4" />
                <span>{{ course.lessons_count }} lessons</span>
              </div>
              <div class="flex items-center gap-1">
                <Clock class="h-4 w-4" />
                <span>{{ formatDate(course.created_at) }}</span>
              </div>
            </div>

            <!-- Creator Info -->
            <div class="flex items-center gap-2 text-sm">
              <span class="text-muted-foreground">Created by</span>
              <span class="font-medium text-foreground">{{ course.created_by.full_name }}</span>
              <span class="text-muted-foreground">@{{ course.created_by.username }}</span>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
              <Button 
                :variant="getJoinButtonVariant(course)"
                :disabled="isJoinButtonDisabled(course)"
                class="flex-1"
              >
                {{ getJoinButtonText(course) }}
              </Button>
              <Button variant="outline" size="sm">
                <Eye class="h-4 w-4" />
              </Button>
              <Button 
                v-if="course.is_creator" 
                variant="outline" 
                size="sm"
                class="text-destructive hover:text-destructive"
              >
                <Trash2 class="h-4 w-4" />
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Current Enrollment Info -->
      <Card v-if="user?.current_enrollment" class="mt-8 border-primary/20">
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Trophy class="h-5 w-5 text-primary" />
            Your Current Class
          </CardTitle>
          <CardDescription>
            You're currently enrolled in this class
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-semibold text-foreground">{{ user.current_enrollment!.class.title }}</h3>
              <p class="text-sm text-muted-foreground">Continue your learning journey</p>
            </div>
            <Button variant="primary">
              Continue Learning
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
