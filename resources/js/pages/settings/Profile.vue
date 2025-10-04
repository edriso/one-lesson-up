<script setup lang="ts">
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { router, Head, Link, usePage } from '@inertiajs/vue3';

// import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import TimezonePicker from '@/components/TimezonePicker.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Info } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    user: {
        username: string;
        full_name: string;
        email: string;
        email_verified_at: string | null;
        bio: string | null;
        website_url: string | null;
        avatar: string | null;
        points: number;
        title: string | null;
        timezone: string;
        timezone_updated_at: string;
        can_update_timezone: boolean;
    };
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const authUser = page.props.auth.user;

// Form data - initialize with user data
const username = ref(props.user.username || '');
const fullName = ref(props.user.full_name || '');
const email = ref(props.user.email || '');
const bio = ref(props.user.bio || '');
const websiteUrl = ref(props.user.website_url || '');
const avatar = ref(props.user.avatar || '');
const title = ref(props.user.title || '');

// Bio character counter
const bioCharCount = ref(bio.value.length);

// Watch bio changes to update character count
watch(bio, (newBio) => {
    bioCharCount.value = newBio.length;
});

// Point thresholds
const PROFILE_PICTURE_POINTS = 100;
const canUploadAvatar = computed(
    () => (authUser.points || 0) >= PROFILE_PICTURE_POINTS,
);

// Profile visibility
const isPublic = ref(authUser.is_public || false);

// Timezone
const timezone = ref(props.user.timezone || 'UTC');
const canUpdateTimezone = ref(props.user.can_update_timezone ?? true);
const timezoneUpdatedAt = ref(props.user.timezone_updated_at);

// Client-side validation
const clientErrors = ref<Record<string, string>>({});
const recentlySuccessful = ref(false);

const validateForm = (formData: any) => {
    const errors: Record<string, string> = {};

    // Username validation
    const username = formData.username?.trim() || '';
    if (!username || username.length < 3) {
        errors.username = 'Username must be at least 3 characters long.';
    } else if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        errors.username = 'Username can only contain letters, numbers, and underscores.';
    }

    // Full name validation
    const fullName = formData.full_name?.trim() || '';
    if (!fullName || fullName.length < 2) {
        errors.full_name = 'Full name must be at least 2 characters long.';
    }

    // Bio validation (if provided)
    const bio = formData.bio?.trim() || '';
    if (bio && bio.length > 255) {
        errors.bio = 'Bio must be 255 characters or less.';
    }

    // Website URL validation (if provided)
    const websiteUrl = formData.website_url?.trim() || '';
    if (websiteUrl) {
        try {
            new URL(websiteUrl);
        } catch {
            errors.website_url = 'Please enter a valid URL (e.g., https://example.com).';
        }
    }

    // Avatar URL validation (if provided)
    const avatar = formData.avatar?.trim() || '';
    if (avatar) {
        try {
            new URL(avatar);
        } catch {
            errors.avatar = 'Please enter a valid URL (e.g., https://example.com/image.jpg).';
        }
    }

    return errors;
};

const handleSubmit = (event: Event) => {
    // Clear previous errors
    clientErrors.value = {};

    // Get form data from reactive variables
    const formData = {
        username: username.value,
        full_name: fullName.value,
        email: email.value,
        bio: bio.value,
        website_url: websiteUrl.value,
        avatar: avatar.value,
        title: title.value,
        is_public: isPublic.value,
        timezone: timezone.value,
    };

    // Validate form data
    const errors = validateForm(formData);
    
    if (Object.keys(errors).length > 0) {
        clientErrors.value = errors;
        event.preventDefault(); // Prevent form submission
        return false;
    }

    // If validation passes, submit the form using Inertia
    router.patch('/settings/profile', formData, {
        preserveScroll: true,
        onSuccess: () => {
            // Form submitted successfully
            recentlySuccessful.value = true;
            setTimeout(() => {
                recentlySuccessful.value = false;
            }, 3000); // Hide after 3 seconds
        },
        onError: (errors) => {
            // Handle server-side errors if needed
            console.error('Form submission errors:', errors);
        }
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your profile details and settings"
                />

                <form
                    @submit.prevent="handleSubmit"
                    class="space-y-6"
                >
                    <!-- Hidden timezone field -->
                    <input type="hidden" name="timezone" :value="timezone" />

                    <!-- Username -->
                    <div class="grid gap-2">
                        <Label for="username">Username</Label>
                        <Input
                            id="username"
                            v-model="username"
                            class="mt-1 block w-full"
                            name="username"
                            required
                            autocomplete="username"
                            placeholder="Username"
                        />
                        <InputError class="mt-2" :message="clientErrors.username" />
                    </div>

                    <!-- Full Name -->
                    <div class="grid gap-2">
                        <Label for="full_name">Name</Label>
                        <Input
                            id="full_name"
                            v-model="fullName"
                            class="mt-1 block w-full"
                            name="full_name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="clientErrors.full_name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            v-model="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            required
                            autocomplete="email"
                            placeholder="email@example.com"
                        />
                        <InputError class="mt-2" :message="clientErrors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <!-- Title -->
                    <div class="grid gap-2">
                        <Label for="title">Title / Job Role</Label>
                        <Input
                            id="title"
                            v-model="title"
                            class="mt-1 block w-full"
                            name="title"
                            placeholder="e.g., Software Engineer, Student"
                        />
                        <InputError class="mt-2" :message="clientErrors.title" />
                    </div>

                    <!-- Bio -->
                    <div class="grid gap-2">
                        <Label for="bio">Bio</Label>
                        <Textarea
                            id="bio"
                            v-model="bio"
                            class="mt-1 block w-full"
                            name="bio"
                            @input="bioCharCount = bio.length"
                            placeholder="Tell us about yourself..."
                            :rows="4"
                            maxlength="255"
                        />
                        <p
                            class="text-xs text-muted-foreground"
                            :class="{ 'text-red-500': bioCharCount > 255 }"
                        >
                            {{ bioCharCount }}/255 characters
                        </p>
                        <InputError class="mt-2" :message="clientErrors.bio" />
                    </div>

                    <!-- Website URL -->
                    <div class="grid gap-2">
                        <Label for="website_url">Personal Website</Label>
                        <Input
                            id="website_url"
                            v-model="websiteUrl"
                            type="url"
                            class="mt-1 block w-full"
                            name="website_url"
                            placeholder="https://yourwebsite.com"
                        />
                        <InputError
                            class="mt-2"
                            :message="clientErrors.website_url"
                        />
                    </div>

                    <!-- Timezone -->
                    <TimezonePicker
                        v-model="timezone"
                        :can-update-timezone="canUpdateTimezone"
                        :timezone-updated-at="timezoneUpdatedAt"
                        :errors="clientErrors"
                    />

                    <!-- Avatar URL -->
                    <div class="grid gap-2">
                        <Label for="avatar">Profile Picture URL</Label>
                        <Input
                            id="avatar"
                            v-model="avatar"
                            type="url"
                            class="mt-1 block w-full"
                            name="avatar"
                            :disabled="!canUploadAvatar"
                            placeholder="https://example.com/your-photo.jpg"
                        />
                        <Alert v-if="!canUploadAvatar" variant="destructive">
                            <Info class="h-4 w-4" />
                            <AlertDescription>
                                You need at least
                                {{ PROFILE_PICTURE_POINTS }} points to set a
                                profile picture. You currently have
                                {{ user.points }} points.
                            </AlertDescription>
                        </Alert>
                        <p v-else class="text-xs text-muted-foreground">
                            Enter a direct URL to your profile picture
                        </p>
                        <InputError class="mt-2" :message="clientErrors.avatar" />
                    </div>

                    <!-- Privacy Settings -->
                    <div class="grid gap-2">
                        <Label for="is_public">Profile Visibility</Label>
                        <div class="flex items-center space-x-2">
                            <Switch id="is_public" v-model:checked="isPublic" />
                            <input
                                type="hidden"
                                name="is_public"
                                :value="isPublic ? '1' : '0'"
                            />
                            <Label for="is_public" class="text-sm">
                                Make my profile public
                            </Label>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            When enabled, your profile and activities will be
                            visible to other users. When disabled, your profile
                            will show as private to other users.
                        </p>
                        <InputError class="mt-2" :message="clientErrors.is_public" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            data-test="update-profile-button"
                            @click="handleSubmit"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <!-- <DeleteUser /> -->
        </SettingsLayout>
    </AppLayout>
</template>
