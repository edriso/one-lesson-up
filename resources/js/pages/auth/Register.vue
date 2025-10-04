<script setup lang="ts">
import RegisteredUserController from '@/actions/App/Http/Controllers/Auth/RegisteredUserController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import TimezonePicker from '@/components/TimezonePicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

// Timezone detection
const detectedTimezone = ref('UTC');

// Client-side validation
const clientErrors = ref<Record<string, string>>({});

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

    // Email validation
    const email = formData.email?.trim() || '';
    if (!email) {
        errors.email = 'Email address is required.';
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        errors.email = 'Please enter a valid email address.';
    }

    // Password validation
    if (!formData.password || formData.password.length < 8) {
        errors.password = 'Password must be at least 8 characters long.';
    }

    // Password confirmation validation
    if (!formData.password_confirmation) {
        errors.password_confirmation = 'Please confirm your password.';
    } else if (formData.password !== formData.password_confirmation) {
        errors.password_confirmation = 'Passwords do not match.';
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

    return errors;
};

const handleSubmit = (event: Event) => {
    // Clear previous errors
    clientErrors.value = {};

    // Get form data from the form state
    const formData = {
        username: (document.querySelector('input[name="username"]') as HTMLInputElement)?.value || '',
        full_name: (document.querySelector('input[name="full_name"]') as HTMLInputElement)?.value || '',
        email: (document.querySelector('input[name="email"]') as HTMLInputElement)?.value || '',
        password: (document.querySelector('input[name="password"]') as HTMLInputElement)?.value || '',
        password_confirmation: (document.querySelector('input[name="password_confirmation"]') as HTMLInputElement)?.value || '',
        bio: (document.querySelector('textarea[name="bio"]') as HTMLTextAreaElement)?.value || '',
        website_url: (document.querySelector('input[name="website_url"]') as HTMLInputElement)?.value || '',
    };

    // Validate form data
    const errors = validateForm(formData);
    
    if (Object.keys(errors).length > 0) {
        clientErrors.value = errors;
        event.preventDefault(); // Prevent form submission
        return false;
    }

    // If validation passes, allow form submission
    return true;
};

onMounted(() => {
    // Auto-detect user's timezone
    try {
        const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        detectedTimezone.value = userTimezone;
    } catch {
        console.warn('Could not detect timezone, using UTC');
        detectedTimezone.value = 'UTC';
    }
});
</script>

<template>
    <AuthBase
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <Form
            v-bind="RegisteredUserController.store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <!-- Hidden timezone field -->
            <input type="hidden" name="timezone" :value="detectedTimezone" />
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="username">Username</Label>
                    <Input
                        id="username"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        name="username"
                        placeholder="Username"
                    />
                    <InputError :message="clientErrors.username || errors.username" />
                </div>

                <div class="grid gap-2">
                    <Label for="full_name">Name</Label>
                    <Input
                        id="full_name"
                        type="text"
                        required
                        :tabindex="2"
                        autocomplete="name"
                        name="full_name"
                        placeholder="Full name"
                    />
                    <InputError :message="clientErrors.full_name || errors.full_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="3"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="clientErrors.email || errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                    />
                    <InputError :message="clientErrors.password || errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                    />
                    <InputError :message="clientErrors.password_confirmation || errors.password_confirmation" />
                </div>

                <TimezonePicker v-model="detectedTimezone" :errors="errors" />

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="6"
                    :disabled="processing"
                    data-test="register-user-button"
                    @click="handleSubmit"
                >
                    <LoaderCircle
                        v-if="processing"
                        class="h-4 w-4 animate-spin"
                    />
                    Create account
                </Button>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="underline underline-offset-4"
                    :tabindex="7"
                    >Log in</TextLink
                >
            </div>
        </Form>
    </AuthBase>
</template>
