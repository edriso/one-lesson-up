<template>
    <Dialog :open="isOpen" @update:open="(open) => !open && closeModal()">
        <DialogContent class="sm:max-w-[600px]">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <GraduationCap class="h-5 w-5 text-primary" />
                    {{
                        isEditing
                            ? 'Edit Course Reflection'
                            : 'Course Reflection'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? 'Update your thoughts about what you learned in this course.'
                            : 'Share your thoughts about what you learned in this course. This reflection helps you solidify your learning and marks the course as completed.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="reflection"
                        >What did you learn from this course? *</Label
                    >
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
                    <InputError
                        v-if="reflectionError"
                        :message="reflectionError"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="reflection_link"
                        >Link to your work (optional)</Label
                    >
                    <Input
                        id="reflection_link"
                        v-model="reflectionLink"
                        type="url"
                        placeholder="https://example.com/your-work"
                    />
                    <p class="text-xs text-muted-foreground">
                        Share a link to your project, portfolio, or related work
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4">
                <Button
                    variant="outline"
                    @click="closeModal"
                    :disabled="isSubmitting"
                >
                    Cancel
                </Button>
                <Button
                    @click="submitReflection"
                    :disabled="isSubmitting || !reflection.trim()"
                >
                    <BadgeCheck class="h-4 w-4" />
                    {{
                        isSubmitting
                            ? 'Submitting...'
                            : isEditing
                              ? 'Update Reflection'
                              : 'Complete Class'
                    }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>

<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { router } from '@inertiajs/vue3';
import { BadgeCheck, GraduationCap } from 'lucide-vue-next';
import { ref, watch } from 'vue';

interface Props {
    isOpen: boolean;
    course?: {
        id: number;
        title: string;
    };
    existingReflection?: string;
    existingReflectionLink?: string;
    isEditing?: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    close: [];
    success: [];
}>();

const reflection = ref('');
const reflectionLink = ref('');
const reflectionError = ref('');
const isSubmitting = ref(false);

// Initialize reflection when modal opens
watch(
    () => props.isOpen,
    (newValue) => {
        if (newValue && props.existingReflection) {
            reflection.value = props.existingReflection;
        } else if (newValue) {
            reflection.value = '';
        }

        if (newValue && props.existingReflectionLink) {
            reflectionLink.value = props.existingReflectionLink;
        } else if (newValue) {
            reflectionLink.value = '';
        }
    },
);

const closeModal = () => {
    if (isSubmitting.value) return;

    reflection.value = '';
    reflectionLink.value = '';
    reflectionError.value = '';
    emit('close');
};

const submitReflection = async () => {
    // Clear previous errors
    reflectionError.value = '';

    // Enhanced client-side validation
    if (!reflection.value.trim()) {
        reflectionError.value = 'Please provide a reflection.';
        return;
    }

    if (reflection.value.trim().length < 50) {
        reflectionError.value = 'Please provide a reflection of at least 50 characters.';
        return;
    }

    // Validate reflection link if provided
    if (reflectionLink.value.trim()) {
        try {
            new URL(reflectionLink.value.trim());
        } catch {
            reflectionError.value = 'Please enter a valid URL (e.g., https://example.com) or leave the link field empty.';
            return;
        }
    }

    isSubmitting.value = true;

    try {
        const url = props.isEditing
            ? `/classes/${props.course?.id}/reflection`
            : `/classes/${props.course?.id}/complete`;

        await router.post(
            url,
            {
                reflection: reflection.value.trim(),
                reflection_link: reflectionLink.value.trim() || null,
            },
            {
                preserveScroll: false,
                onSuccess: () => {
                    reflection.value = '';
                    reflectionLink.value = '';
                    reflectionError.value = '';
                    emit('success');
                    emit('close');
                },
                onError: (errors) => {
                    if (errors.reflection) {
                        reflectionError.value = errors.reflection;
                    } else {
                        reflectionError.value =
                            'Failed to submit reflection. Please try again.';
                    }
                },
                onFinish: () => {
                    isSubmitting.value = false;
                },
            },
        );
    } catch {
        reflectionError.value =
            'Failed to submit reflection. Please try again.';
        isSubmitting.value = false;
    }
};
</script>
