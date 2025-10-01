<template>
  <Dialog :open="isOpen" @update:open="closeModal">
    <DialogContent class="sm:max-w-[600px]">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <BookOpen class="h-5 w-5 text-primary" />
          Course Reflection
        </DialogTitle>
        <DialogDescription>
          Share your thoughts about what you learned in this course. This reflection helps you solidify your learning and marks the course as completed.
        </DialogDescription>
      </DialogHeader>
      
      <div class="space-y-4">
        <div class="bg-muted/30 p-4 rounded-lg">
          <div class="flex items-center gap-2 mb-2">
            <Trophy class="h-4 w-4 text-primary" />
            <span class="font-medium text-sm">Course: {{ course?.title || 'Unknown Course' }}</span>
          </div>
          <p class="text-sm text-muted-foreground">
            You've completed all lessons! Now take a moment to reflect on your learning journey.
          </p>
        </div>

        <div class="space-y-2">
          <Label for="reflection">What did you learn? *</Label>
          <Textarea
            id="reflection"
            v-model="reflection"
            placeholder="Share your key takeaways, insights, and how this course has helped you grow..."
            :rows="6"
            class="resize-none"
          />
          <p class="text-xs text-muted-foreground">
            Minimum 50 characters. Be specific about what you learned and how it applies to your goals.
          </p>
          <InputError v-if="reflectionError" :message="reflectionError" />
        </div>

        <Alert v-if="reflectionError" variant="destructive">
          <AlertCircle class="h-4 w-4" />
          <AlertDescription>{{ reflectionError }}</AlertDescription>
        </Alert>
      </div>

      <div class="flex justify-end gap-2 pt-4">
        <Button variant="outline" @click="closeModal" :disabled="isSubmitting">
          Cancel
        </Button>
        <Button 
          @click="submitReflection"
          :disabled="!isReflectionValid || isSubmitting"
        >
          <CheckCircle v-if="!isSubmitting" class="h-4 w-4 mr-2" />
          <div v-else class="h-4 w-4 mr-2 animate-spin rounded-full border-2 border-current border-t-transparent"></div>
          {{ isSubmitting ? 'Submitting...' : 'Complete Course' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import InputError from '@/components/InputError.vue';
import { BookOpen, Trophy, CheckCircle, AlertCircle } from 'lucide-vue-next';

interface Course {
  id: number;
  title: string;
}

interface Props {
  isOpen: boolean;
  course?: Course | null;
}

interface Emits {
  (e: 'close'): void;
  (e: 'success'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const reflection = ref('');
const reflectionError = ref('');
const isSubmitting = ref(false);

const isReflectionValid = computed(() => {
  return reflection.value.trim().length >= 50;
});

const closeModal = () => {
  // Always allow closing, even if submitting (for success cases)
  reflection.value = '';
  reflectionError.value = '';
  isSubmitting.value = false;
  emit('close');
};

const submitReflection = () => {
  if (!props.course) {
    reflectionError.value = 'Course information is missing.';
    return;
  }

  if (!isReflectionValid.value) {
    reflectionError.value = 'Please write at least 50 characters about what you learned.';
    return;
  }

  isSubmitting.value = true;
  reflectionError.value = '';

  router.post(`/classes/${props.course.id}/complete`, {
    reflection: reflection.value.trim()
  }, {
    onSuccess: () => {
      // Close modal and emit success
      closeModal();
      emit('success');
    },
    onError: (errors: any) => {
      if (errors.reflection) {
        reflectionError.value = errors.reflection;
      } else if (errors.message) {
        reflectionError.value = errors.message;
      } else {
        reflectionError.value = 'Something went wrong. Please try again.';
      }
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
    preserveScroll: false, // Allow page refresh
  });
};

// Reset form when modal opens
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    reflection.value = '';
    reflectionError.value = '';
  }
});
</script>
