<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { 
  BookOpen, 
  Plus, 
  Users, 
  Trophy, 
  Search,
  X,
  Lock
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Tag {
  id: number;
  name: string;
}

interface Course {
  id: number;
  title: string;
  description: string;
  students_count: number;
  active_students_count?: number;
  completed_students_count?: number;
  lessons_count: number;
  created_at: string;
  can_join: boolean;
  is_enrolled: boolean;
  is_completed: boolean;
  is_creator: boolean;
  is_featured?: boolean;
  is_public?: boolean;
  tags?: Tag[];
}

interface Props {
  courses?: Course[];
  can_create_class?: boolean;
  user?: {
    id: number;
    enrollment_id?: number;
    current_class?: {
      id: number;
      title: string;
    };
  };
}

const props = withDefaults(defineProps<Props>(), {
  courses: () => [],
  can_create_class: false,
  user: undefined,
});

// Search functionality
const searchQuery = ref('');

// Memoized current class ID
const currentClassId = computed(() => props.user?.current_class?.id);

// Optimized filtering and sorting
const filteredCourses = computed(() => {
  let courses = props.courses;
  
  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    courses = courses.filter(course => {
      const titleMatch = course.title?.toLowerCase().includes(query);
      const descMatch = course.description?.toLowerCase().includes(query);
      const tagMatch = course.tags?.some(tag => tag.name?.toLowerCase().includes(query));
      return titleMatch || descMatch || tagMatch;
    });
  }

  // Sort courses: current enrollment first, then enrolled, then by student count
  return [...courses].sort((a, b) => {
    // Current enrollment first
    const aIsCurrent = a.id === currentClassId.value;
    const bIsCurrent = b.id === currentClassId.value;
    if (aIsCurrent !== bIsCurrent) return aIsCurrent ? -1 : 1;
    
    // Enrolled courses next
    if (a.is_enrolled !== b.is_enrolled) return a.is_enrolled ? -1 : 1;
    
    // Sort by popularity (student count) for non-enrolled courses
    if (!a.is_enrolled && !b.is_enrolled) {
      return b.students_count - a.students_count;
    }
    
    return 0;
  });
});

const clearSearch = () => {
  searchQuery.value = '';
};

// Simplified student count text
const getStudentCountText = (course: Course): string => {
  const { students_count, active_students_count, completed_students_count } = course;
  
  if (active_students_count === undefined || completed_students_count === undefined) {
    return `${students_count} student${students_count !== 1 ? 's' : ''}`;
  }
  
  if (students_count === 0) return '0 students';
  if (active_students_count === 0) return `${students_count} completed`;
  if (completed_students_count === 0) return `${students_count} active`;
  
  return `${students_count} total (${active_students_count} active, ${completed_students_count} completed)`;
};

// Get course status badge
const getCourseStatus = (course: Course) => {
  if (course.id === currentClassId.value) {
    return { label: 'Current', variant: 'secondary' as const };
  }
  if (course.is_completed) {
    return { label: 'Completed', variant: 'default' as const };
  }
  if (course.is_enrolled) {
    return { label: 'In Progress', variant: 'outline' as const };
  }
  return null;
};

// Get button text based on course state
const getButtonText = (course: Course): string => {
  if (!course.is_enrolled) {
    return course.can_join ? 'Join Class' : 'Cannot Join';
  }
  if (course.is_completed) {
    return 'View Completed';
  }
  return course.id === currentClassId.value ? 'Continue Learning' : 'View Class';
};

// Get button variant based on course state
const getButtonVariant = (course: Course) => {
  if (!course.is_enrolled) {
    return course.can_join ? 'default' : 'outline';
  }
  if (course.is_completed) {
    return 'secondary';
  }
  return course.id === currentClassId.value ? 'default' : 'outline';
};

const joinCourse = (courseId: number) => {
  router.post(`/classes/${courseId}/join`, {}, {
    preserveScroll: true,
  });
};

const viewCourse = (courseId: number) => {
  router.visit(`/classes/${courseId}`);
};

const handleCourseAction = (course: Course) => {
  if (course.is_enrolled) {
    viewCourse(course.id);
  } else if (course.can_join) {
    joinCourse(course.id);
  }
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
            variant="default" 
            :disabled="!can_create_class"
            class="w-full md:w-auto cursor-pointer"
            :title="!can_create_class ? 'You must leave your current class before creating a new one' : 'Create a new class'"
          >
            <Plus class="h-4 w-4 mr-2" />
            Create Class
          </Button>
        </Link>
      </div>

      <!-- Tooltip for disabled create button -->
      <div v-if="!can_create_class" class="mb-4 p-3 bg-muted/50 rounded-lg border border-muted">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Lock class="h-4 w-4" />
          <span v-if="user.enrollment_id">
            You're already enrolled in "{{ user.current_class?.title }}". 
            Complete it or leave to join or create a new class.
          </span>
          <span v-else>
            You need to meet certain requirements to create a class.
          </span>
        </div>
      </div>

      <!-- Search and Filter -->
      <div class="flex flex-col md:flex-row gap-4 mb-6">
        <!-- Search Input -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input 
            v-model="searchQuery"
            placeholder="Search classes by title, description, or tags..." 
            class="pl-10 pr-10 h-11"
          />
          <Button
            v-if="searchQuery"
            variant="ghost"
            size="sm"
            class="absolute right-1 top-1/2 transform -translate-y-1/2 h-8 w-8 p-0"
            @click="clearSearch"
          >
            <X class="h-4 w-4" />
          </Button>
        </div>

      </div>

      <!-- Results Count -->
      <div v-if="searchQuery" class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          Showing {{ filteredCourses.length }} of {{ courses.length }} classes
        </p>
        <Button 
          v-if="searchQuery"
          variant="ghost" 
          size="sm"
          @click="clearSearch"
        >
          Clear Search
        </Button>
      </div>

      <!-- Classes Grid -->
      <div v-if="filteredCourses.length === 0" class="text-center py-12">
        <BookOpen class="h-16 w-16 mx-auto mb-4 text-muted-foreground opacity-50" />
        <h3 class="text-lg font-semibold text-foreground mb-2">
          {{ searchQuery ? 'No Matching Classes' : 'No Classes Found' }}
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ searchQuery 
            ? 'Try adjusting your search terms to find classes.' 
            : 'Be the first to create a class or check back later for new classes.' 
          }}
        </p>
        <div class="flex items-center justify-center gap-2">
          <Button 
            v-if="searchQuery"
            variant="outline"
            @click="clearSearch"
          >
            Clear Search
          </Button>
          <Link v-if="can_create_class && !searchQuery" :href="'/classes/create'">
            <Button variant="default">
              <Plus class="h-4 w-4 mr-2" />
              Create First Class
            </Button>
          </Link>
        </div>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card v-for="course in filteredCourses" :key="course.id" 
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
                <Badge 
                  v-if="getCourseStatus(course)" 
                  :variant="getCourseStatus(course)!.variant" 
                  :class="getCourseStatus(course)!.variant === 'secondary' ? 'text-secondary-foreground' : 'bg-primary text-primary-foreground'"
                >
                  {{ getCourseStatus(course)!.label }}
                </Badge>
              </div>
            </div>

            <!-- Tags (moved after description) -->
            <div v-if="course.tags && course.tags.length > 0" class="flex flex-wrap gap-1">
              <Badge 
                v-for="tag in course.tags" 
                :key="tag.id" 
                variant="outline" 
                class="text-xs"
              >
                {{ tag.name }}
              </Badge>
            </div>
  
            <!-- Class Stats -->
            <div class="flex flex-col gap-4 text-sm text-muted-foreground mt-4">
              <div class="flex items-center gap-1">
                <BookOpen class="h-4 w-4" />
                <span>{{ course.lessons_count }} lessons</span>
              </div>
              <div class="flex items-center gap-1">
                <Users class="h-4 w-4" />
                <span>{{ getStudentCountText(course) }}</span>
              </div>
            </div>
          </CardHeader>

          <!-- Actions at the very end of the card -->
          <div class="px-6 pt-4 border-t border-border/50">
            <div class="flex gap-2">
              <Button 
                :variant="getButtonVariant(course)"
                class="flex-1 cursor-pointer"
                :disabled="!course.is_enrolled && !course.can_join"
                @click="handleCourseAction(course)"
              >
                {{ getButtonText(course) }}
              </Button>
            </div>
          </div>
        </Card>
      </div>

      <!-- Current Enrollment Info -->
      <Card v-if="user?.enrollment_id && user?.current_class" class="mt-8 border-primary/20">
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
              <h3 class="font-semibold text-foreground">{{ user.current_class!.title }}</h3>
              <p class="text-sm text-muted-foreground">Continue your learning journey</p>
            </div>
            <Link :href="`/classes/${user.current_class!.id}`">
              <Button variant="default">
                Continue Learning
              </Button>
            </Link>
          </div>
        </CardContent>
      </Card>
    </div>
  </AppLayout>
</template>
