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

// Modal state
const isModalOpen = ref(false);
const selectedLesson = ref<Lesson | null>(null);
const summary = ref('');
const link = ref('');

// Leave course modal state
const isLeaveModalOpen = ref(false);

// Lesson summary modal state
const isSummaryModalOpen = ref(false);
const selectedLessonForSummary = ref<Lesson | null>(null);
const lessonSummary = ref('');
const lessonLink = ref('');
const isEditingSummary = ref(false);

const openCompleteModal = (lesson: Lesson) => {
  selectedLesson.value = lesson;
  summary.value = '';
  link.value = '';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  selectedLesson.value = null;
  summary.value = '';
  link.value = '';
};

const submitCompletion = () => {
  if (!selectedLesson.value || !summary.value.trim()) return;
  
  router.post(`/lessons/${selectedLesson.value.id}/complete`, {
    summary: summary.value,
    link: link.value,
  }, {
    onSuccess: () => {
      closeModal();
    },
    preserveScroll: true,
  });
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
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
    });
    
    if (response.ok) {
      const data = await response.json();
      lessonSummary.value = data.summary || '';
      lessonLink.value = data.link || '';
    } else {
      // If no summary exists, start in edit mode
      lessonSummary.value = '';
      lessonLink.value = '';
      isEditingSummary.value = true;
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
};

const startEditingSummary = () => {
  isEditingSummary.value = true;
};

const saveSummary = async () => {
  if (!selectedLessonForSummary.value || !lessonSummary.value.trim()) return;
  
  try {
    const response = await fetch(`/lessons/${selectedLessonForSummary.value.id}/summary`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({
        summary: lessonSummary.value,
        link: lessonLink.value,
      }),
    });
    
    if (response.ok) {
      closeSummaryModal();
    } else {
      const errorData = await response.json();
      console.error('Failed to save lesson summary:', errorData);
      alert('Failed to save lesson summary. Please try again.');
    }
  } catch (error) {
    console.error('Failed to save lesson summary:', error);
    alert('Failed to save lesson summary. Please try again.');
  }
};

const joinCourse = () => {
  router.post(`/classes/${props.course.id}/join`, {}, {
    preserveScroll: false,
  });
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    month: 'long',
    day: 'numeric',
    year: 'numeric'
  });
};

const completionPercentage = computed(() => {
  // Add safety checks
  if (!props.course || !props.course.modules || props.course.total_lessons === 0) return 0;
  
  // Find the current module (first module with incomplete lessons)
  const currentModule = props.course.modules.find(module => 
    module.lessons && module.lessons.some(lesson => !lesson.is_completed)
  );
  
  if (!currentModule) {
    // All modules completed - show 100%
    return 100;
  }
  
  // If it's the last module, show overall progress
  const isLastModule = props.course.modules.indexOf(currentModule) === props.course.modules.length - 1;
  
  if (isLastModule) {
    // Last module - show overall course progress
    const completed = props.completed_lessons_count || 0;
    const total = props.course.total_lessons || 1;
    return Math.round((completed / total) * 100);
  } else {
    // Not last module - show current module progress
    const moduleCompletedLessons = currentModule.lessons.filter(lesson => lesson.is_completed).length;
    const moduleTotalLessons = currentModule.lessons.length;
    return Math.round((moduleCompletedLessons / moduleTotalLessons) * 100);
  }
});

// Find the next available lesson (first incomplete lesson in sequence)
const nextAvailableLesson = computed(() => {
  if (!props.course || !props.course.modules) return null;
  
  // Go through modules in order
  for (const module of props.course.modules) {
    if (!module.lessons) continue;
    
    // Go through lessons in order within the module
    for (const lesson of module.lessons) {
      if (!lesson.is_completed) {
        return lesson;
      }
    }
  }
  
  return null; // All lessons completed
});

// Check if a lesson can be completed (only the next available lesson)
const canCompleteLesson = (lesson: any) => {
  const nextLesson = nextAvailableLesson.value;
  return nextLesson && nextLesson.id === lesson.id;
};

const progressText = computed(() => {
  if (props.course.total_lessons === 0) return 'No lessons available';
  
  // Find the current module (first module with incomplete lessons)
  const currentModule = props.course.modules.find(module => 
    module.lessons.some(lesson => !lesson.is_completed)
  );
  
  if (!currentModule) {
    // All modules completed
    return `${props.completed_lessons_count} of ${props.course.total_lessons} lessons completed`;
  }
  
  // If it's the last module, show overall progress
  const isLastModule = props.course.modules.indexOf(currentModule) === props.course.modules.length - 1;
  
  if (isLastModule) {
    // Last module - show overall course progress
    return `${props.completed_lessons_count} of ${props.course.total_lessons} lessons completed`;
  } else {
    // Not last module - show current module progress
    const moduleCompletedLessons = currentModule.lessons.filter(lesson => lesson.is_completed).length;
    const moduleTotalLessons = currentModule.lessons.length;
    return `${moduleCompletedLessons} of ${moduleTotalLessons} lessons in current module`;
  }
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
            <Button variant="ghost" size="sm" @click="router.visit('/classes')">
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
              <span>Created {{ formatDate(course.created_at) }}</span>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col gap-2">
          <Button 
            v-if="can_join"
            variant="default"
            @click="joinCourse"
            class="bg-primary text-primary-foreground hover:bg-primary/90"
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
          >
            <ExternalLink class="h-4 w-4 mr-2" />
            Resources
          </Button>
        </div>
      </div>

      <!-- Enrollment Status & Progress -->
      <Card v-if="is_enrolled" class="border-primary/30 bg-primary/5">
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
          </div>
        </CardContent>
      </Card>

      <!-- Completed Course Status -->
      <Card v-else-if="is_completed" class="mb-4 p-4 bg-gradient-to-r from-primary/20 to-secondary/20 border border-primary/30 rounded-lg">
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
                  You completed this class on {{ formatDate(completion_date!) }}. 
                </p>
              </div>
            </div>
              <Badge variant="default" class="bg-primary text-primary-foreground text-lg px-3 py-1">
                100%
              </Badge>
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
                    class="ml-auto bg-primary text-primary-foreground hover:bg-primary/90"
                    @click="openCompleteModal(lesson)"
                  >
                    Complete
                  </Button>
                  <Button
                    v-else-if="lesson.is_completed || is_completed"
                    size="sm"
                    variant="outline"
                    class="ml-auto"
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
      <Card v-if="!is_completed" class="border-primary/20">
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
    <Dialog :open="isModalOpen" @update:open="closeModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Complete Lesson: {{ selectedLesson?.name }}</DialogTitle>
          <DialogDescription>
            Share your insights and mark this lesson as complete.
          </DialogDescription>
        </DialogHeader>
        
        <div class="space-y-4">
          <div class="space-y-2">
            <Label for="summary">Your Summary *</Label>
            <Textarea 
              id="summary" 
              v-model="summary"
              placeholder="e.g., I learned about Vue 3's Composition API, reactive state management with ref and reactive, and how to use computed properties for derived state."
              :rows="4"
              required
            />
          </div>

          <div class="space-y-2">
            <Label for="link">Optional Resource Link</Label>
            <div class="relative">
              <LinkIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input 
                id="link" 
                v-model="link"
                type="url"
                placeholder="https://your-notes.com/lesson-summary" 
                class="pl-10"
              />
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-4">
          <Button variant="outline" @click="closeModal">
            Cancel
          </Button>
          <Button 
            @click="submitCompletion"
            :disabled="!summary.trim()"
          >
            <CheckCircle class="h-4 w-4 mr-2" />
            Mark as Complete
          </Button>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Leave Course Confirmation Modal -->
    <Dialog :open="isLeaveModalOpen" @update:open="closeLeaveModal">
      <DialogContent class="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Leave Class</DialogTitle>
          <DialogDescription>
            Are you sure you want to leave this class? Your progress will be saved but you will need to rejoin to continue.
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
              <Button variant="outline" @click="isEditingSummary = false">
                Cancel
              </Button>
              <Button @click="saveSummary">
                Save Summary
              </Button>
            </div>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </AppLayout>
</template>
