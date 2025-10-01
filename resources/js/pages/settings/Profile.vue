<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { Trophy, Info } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

// Point thresholds
const PROFILE_PICTURE_POINTS = 5;
const canUploadAvatar = computed(() => user.points >= PROFILE_PICTURE_POINTS);
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

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <!-- Points Display -->
                    <Alert>
                        <Trophy class="h-4 w-4 text-primary" />
                        <AlertDescription>
                            You have <strong>{{ user.points }} points</strong>. Complete lessons to earn more!
                        </AlertDescription>
                    </Alert>

                    <!-- Full Name -->
                    <div class="grid gap-2">
                        <Label for="full_name">Name</Label>
                        <Input
                            id="full_name"
                            class="mt-1 block w-full"
                            name="full_name"
                            :default-value="user.full_name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.full_name" />
                    </div>

                    <!-- Email -->
                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
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
                            class="mt-1 block w-full"
                            name="title"
                            :default-value="user.title"
                            placeholder="e.g., Software Engineer, Student"
                        />
                        <InputError class="mt-2" :message="errors.title" />
                    </div>

                    <!-- Bio -->
                    <div class="grid gap-2">
                        <Label for="bio">Bio</Label>
                        <Textarea
                            id="bio"
                            class="mt-1 block w-full"
                            name="bio"
                            :default-value="user.bio"
                            placeholder="Tell us about yourself..."
                            :rows="4"
                        />
                        <InputError class="mt-2" :message="errors.bio" />
                    </div>

                    <!-- Avatar URL -->
                    <div class="grid gap-2">
                        <Label for="avatar">Profile Picture URL</Label>
                        <Input
                            id="avatar"
                            type="url"
                            class="mt-1 block w-full"
                            name="avatar"
                            :default-value="user.avatar"
                            :disabled="!canUploadAvatar"
                            placeholder="https://example.com/your-photo.jpg"
                        />
                        <Alert v-if="!canUploadAvatar" variant="destructive">
                            <Info class="h-4 w-4" />
                            <AlertDescription>
                                You need at least {{ PROFILE_PICTURE_POINTS }} points to set a profile picture. 
                                You currently have {{ user.points }} points.
                            </AlertDescription>
                        </Alert>
                        <p v-else class="text-xs text-muted-foreground">
                            Enter a direct URL to your profile picture
                        </p>
                        <InputError class="mt-2" :message="errors.avatar" />
                    </div>

                    <!-- LinkedIn URL -->
                    <div class="grid gap-2">
                        <Label for="linkedin_url">LinkedIn Profile</Label>
                        <Input
                            id="linkedin_url"
                            type="url"
                            class="mt-1 block w-full"
                            name="linkedin_url"
                            :default-value="user.linkedin_url"
                            placeholder="https://linkedin.com/in/yourprofile"
                        />
                        <InputError class="mt-2" :message="errors.linkedin_url" />
                    </div>

                    <!-- Website URL -->
                    <div class="grid gap-2">
                        <Label for="website_url">Personal Website</Label>
                        <Input
                            id="website_url"
                            type="url"
                            class="mt-1 block w-full"
                            name="website_url"
                            :default-value="user.website_url"
                            placeholder="https://yourwebsite.com"
                        />
                        <InputError class="mt-2" :message="errors.website_url" />
                    </div>

                    <!-- Public Profile Toggle -->
                    <div class="flex items-center justify-between rounded-lg border p-4">
                        <div class="space-y-0.5">
                            <Label for="is_public" class="text-base">Public Profile</Label>
                            <p class="text-sm text-muted-foreground">
                                Make your profile visible to other users
                            </p>
                        </div>
                        <Switch
                            id="is_public"
                            name="is_public"
                            :default-checked="user.is_public"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
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

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
