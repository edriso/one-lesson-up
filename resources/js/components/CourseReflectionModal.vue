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
        <div class="space-y-2">
          <Label for="reflection">What did you learn from this course? *</Label>
          <Textarea
            id="reflection"
            v-model="reflection"
            placeholder="Share your key takeaways and insights..."
            :rows="4"
            class="resize-none"
          />
          <p class="text-xs text-muted-foreground">
            Minimum 50 characters
          </p>
          <InputError v-if="reflectionError" :message="reflectionError" />
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-4">
        <Button variant="outline" @click="closeModal" :disabled="isSubmitting">
          Cancel
        </Button>
        <Button @click="submitReflection" :disabled="isSubmitting || !reflection.trim()">
          <Star class="h-4 w-4 mr-2" />
          {{ isSubmitting ? 'Submitting...' : 'Complete Course' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { BookOpen, Star } from 'lucide-vue-next';

interface Props {
  isOpen: boolean;
  course?: {
    id: number;
    title: string;
  };
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  success: [];
}>();

const reflection = ref('');
const reflectionError = ref('');
const isSubmitting = ref(false);

const closeModal = () => {
  if (isSubmitting.value) return;
  
  reflection.value = '';
  reflectionError.value = '';
  emit('close');
};

const submitReflection = async () => {
  if (!reflection.value.trim() || reflection.value.trim().length < 50) {
    reflectionError.value = 'Please provide a reflection of at least 50 characters.';
    return;
  }

  isSubmitting.value = true;
  reflectionError.value = '';

  try {
    await router.post(`/classes/${props.course?.id}/complete`, {
      reflection: reflection.value.trim()
    }, {
      preserveScroll: false,
      onSuccess: () => {
        reflection.value = '';
        reflectionError.value = '';
        emit('success');
        emit('close');
      },
      onError: (errors) => {
        if (errors.reflection) {
          reflectionError.value = errors.reflection;
        } else {
          reflectionError.value = 'Failed to submit reflection. Please try again.';
        }
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  } catch {
    reflectionError.value = 'Failed to submit reflection. Please try again.';
    isSubmitting.value = false;
  }
};
</script>