<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Button } from '@/components/ui/button';
import { CheckCircle, Link as LinkIcon } from 'lucide-vue-next';
import ModalAlert from '@/components/ModalAlert.vue';

interface Lesson {
  id: number;
  name?: string;
  title?: string;
}

interface Props {
  isOpen: boolean;
  lesson: Lesson | null;
}

interface Emits {
  (e: 'update:isOpen', value: boolean): void;
  (e: 'completed'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const summary = ref('');
const link = ref('');
const completionError = ref('');
const isCompletingLesson = ref(false);

const lessonName = computed(() => props.lesson?.name || props.lesson?.title || 'Unknown Lesson');

const closeModal = () => {
  emit('update:isOpen', false);
  summary.value = '';
  link.value = '';
  completionError.value = '';
};

const submitCompletion = () => {
  if (!props.lesson || !summary.value.trim()) {
    completionError.value = 'Please provide a summary before completing the lesson.';
    return;
  }

  isCompletingLesson.value = true;
  completionError.value = '';  // Prepare data - only include link if it's not empty and looks like a URL
  const data: any = {
    summary: summary.value,
  };
  
  // Only include link if it's not empty and looks like a valid URL
  if (link.value.trim()) {
    try {
      new URL(link.value.trim());
      data.link = link.value.trim();
    } catch {
      completionError.value = 'Please enter a valid URL (e.g., https://example.com) or leave the link field empty.';
      isCompletingLesson.value = false;
      return;
    }
  }
  
  router.post(`/lessons/${props.lesson.id}/complete`, data, {
    onSuccess: () => {
      emit('completed');
      closeModal();
    },
    onError: (errors: any) => {
      if (errors.summary) {
        completionError.value = Array.isArray(errors.summary) ? errors.summary[0] : errors.summary;
      } else if (errors.link) {
        completionError.value = 'Please enter a valid URL (e.g., https://example.com) or leave the link field empty.';
      } else {
        completionError.value = 'Failed to complete lesson. Please try again.';
      }
    },
    onFinish: () => {
      isCompletingLesson.value = false;
    },
    preserveScroll: true,
  });
};

// Reset form when modal closes
watch(() => props.isOpen, (newValue) => {
  if (!newValue) {
    summary.value = '';
    link.value = '';
    completionError.value = '';
  }
});
</script>

<template>
  <Dialog :open="isOpen" @update:open="closeModal">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>Complete Lesson: {{ lessonName }}</DialogTitle>
        <DialogDescription>
          Share your insights and mark this lesson as complete.
        </DialogDescription>
      </DialogHeader>
      
      <!-- Error Messages -->
      <ModalAlert 
        v-if="completionError" 
        type="error" 
        :message="completionError" 
      />
      
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
          <p class="text-xs text-muted-foreground">
            Share a resource, project, or reference related to this lesson
          </p>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-4">
        <Button variant="outline" @click="closeModal" :disabled="isCompletingLesson">
          Cancel
        </Button>
        <Button 
          @click="submitCompletion"
          :disabled="!summary.trim() || isCompletingLesson"
        >
          <CheckCircle v-if="!isCompletingLesson" class="h-4 w-4 mr-2" />
          <div v-else class="h-4 w-4 mr-2 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
          {{ isCompletingLesson ? 'Completing...' : 'Mark as Complete' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>