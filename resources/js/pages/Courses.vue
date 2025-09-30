<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuCheckboxItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
  BookOpen, 
  Plus, 
  Users, 
  Clock, 
  Trophy, 
  Search,
  Filter,
  Eye,
  X,
  Lock
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Course {
  id: number;
  title: string;
  description: string;
  students_count: number;
  lessons_count: number;
  created_at: string;
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

// Search and Filter
const searchQuery = ref('');
const filterOptions = ref({
  showMyClasses: false,
  showEnrolled: false,
  showAvailable: false,
  showPublic: true,
});

const filteredCourses = computed(() => {
  let filtered = props.courses;

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(course => 
      course.title.toLowerCase().includes(query) ||
      course.description.toLowerCase().includes(query)
    );
  }

  // Apply filters
  if (filterOptions.value.showMyClasses) {
    filtered = filtered.filter(course => course.is_creator);
  }

  if (filterOptions.value.showEnrolled) {
    filtered = filtered.filter(course => course.is_enrolled);
  }

  if (filterOptions.value.showAvailable) {
    filtered = filtered.filter(course => course.can_join && !course.is_enrolled);
  }

  if (!filterOptions.value.showPublic) {
    filtered = filtered.filter(course => !course.is_public);
  }

  return filtered;
});

const hasActiveFilters = computed(() => {
  return filterOptions.value.showMyClasses || 
         filterOptions.value.showEnrolled || 
         filterOptions.value.showAvailable ||
         !filterOptions.value.showPublic;
});

const clearFilters = () => {
  filterOptions.value = {
    showMyClasses: false,
    showEnrolled: false,
    showAvailable: false,
    showPublic: true,
  };
};

const clearSearch = () => {
  searchQuery.value = '';
};

const joinCourse = (courseId: number) => {
  router.post(`/classes/${courseId}/join`, {}, {
    preserveScroll: true,
  });
};

const viewCourse = (courseId: number) => {
  router.visit(`/classes/${courseId}`);
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
        <!-- Search Input -->
        <div class="relative flex-1">
          <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input 
            v-model="searchQuery"
            placeholder="Search classes by title or description..." 
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

        <!-- Filter Dropdown -->
        <DropdownMenu>
          <DropdownMenuTrigger as-child>
            <Button variant="outline" class="w-full md:w-auto h-11">
              <Filter class="h-4 w-4 mr-2" />
              Filter
              <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2 text-xs px-1.5">
                {{ Object.values(filterOptions).filter(v => v === true).length }}
              </Badge>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>Filter Classes</DropdownMenuLabel>
            <DropdownMenuSeparator />
            
            <DropdownMenuCheckboxItem
              v-model:checked="filterOptions.showMyClasses"
            >
              My Classes
            </DropdownMenuCheckboxItem>
            
            <DropdownMenuCheckboxItem
              v-model:checked="filterOptions.showEnrolled"
            >
              Enrolled Classes
            </DropdownMenuCheckboxItem>
            
            <DropdownMenuCheckboxItem
              v-model:checked="filterOptions.showAvailable"
            >
              Available to Join
            </DropdownMenuCheckboxItem>
            
            <DropdownMenuSeparator />
            
            <DropdownMenuCheckboxItem
              v-model:checked="filterOptions.showPublic"
            >
              Public Classes
            </DropdownMenuCheckboxItem>
            
            <template v-if="hasActiveFilters">
              <DropdownMenuSeparator />
              <div class="px-2 py-1.5">
                <Button 
                  variant="ghost" 
                  size="sm" 
                  class="w-full justify-center text-xs"
                  @click="clearFilters"
                >
                  Clear Filters
                </Button>
              </div>
            </template>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <!-- Results Count -->
      <div v-if="searchQuery || hasActiveFilters" class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted-foreground">
          Showing {{ filteredCourses.length }} of {{ courses.length }} classes
        </p>
        <Button 
          v-if="searchQuery || hasActiveFilters"
          variant="ghost" 
          size="sm"
          @click="() => { clearSearch(); clearFilters(); }"
        >
          Clear All
        </Button>
      </div>

      <!-- Classes Grid -->
      <div v-if="filteredCourses.length === 0" class="text-center py-12">
        <BookOpen class="h-16 w-16 mx-auto mb-4 text-muted-foreground opacity-50" />
        <h3 class="text-lg font-semibold text-foreground mb-2">
          {{ searchQuery || hasActiveFilters ? 'No Matching Classes' : 'No Classes Found' }}
        </h3>
        <p class="text-muted-foreground mb-4">
          {{ searchQuery || hasActiveFilters 
            ? 'Try adjusting your search or filters to find classes.' 
            : 'Be the first to create a class or check back later for new classes.' 
          }}
        </p>
        <div class="flex items-center justify-center gap-2">
          <Button 
            v-if="searchQuery || hasActiveFilters"
            variant="outline"
            @click="() => { clearSearch(); clearFilters(); }"
          >
            Clear Search & Filters
          </Button>
          <Link v-if="canCreateClass && !searchQuery && !hasActiveFilters" :href="'/classes/create'">
            <Button variant="primary">
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
                <Badge v-if="course.is_enrolled" variant="secondary" class="text-secondary-foreground">
                  Enrolled
                </Badge>
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

            <!-- Actions -->
            <div class="flex gap-2">
              <Button 
                v-if="!course.is_enrolled && !course.is_creator"
                :variant="course.can_join ? 'default' : 'outline'"
                :disabled="!course.can_join"
                class="flex-1 bg-primary text-primary-foreground hover:bg-primary/90"
                @click="joinCourse(course.id)"
              >
                {{ course.can_join ? 'Join Class' : 'Cannot Join' }}
              </Button>
              <Button 
                v-else
                variant="outline"
                class="flex-1"
                @click="viewCourse(course.id)"
              >
                <Eye class="h-4 w-4 mr-2" />
                {{ course.is_enrolled ? 'View & Continue' : 'View Class' }}
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
