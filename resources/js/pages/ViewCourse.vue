<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import ModalAlert from '@/components/ModalAlert.vue';
import LessonCompletionModal from '@/components/LessonCompletionModal.vue';
import CourseReflectionModal from '@/components/CourseReflectionModal.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { 
  BookOpen, 
  ArrowLeft, 
  ExternalLink,
  LogOut,
  CheckCircle,
  Circle,
  Trophy,
  Link as LinkIcon,
  Lock
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Lesson {
  id: number;
  name: string;
  description: string;
  order: number;
  is_completed: boolean;
}

interface Module {
  id: number;
  name: string;
  description: string;
  order: number;
  lessons: Lesson[];
}

interface Props {
  course: {
    id: number;
    title: string;
    description: string;
    link?: string;
    course_reflection?: string;
    modules: Module[];
    total_lessons: number;
    total_modules: number;
    created_at: string;
  };
  is_enrolled: boolean;
  is_completed: boolean;
  can_join: boolean;
  completed_lessons_count: number;
  completion_date?: string;
}

const props = defineProps<Props>();
const { formatLongDate } = useDateFormatter();

// Modal state
const isModalOpen = ref(false);
const selectedLesson = ref<Lesson | null>(null);

// Leave course modal state
const isLeaveModalOpen = ref(false);

// Lesson summary modal state
const isSummaryModalOpen = ref(false);
const selectedLessonForSummary = ref<Lesson | null>(null);
const lessonSummary = ref('');
const lessonLink = ref('');
const isEditingSummary = ref(false);
const summaryError = ref('');
const isSaving = ref(false);

// Course reflection modal state
const isReflectionModalOpen = ref(false);

const openCompleteModal = (lesson: Lesson) => {
  selectedLesson.value = lesson;
  isModalOpen.value = true;
};

const onLessonCompleted = () => {
  // This will be called when the lesson is successfully completed
  // The page will already be refreshed by Inertia
};

const openLeaveModal = () => {
  isLeaveModalOpen.value = true;
};

const closeLeaveModal = () => {
  isLeaveModalOpen.value = false;
};

const confirmLeaveCourse = () => {
  router.post(`/classes/${props.course.id}/leave`, {}, {
    preserveScroll: false,
    onSuccess: () => {
      closeLeaveModal();
    },
  });
};

const openSummaryModal = async (lesson: Lesson) => {
  selectedLessonForSummary.value = lesson;
  isSummaryModalOpen.value = true;
  isEditingSummary.value = false;
  
  try {
    // Fetch lesson summary from backend
    const response = await fetch(`/lessons/${lesson.id}/summary`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    
    if (response.ok) {
      const data = await response.json();
      lessonSummary.value = data.summary || '';
      lessonLink.value = data.link || '';
      // If there's no summary, automatically start editing
      if (!data.summary) {
        isEditingSummary.value = true;
      }
    } else if (response.status === 404) {
      // Lesson not completed or no summary exists, start in edit mode
      lessonSummary.value = '';
      lessonLink.value = '';
      isEditingSummary.value = true;
    } else {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
  } catch (error) {
    console.error('Failed to fetch lesson summary:', error);
    lessonSummary.value = '';
    lessonLink.value = '';
    isEditingSummary.value = true;
  }
};

const closeSummaryModal = () => {
  isSummaryModalOpen.value = false;
  selectedLessonForSummary.value = null;
  lessonSummary.value = '';
  lessonLink.value = '';
  isEditingSummary.value = false;
  summaryError.value = '';
  isSaving.value = false;
};

const startEditingSummary = () => {
  isEditingSummary.value = true;
  summaryError.value = '';
};

const saveSummary = async () => {
  if (!selectedLessonForSummary.value || !lessonSummary.value.trim()) {
    summaryError.value = 'Please provide a summary before saving.';
    return;
  }
  
  isSaving.value = true;
  summaryError.value = '';
  
  try {
    const response = await fetch(`/lessons/${selectedLessonForSummary.value.id}/summary`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        summary: lessonSummary.value.trim(),
        link: lessonLink.value.trim() || null,
      }),
    });
    
    const data = await response.json();
    
    if (response.ok && data.success) {
      isEditingSummary.value = false;
      // Update the displayed data
      if (data.data) {
        lessonSummary.value = data.data.summary || lessonSummary.value;
        lessonLink.value = data.data.link || lessonLink.value;
      }
    } else {
      // Handle different types of errors
      if (data.errors) {
        // Validation errors
        const errorMessages = Object.values(data.errors).flat();
        summaryError.value = errorMessages.join(', ');
      } else if (data.message) {
        summaryError.value = data.message;
      } else if (data.error) {
        summaryError.value = data.error;
      } else {
        summaryError.value = `Failed to save summary. Server responded with status ${response.status}.`;
      }
    }
  } catch (error) {
    console.error('Failed to save lesson summary:', error);
    summaryError.value = 'Failed to save summary. Please check your connection and try again.';
  } finally {
    isSaving.value = false;
  }
};

const joinCourse = () => {
  router.post(`/classes/${props.course.id}/join`, {}, {
    preserveScroll: false,
  });
};

const openReflectionModal = () => {
  isReflectionModalOpen.value = true;
};

const closeReflectionModal = () => {
  isReflectionModalOpen.value = false;
};

const onCourseCompleted = () => {
  // This will be called when the course is successfully completed
  // The page will already be refreshed by Inertia
};

// Memoized computation for current module to avoid recalculation
const currentModule = computed(() => {
  if (!props.course?.modules?.length) return null;
  
  return props.course.modules.find(module => 
    module.lessons?.some(lesson => !lesson.is_completed)
  );
});

// Check if current module is the last one
const isLastModule = computed(() => {
  if (!currentModule.value || !props.course?.modules?.length) return false;
  return props.course.modules.indexOf(currentModule.value) === props.course.modules.length - 1;
});

// Calculate completion percentage based on current module context
const completionPercentage = computed(() => {
  if (!props.course?.total_lessons) return 0;
  
  // All modules completed
  if (!currentModule.value) return 100;
  
  // Last module - show overall course progress
  if (isLastModule.value) {
    return Math.round((props.completed_lessons_count / props.course.total_lessons) * 100);
  }
  
  // Not last module - show current module progress
  const completedInModule = currentModule.value.lessons.filter(l => l.is_completed).length;
  const totalInModule = currentModule.value.lessons.length || 1;
  return Math.round((completedInModule / totalInModule) * 100);
});

// Find the next available lesson (first incomplete lesson in sequence)
const nextAvailableLesson = computed(() => {
  if (!props.course?.modules?.length) return null;
  
  for (const module of props.course.modules) {
    const nextLesson = module.lessons?.find(lesson => !lesson.is_completed);
    if (nextLesson) return nextLesson;
  }
  
  return null; // All lessons completed
});

// Check if a lesson can be completed (only the next available lesson)
const canCompleteLesson = (lesson: Lesson): boolean => {
  return nextAvailableLesson.value?.id === lesson.id;
};

// Get progress text based on current module
const progressText = computed(() => {
  if (!props.course?.total_lessons) return 'No lessons available';
  
  if (!currentModule.value) {
    return `${props.completed_lessons_count} of ${props.course.total_lessons} lessons completed`;
  }
  
  if (isLastModule.value) {
    return `${props.completed_lessons_count} of ${props.course.total_lessons} lessons completed`;
  }
  
  const completedInModule = currentModule.value.lessons.filter(l => l.is_completed).length;
  return `${completedInModule} of ${currentModule.value.lessons.length} lessons in current module`;
});

</script>

<template>
  <Head :title="course.title" />
  
  <AppLayout>
    <div class="max-w-6xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-start justify-between gap-4">
        <div class="flex-1">
          <div class="flex items-center gap-2 mb-2">
            <Button variant="ghost" size="sm" @click="router.visit('/classes')" class="cursor-pointer">
              <ArrowLeft class="h-4 w-4 mr-2" />
              Back to Classes
            </Button>
          </div>
          
          <h1 class="text-4xl font-bold text-foreground mb-2">
            {{ course.title }}
          </h1>
          
          <p class="text-lg text-muted-foreground">
            {{ course.description }}
          </p>
          
          <div class="flex items-center gap-4 mt-4">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <BookOpen class="h-4 w-4" />
              <span>{{ course.total_modules }} modules</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <Trophy class="h-4 w-4" />
              <span>{{ course.total_lessons }} lessons</span>
            </div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span>Created {{ formatLongDate(course.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-2">
          <Button 
            v-if="can_join"
            variant="default"
            @click="joinCourse"
            class="bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer"
          >
            Join Class
          </Button>
          <Button 
            v-if="course.link"
            variant="outline"
            as="a"
            :href="course.link"
            target="_blank"
            rel="noopener noreferrer"
            class="cursor-pointer"
          >
            <ExternalLink class="h-4 w-4 mr-2" />
            View Class
          </Button>
        </div>
      </div>

      <!-- Completed Course Status -->
      <Card v-if="is_completed" class="bg-gradient-to-l from-primary/20 to-secondary/20 border border-primary/30 rounded-lg">
        <CardContent class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-primary/20 rounded-full flex items-center justify-center">
                    <Trophy class="h-6 w-6 text-primary" />
                  </div>
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold text-foreground">Congratulations! 🎉</h3>
                  <p class="text-sm text-muted-foreground">
                    You completed this class on {{ formatLongDate(completion_date!) }}. 
                  </p>
                </div>
              </div>
              <Badge variant="secondary" class="text-secondary-foreground text-lg px-3 py-1">
                100%
              </Badge>
            </div>
            
            <!-- Course Reflection Display -->
            <div v-if="course.course_reflection" class="mt-4 p-4 bg-background/50 rounded-lg border border-primary/20">
              <div class="flex items-start gap-3">
                <BookOpen class="h-5 w-5 text-primary mt-0.5 flex-shrink-0" />
                <div class="flex-1">
                  <h4 class="font-medium text-foreground mb-2">Your Course Reflection</h4>
                  <p class="text-sm text-muted-foreground leading-relaxed whitespace-pre-wrap">{{ course.course_reflection }}</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Enrollment Status & Progress -->
      <Card v-else-if="is_enrolled" class="border-primary/30 bg-primary/5">
        <CardContent class="p-6">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <CheckCircle class="h-5 w-5 text-primary flex-shrink-0" />
                <div>
                  <p class="font-medium text-foreground">You're enrolled in this class</p>
                  <p class="text-sm text-muted-foreground">
                    {{ progressText }}
                  </p>
                </div>
              </div>
              <Badge variant="secondary" class="text-secondary-foreground text-lg px-3 py-1">
                {{ completionPercentage }}%
              </Badge>
            </div>
            <Progress :model-value="completionPercentage" class="h-2" />
            
            <!-- Reflection Prompt when all lessons completed but course not finished -->
            <div v-if="completionPercentage === 100 && !is_completed" class="mt-4 p-4 bg-gradient-to-r from-primary/10 to-secondary/10 border border-primary/20 rounded-lg">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                  <div class="w-10 h-10 bg-primary/20 rounded-full flex items-center justify-center">
                    <BookOpen class="h-5 w-5 text-primary" />
                  </div>
                </div>
                <div class="flex-1">
                  <h3 class="font-semibold text-foreground mb-1">Almost There! 🎯</h3>
                  <p class="text-sm text-muted-foreground mb-3">
                    You've completed all lessons! The final step is to write a reflection about what you learned in this course to mark it as completed.
                  </p>
                  <Button @click="openReflectionModal" class="bg-primary hover:bg-primary/90">
                    <BookOpen class="h-4 w-4 mr-2" />
                    Write Course Reflection
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Modules and Lessons -->
      <div class="space-y-4">
        <h2 class="text-2xl font-bold text-foreground">Course Content</h2>
        
        <div class="space-y-4">
          <Card 
            v-for="(module, moduleIndex) in course.modules" 
            :key="module.id"
            class="border-primary/20"
          >
            <CardHeader>
              <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-2">
                    <Badge variant="secondary" class="text-secondary-foreground">
                      Module {{ moduleIndex + 1 }}
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                      {{ module.lessons.length }} lessons
                    </span>
                  </div>
                  <CardTitle class="text-xl">{{ module.name }}</CardTitle>
                  <CardDescription v-if="module.description" class="mt-1">
                    {{ module.description }}
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            
            <CardContent>
              <div class="space-y-2">
                <div 
                  v-for="(lesson, lessonIndex) in module.lessons" 
                  :key="lesson.id"
                  class="flex items-start gap-3 p-3 rounded-lg transition-colors"
                  :class="[
                    lesson.is_completed 
                      ? 'bg-primary/5 border border-primary/20' 
                      : canCompleteLesson(lesson)
                      ? is_enrolled 
                        ? 'bg-secondary/10 border border-secondary/30 ring-2 ring-secondary/20'
                        : 'bg-muted/30'
                      : is_enrolled 
                      ? 'bg-muted/30 opacity-60'
                      : 'bg-muted/30'
                  ]"
                >
                  <component 
                    :is="lesson.is_completed ? CheckCircle : Circle" 
                    class="h-5 w-5 mt-0.5 flex-shrink-0"
                    :class="lesson.is_completed ? 'text-primary' : 'text-muted-foreground'"
                  />
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="font-medium text-foreground" :class="{ 'line-through opacity-70': lesson.is_completed || is_completed }">
                        {{ lessonIndex + 1 }}. {{ lesson.name }}
                      </h4>
                      <Badge v-if="is_enrolled && canCompleteLesson(lesson)" variant="default" class="text-xs bg-secondary text-secondary-foreground">
                        Next
                      </Badge>
                    </div>
                    <p v-if="lesson.description" class="text-sm text-muted-foreground mt-1">
                      {{ lesson.description }}
                    </p>
                  </div>
                  <Button
                    v-if="is_enrolled && !lesson.is_completed && canCompleteLesson(lesson)"
                    size="sm"
                    class="ml-auto bg-primary text-primary-foreground hover:bg-primary/90 cursor-pointer"
                    @click="openCompleteModal(lesson)"
                  >
                    Complete
                  </Button>
                  <Button
                    v-else-if="lesson.is_completed || is_completed"
                    size="sm"
                    variant="outline"
                    class="ml-auto cursor-pointer"
                    @click="openSummaryModal(lesson)"
                  >
                    View Summary
                  </Button>
                  <div
                    v-else-if="is_enrolled && !lesson.is_completed && !canCompleteLesson(lesson)"
                    class="ml-auto text-sm text-muted-foreground flex items-center gap-1"
                  >
                    <Lock class="h-4 w-4" />
                    Complete previous lessons first
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Bottom Actions -->
      <Card v-if="!is_completed && (is_enrolled || can_join)" class="border-primary/20">
        <CardContent class="p-6">
          <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div>
              <p class="font-semibold text-foreground">
                Ready to {{ is_enrolled ? 'continue your' : 'start your' }} learning journey?
              </p>
              <p class="text-sm text-muted-foreground">
                {{ is_enrolled 
                  ? 'Keep going and complete all lessons to earn points!' 
                  : 'Join this class and start earning points today!' 
                }}
              </p>
            </div>
            <div class="flex gap-2">
              <Button 
                v-if="!is_enrolled && can_join"
                variant="default"
                size="lg"
                @click="joinCourse"
                class="bg-primary text-primary-foreground hover:bg-primary/90"
              >
                Join Class Now
              </Button>
              <Button 
                v-else-if="is_enrolled"
                variant="outline"
                @click="openLeaveModal"
                class="text-destructive hover:text-destructive"
              >
                <LogOut class="h-4 w-4 mr-2" />
                Leave Class
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Complete Lesson Modal -->
    <LessonCompletionModal 
      :is-open="isModalOpen"
      :lesson="selectedLesson"
      @update:is-open="isModalOpen = $event"
      @completed="onLessonCompleted"
    />

    <!-- Leave Course Confirmation Modal -->
    <Dialog :open="isLeaveModalOpen" @update:open="closeLeaveModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Leave Class</DialogTitle>
          <DialogDescription>
            Are you sure you want to leave this class? All points earned will be lost.
          </DialogDescription>
        </DialogHeader>
        
        <div class="flex justify-end gap-2 pt-4">
          <Button variant="outline" @click="closeLeaveModal">
            Cancel
          </Button>
          <Button 
            variant="destructive"
            @click="confirmLeaveCourse"
          >
            <LogOut class="h-4 w-4 mr-2" />
            Leave Class
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Lesson Summary Modal -->
    <Dialog :open="isSummaryModalOpen" @update:open="closeSummaryModal">
      <DialogContent class="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Lesson Summary</DialogTitle>
          <DialogDescription>
            {{ selectedLessonForSummary?.name }}
          </DialogDescription>
        </DialogHeader>
        
        <!-- Error Messages -->
        <ModalAlert 
          v-if="summaryError" 
          type="error" 
          :message="summaryError" 
        />
        
        <div class="space-y-4">
          <div v-if="!isEditingSummary">
            <div class="p-4 bg-muted/30 rounded-lg">
              <h4 class="font-medium mb-2">Summary</h4>
              <p v-if="lessonSummary" class="text-sm text-foreground">{{ lessonSummary }}</p>
              <p v-else class="text-sm text-muted-foreground italic">No summary provided</p>
            </div>
            
            <div v-if="lessonLink" class="p-4 bg-muted/30 rounded-lg">
              <h4 class="font-medium mb-2">Link</h4>
              <a :href="lessonLink" target="_blank" class="text-sm text-primary hover:underline flex items-center gap-1">
                <ExternalLink class="h-4 w-4" />
                {{ lessonLink }}
              </a>
            </div>
            
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="closeSummaryModal">
                Close
              </Button>
              <Button @click="startEditingSummary">
                Edit Summary
              </Button>
            </div>
          </div>
          
          <div v-else class="space-y-4">
            <div>
              <Label for="lesson-summary">Summary</Label>
              <Textarea
                id="lesson-summary"
                v-model="lessonSummary"
                placeholder="Describe what you learned from this lesson..."
                :rows="4"
                class="mt-1"
              />
            </div>
            
            <div>
              <Label for="lesson-link">Optional Link</Label>
              <div class="relative mt-1">
                <LinkIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input 
                  id="lesson-link" 
                  v-model="lessonLink"
                  type="url"
                  placeholder="https://your-notes.com/lesson-summary" 
                  class="pl-10"
                />
              </div>
            </div>
            
            <div class="flex justify-end gap-2">
              <Button variant="outline" @click="isEditingSummary = false" :disabled="isSaving">
                Cancel
              </Button>
              <Button @click="saveSummary" :disabled="isSaving">
                {{ isSaving ? 'Saving...' : 'Save Summary' }}
              </Button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Course Reflection Modal -->
    <CourseReflectionModal
      :is-open="isReflectionModalOpen"
      :course="course"
      @close="closeReflectionModal"
      @success="onCourseCompleted"
    />
  </AppLayout>
</template>
