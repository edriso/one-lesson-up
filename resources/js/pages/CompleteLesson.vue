<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  ArrowLeft, 
  CheckCircle,
  Link as LinkIcon,
  FileText,
  Notebook,
} from 'lucide-vue-next';

interface Props {
  lesson: {
    id: number;
    name: string;
    description: string;
    module: {
      name: string;
    };
    course: {
      id: number;
      name: string;
    };
  };
}

const props = defineProps<Props>();

const form = useForm({
  summary: '',
  link: '',
});

const submit = () => {
  form.post(`/lessons/${props.lesson.id}/complete`, {
    onSuccess: () => {
      // Redirect handled by controller
    },
  });
};
</script>

<template>
  <Head :title="`Complete: ${lesson.name}`" />
  
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="sm" @click="$inertia.visit(`/classes/${lesson.course.id}`)">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Back to Class
        </Button>
      </div>

      <!-- Breadcrumb Info -->
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <Notebook class="h-4 w-4" />
          <span>{{ lesson.course.name }}</span>
          <span>›</span>
          <span>{{ lesson.module.name }}</span>
        </div>
        <h1 class="text-4xl font-bold text-foreground">
          {{ lesson.name }}
        </h1>
        <p v-if="lesson.description" class="text-lg text-muted-foreground">
          {{ lesson.description }}
        </p>
      </div>

      <!-- Info Card -->
      <Card class="border-secondary/30 bg-secondary/5">
        <CardContent class="p-4">
          <div class="flex gap-3">
            <CheckCircle class="h-5 w-5 text-secondary-foreground mt-0.5 flex-shrink-0" />
            <div class="space-y-1">
              <p class="text-sm font-medium text-foreground">
                Complete this lesson to earn points
              </p>
              <p class="text-sm text-muted-foreground">
                Share a brief summary of what you learned and optionally include a link to your work or notes.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Completion Form -->
      <form @submit.prevent="submit">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <FileText class="h-5 w-5" />
              Lesson Summary
            </CardTitle>
            <CardDescription>
              Write a summary of what you learned in this lesson
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <!-- Summary Field -->
            <div class="space-y-2">
              <Label for="summary">
                Summary *
                <span class="text-sm text-muted-foreground font-normal ml-2">
                  What did you learn? What are the key takeaways?
                </span>
              </Label>
              <Textarea 
                id="summary" 
                v-model="form.summary"
                placeholder="Example: I learned about React hooks, specifically useState and useEffect. The key takeaway is understanding component lifecycle and state management patterns..."
                :rows="6"
                required
                :class="form.errors.summary ? 'border-destructive' : ''"
              />
              <p v-if="form.errors.summary" class="text-sm text-destructive">
                {{ form.errors.summary }}
              </p>
              <p class="text-xs text-muted-foreground">
                {{ form.summary.length }} / 1000 characters
              </p>
            </div>

            <!-- Link Field (Optional) -->
            <div class="space-y-2">
              <Label for="link">
                Resource Link (Optional)
                <span class="text-sm text-muted-foreground font-normal ml-2">
                  Link to your notes, project, or related resources
                </span>
              </Label>
              <div class="relative">
                <LinkIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                <Input 
                  id="link" 
                  v-model="form.link"
                  type="url"
                  placeholder="https://github.com/yourname/project or https://notion.so/notes"
                  class="pl-10"
                  :class="{ 'border-destructive': form.errors.link }"
                />
              </div>
              <p v-if="form.errors.link" class="text-sm text-destructive">
                {{ form.errors.link }}
              </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3 pt-4">
              <Button 
                type="button" 
                variant="outline"
                @click="$inertia.visit(`/classes/${lesson.course.id}`)"
                class="flex-1"
              >
                Cancel
              </Button>
              <Button 
                type="submit" 
                variant="default"
                :disabled="form.processing || !form.summary"
                class="flex-1 bg-primary text-primary-foreground hover:bg-primary/90"
              >
                <CheckCircle class="h-4 w-4 mr-2" />
                {{ form.processing ? 'Completing...' : 'Complete Lesson' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </form>
    </div>
  </AppLayout>
</template>
