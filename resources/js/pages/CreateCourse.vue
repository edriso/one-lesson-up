<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import { 
  BookOpen, 
  Plus, 
  Trash2, 
  GripVertical,
  ArrowLeft,
  Save,
  AlertCircle,
  Tag,
  X,
  ChevronUp
} from 'lucide-vue-next';

interface Lesson {
  id: string;
  name: string;
  description: string;
}

interface Module {
  id: string;
  name: string;
  description: string;
  lessons: Lesson[];
}

interface Tag {
  id: number;
  name: string;
}

interface Props {
  available_tags?: Tag[];
}

const props = defineProps<Props>();

const customTagInput = ref('');

const form = useForm({
  name: '',
  description: '',
  link: '',
  is_public: true, // Default to public
  tags: [] as string[],
  modules: [
    {
      id: crypto.randomUUID(),
      name: '',
      description: '',
      lessons: [
        { id: crypto.randomUUID(), name: '', description: '' }
      ]
    }
  ] as Module[]
});

const addModule = () => {
  form.modules.push({
    id: crypto.randomUUID(),
    name: '',
    description: '',
    lessons: [
      { id: crypto.randomUUID(), name: '', description: '' }
    ]
  });
};

const removeModule = (moduleId: string) => {
  if (form.modules.length > 1) {
    form.modules = form.modules.filter(m => m.id !== moduleId);
  }
};

const addLesson = (moduleId: string) => {
  const module = form.modules.find(m => m.id === moduleId);
  if (module) {
    module.lessons.push({
      id: crypto.randomUUID(),
      name: '',
      description: ''
    });
  }
};

const removeLesson = (moduleId: string, lessonId: string) => {
  const module = form.modules.find(m => m.id === moduleId);
  if (module && module.lessons.length > 1) {
    module.lessons = module.lessons.filter(l => l.id !== lessonId);
  }
};

// Tag selection functionality
const addTag = (tagName: string) => {
  if (form.tags.length < 3 && !form.tags.includes(tagName)) {
    form.tags.push(tagName);
  }
};

const removeTag = (tagName: string) => {
  const index = form.tags.indexOf(tagName);
  if (index > -1) {
    form.tags.splice(index, 1);
  }
};

const addCustomTag = (tagName: string) => {
  if (tagName.trim() && form.tags.length < 3 && !form.tags.includes(tagName.trim())) {
    form.tags.push(tagName.trim());
  }
};

// Filter available tags based on input
const filteredAvailableTags = computed(() => {
  if (!customTagInput.value || !props.available_tags || props.available_tags.length === 0) {
    return [];
  }
  
  const query = customTagInput.value.toLowerCase().trim();
  if (query.length === 0) return [];
  
  return props.available_tags.filter(tag => {
    const tagName = tag.name.toLowerCase();
    const isMatch = tagName.includes(query);
    const isNotSelected = !form.tags.includes(tag.name);
    return isMatch && isNotSelected;
  });
});

// Handle Enter key in tag input
const handleTagInputEnter = () => {
  if (customTagInput.value.trim()) {
    addCustomTag(customTagInput.value);
    customTagInput.value = '';
  }
};

// Select tag from autocomplete
const selectTag = (tagName: string) => {
  addTag(tagName);
  customTagInput.value = '';
};

// Handle tag input change
const onTagInputChange = () => {
  // This can be used for additional logic if needed
};

// Add tag from input (either existing or new)
const addTagFromInput = () => {
  if (customTagInput.value.trim()) {
    addTag(customTagInput.value.trim());
    customTagInput.value = '';
  }
};

// Get the appropriate button text
const getAddButtonText = () => {
  if (!customTagInput.value.trim()) return 'Add Tag';
  
  const isExistingTag = props.available_tags?.some(tag => 
    tag.name.toLowerCase() === customTagInput.value.toLowerCase().trim()
  );
  
  return isExistingTag ? 'Add Existing Tag' : 'Create New Tag';
};

// Go to top functionality
const goToTop = () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
};

const submit = () => {
  form.post('/classes', {
    onSuccess: () => {
      // Redirect handled by controller
    },
  });
};

const getTotalLessons = () => {
  return form.modules.reduce((total, module) => total + module.lessons.length, 0);
};
</script>

<template>
  <Head title="Create Class" />
  
  <AppLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex items-center gap-4">
        <Button variant="ghost" size="sm" @click="$inertia.visit('/classes')">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Back to Classes
        </Button>
      </div>

      <div>
        <h1 class="text-4xl font-bold text-foreground mb-2">
          Create New Class
        </h1>
        <p class="text-muted-foreground">
          Build your learning path with modules and lessons. Once created, classes cannot be edited.
        </p>
      </div>

      <!-- Info Alert -->
      <Card class="border-secondary/30 bg-secondary/5">
        <CardContent class="p-4">
          <div class="flex gap-3">
            <AlertCircle class="h-5 w-5 text-secondary-foreground mt-0.5 flex-shrink-0" />
            <div class="space-y-1">
              <p class="text-sm font-medium text-foreground">
                Important Information
              </p>
              <ul class="text-sm text-muted-foreground space-y-1 list-disc list-inside">
                <li>Classes cannot be edited or deleted after creation</li>
                <li>Leaving a class later will deduct all points earned from that class</li>
                <li>You can only be enrolled in one class at a time</li>
                <li><strong>Learning deadline:</strong> Complete your class within the calculated deadline to earn bonus points (maintain at least 5 lessons per week pace)</li>
                <li>This simulates a 5-day work week learning schedule with 2 rest days</li>
                <li>After the deadline, you can still complete the class but won't receive bonus points</li>
                <li>You will be automatically enrolled in your class</li>
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      <form @submit.prevent="submit" class="space-y-6">
        <!-- Class Details -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <BookOpen class="h-5 w-5" />
              Class Information
            </CardTitle>
            <CardDescription>
              Basic details about your class
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="name">Class Title *</Label>
              <Input 
                id="name" 
                v-model="form.name"
                placeholder="e.g., Advanced Web Development" 
                required
                :class="{ 'border-destructive': form.errors.name }"
              />
              <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="space-y-2">
              <Label for="description">Description *</Label>
              <Textarea 
                id="description" 
                v-model="form.description"
                placeholder="Describe what students will learn in this class"
                :rows="4"
                required
                :class="form.errors.description ? 'border-destructive' : ''"
              />
              <p v-if="form.errors.description" class="text-sm text-destructive">{{ form.errors.description }}</p>
            </div>

            <div class="space-y-2">
              <Label for="link">Resource Link (Optional)</Label>
              <Input 
                id="link" 
                v-model="form.link"
                type="url"
                placeholder="https://example.com/resources" 
                :class="{ 'border-destructive': form.errors.link }"
              />
              <p class="text-sm text-muted-foreground">Add a link to class materials, syllabus, or resources</p>
              <p v-if="form.errors.link" class="text-sm text-destructive">{{ form.errors.link }}</p>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <div class="space-y-0.5">
                  <Label for="is_public">Make Class Public</Label>
                  <p class="text-sm text-muted-foreground">
                    Allow other users to discover and join this class
                  </p>
                </div>
                <Switch 
                  id="is_public"
                  v-model:checked="form.is_public"
                />
              </div>
              <p v-if="form.errors.is_public" class="text-sm text-destructive">{{ form.errors.is_public }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Tags Selection -->
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Tag class="h-5 w-5" />
              Tags (Optional)
            </CardTitle>
            <CardDescription>
              Add up to 3 tags to help categorize your class
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <!-- Selected Tags -->
            <div v-if="form.tags.length > 0" class="space-y-2">
              <Label>Selected Tags ({{ form.tags.length }}/3)</Label>
              <div class="flex flex-wrap gap-2">
                <Badge 
                  v-for="tag in form.tags" 
                  :key="tag" 
                  variant="secondary" 
                  class="flex items-center gap-1 px-3 py-1"
                >
                  {{ tag }}
                  <Button 
                    type="button" 
                    variant="ghost" 
                    size="sm" 
                    class="h-4 w-4 p-0 hover:bg-destructive/20"
                    @click="removeTag(tag)"
                  >
                    <X class="h-3 w-3" />
                  </Button>
                </Badge>
              </div>
            </div>

            <!-- Add Tag Input -->
            <div v-if="form.tags.length < 3" class="space-y-2">
              <Label for="tag-input">Add Tag</Label>
              <div class="relative">
                <Input 
                  id="tag-input"
                  v-model="customTagInput"
                  placeholder="Search existing tags or create new ones..."
                  @keydown.enter.prevent="handleTagInputEnter"
                  @input="onTagInputChange"
                  class="flex-1"
                />
                
                <!-- Autocomplete suggestions -->
                <div v-if="customTagInput && filteredAvailableTags.length > 0" 
                     class="absolute top-full left-0 right-0 z-50 mt-1 bg-background border border-border rounded-md shadow-lg max-h-32 overflow-y-auto">
                  <div class="px-3 py-2 text-xs font-medium text-muted-foreground border-b border-border">
                    Existing Tags
                  </div>
                  <div v-for="tag in filteredAvailableTags" 
                       :key="tag.id"
                       class="px-3 py-2 hover:bg-muted cursor-pointer text-sm border-b border-border last:border-b-0"
                       @click="selectTag(tag.name)">
                    <span class="font-medium">{{ tag.name }}</span>
                  </div>
                </div>
                
                <!-- Create new tag message -->
                <div v-if="customTagInput && customTagInput.trim().length > 0 && filteredAvailableTags.length === 0 && available_tags && available_tags.length > 0" 
                     class="absolute top-full left-0 right-0 z-50 mt-1 bg-background border border-border rounded-md shadow-lg p-3">
                  <p class="text-sm text-muted-foreground">
                    <span class="font-medium">"{{ customTagInput }}"</span> will be created as a new tag
                  </p>
                </div>
              </div>
              <div class="flex gap-2">
                <Button 
                  type="button" 
                  @click="addTagFromInput"
                  :disabled="!customTagInput.trim()"
                  class="flex-1"
                >
                  {{ getAddButtonText() }}
                </Button>
              </div>
            </div>

            <p v-if="form.tags.length >= 3" class="text-sm text-muted-foreground">
              Maximum of 3 tags reached
            </p>
          </CardContent>
        </Card>

        <!-- Modules and Lessons -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-2xl font-bold text-foreground">Modules & Lessons</h2>
              <p class="text-sm text-muted-foreground">
                {{ form.modules.length }} module(s), {{ getTotalLessons() }} lesson(s) total
              </p>
            </div>
            <Button type="button" @click="addModule" variant="outline">
              <Plus class="h-4 w-4 mr-2" />
              Add Module
            </Button>
          </div>

          <!-- Module Cards -->
          <div class="space-y-4">
            <Card 
              v-for="(module, moduleIndex) in form.modules" 
              :key="module.id"
              class="border-primary/20"
            >
              <CardHeader>
                <div class="flex items-start justify-between gap-4">
                  <div class="flex items-start gap-3 flex-1">
                    <GripVertical class="h-5 w-5 text-muted-foreground mt-2" />
                    <div class="flex-1 space-y-4">
                      <div class="flex items-center gap-2">
                        <Badge variant="secondary" class="text-secondary-foreground">
                          Module {{ moduleIndex + 1 }}
                        </Badge>
                        <span class="text-sm text-muted-foreground">
                          {{ module.lessons.length }} lesson(s)
                        </span>
                      </div>

                      <div class="space-y-2">
                        <Label :for="`module-${module.id}-name`">Module Title *</Label>
                        <Input 
                          :id="`module-${module.id}-name`"
                          v-model="module.name"
                          placeholder="e.g., Introduction to React"
                          required
                          :class="{ 'border-destructive': form.errors[`modules.${moduleIndex}.name`] }"
                        />
                        <p v-if="form.errors[`modules.${moduleIndex}.name`]" class="text-sm text-destructive">
                          {{ form.errors[`modules.${moduleIndex}.name`] }}
                        </p>
                      </div>

                      <div class="space-y-2">
                        <Label :for="`module-${module.id}-description`">Module Description</Label>
                        <Textarea 
                          :id="`module-${module.id}-description`"
                          v-model="module.description"
                          placeholder="Brief description of this module"
                          :rows="2"
                        />
                      </div>
                    </div>
                  </div>
                  
                  <Button 
                    type="button"
                    variant="ghost" 
                    size="sm"
                    @click="removeModule(module.id)"
                    :disabled="form.modules.length === 1"
                    class="text-destructive hover:text-destructive"
                  >
                    <Trash2 class="h-4 w-4" />
                  </Button>
                </div>
              </CardHeader>

              <CardContent class="space-y-3">
                <!-- Lessons -->
                <div class="space-y-3 pl-8">
                  <div class="flex items-center justify-between">
                    <Label class="text-sm font-semibold">Lessons</Label>
                    <Button 
                      type="button"
                      size="sm" 
                      variant="outline"
                      @click="addLesson(module.id)"
                    >
                      <Plus class="h-3 w-3 mr-1" />
                      Add Lesson
                    </Button>
                  </div>

                  <div 
                    v-for="(lesson, lessonIndex) in module.lessons" 
                    :key="lesson.id"
                    class="flex gap-3 p-3 bg-muted/30 rounded-lg"
                  >
                    <div class="flex-1 space-y-2">
                      <div class="flex items-center gap-2">
                        <Badge variant="outline" class="text-xs">
                          Lesson {{ lessonIndex + 1 }}
                        </Badge>
                      </div>

                      <div class="space-y-1">
                        <Input 
                          v-model="lesson.name"
                          placeholder="Lesson title"
                          required
                          class="bg-background"
                          :class="{ 'border-destructive': form.errors[`modules.${moduleIndex}.lessons.${lessonIndex}.name`] }"
                        />
                        <p v-if="form.errors[`modules.${moduleIndex}.lessons.${lessonIndex}.name`]" class="text-sm text-destructive">
                          {{ form.errors[`modules.${moduleIndex}.lessons.${lessonIndex}.name`] }}
                        </p>
                      </div>

                      <Textarea 
                        v-model="lesson.description"
                        placeholder="Lesson description (optional)"
                        :rows="2"
                        class="bg-background"
                      />
                    </div>

                    <Button 
                      type="button"
                      variant="ghost" 
                      size="sm"
                      @click="removeLesson(module.id, lesson.id)"
                      :disabled="module.lessons.length === 1"
                      class="text-destructive hover:text-destructive"
                    >
                      <Trash2 class="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>

        <!-- Form Actions -->
        <Card class="border-primary/30 bg-primary/5">
          <CardContent class="p-6">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
              <div>
                <p class="font-semibold text-foreground mb-1">
                  Ready to create your class?
                </p>
                <p class="text-sm text-muted-foreground">
                  Review your modules and lessons before submitting. Classes cannot be edited or deleted after creation.
                </p>
              </div>
              <div class="flex gap-2 w-full md:w-auto">
                <Button 
                  type="button" 
                  variant="outline"
                  @click="$inertia.visit('/classes')"
                  class="flex-1 md:flex-none"
                >
                  Cancel
                </Button>
                <Button 
                  type="submit" 
                  variant="default"
                  :disabled="form.processing"
                  class="flex-1 md:flex-none bg-primary text-primary-foreground hover:bg-primary/90"
                >
                  <Save class="h-4 w-4 mr-2" />
                  {{ form.processing ? 'Creating...' : 'Create Class' }}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </form>

      <!-- Go to Top Button -->
      <Button 
        @click="goToTop"
        class="fixed bottom-6 right-6 z-50 rounded-full w-12 h-12 shadow-lg bg-primary text-primary-foreground hover:bg-primary/90"
        size="sm"
      >
        <ChevronUp class="h-5 w-5" />
      </Button>
    </div>
  </AppLayout>
</template>
