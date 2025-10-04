<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import { GraduationCap, Medal, Target, Gamepad2, Users } from 'lucide-vue-next';

interface Props {
    stats?: {
        active_learners: number;
        total_classes: number;
        lessons_completed: number;
    };
}

withDefaults(defineProps<Props>(), {
    stats: () => ({
        active_learners: 0,
        total_classes: 0,
        lessons_completed: 0,
    }),
});

// Get app name from environment variable
const appName = import.meta.env.VITE_APP_NAME || 'One Lesson Up';

// Get current year dynamically
const currentYear = new Date().getFullYear();

// Format numbers with K, M suffixes
const formatNumber = (num: number): string => {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M+';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K+';
    }
    return num.toString() + '+';
};
</script>

<template>
    <Head title="Welcome to One Lesson Up" />

    <div
        class="min-h-screen bg-gradient-to-br from-primary/10 via-background to-secondary/10"
    >
        <!-- Navigation -->
        <nav
            class="border-b border-border/40 bg-background/80 backdrop-blur-sm"
        >
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <GraduationCap class="h-8 w-8 text-primary" />
                        <h1 class="text-2xl font-bold text-foreground">
                            {{ appName }}
                        </h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <Link :href="login()">
                            <Button variant="outline">Log in</Button>
                        </Link>
                        <Link :href="register()">
                            <Button
                                variant="default"
                                class="bg-primary text-primary-foreground hover:bg-primary/90"
                            >
                                Get Started
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="container mx-auto px-4 py-16">
            <div class="mx-auto max-w-4xl text-center">
                <h2 class="mb-6 text-4xl font-bold text-foreground md:text-5xl">
                    Turn Learning Into a Game.<br />
                    <span class="text-primary">Level up your learning.</span>
                </h2>
                <p class="mx-auto mb-8 max-w-2xl text-lg text-muted-foreground">
                    Whether it's Udemy courses, YouTube series, bootcamps, or any structured learning path - turn it into a game and actually finish what you start.
                </p>

                <div
                    class="flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Link :href="register()">
                        <Button
                            size="lg"
                            class="bg-primary px-8 py-4 text-lg text-primary-foreground hover:bg-primary/90"
                        >
                            <Gamepad2 class="h-5 w-5" />
                            Start Learning Free
                        </Button>
                    </Link>
                    <Link :href="login()">
                        <Button
                            variant="outline"
                            size="lg"
                            class="px-8 py-4 text-lg"
                        >
                            Sign In
                        </Button>
                    </Link>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="container mx-auto px-4 py-12">
            <div
                class="mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3"
            >
                <!-- Feature 1 -->
                <div class="p-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Target class="h-8 w-8 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-foreground">
                        Stay Focused
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        One learning path at a time. No more scattered learning or abandoned courses, classes, or roadmaps.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-secondary/10"
                    >
                        <Medal class="h-8 w-8 text-secondary-foreground" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-foreground">
                        Gamified Learning
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Earn points for every lesson, get bonuses for consistent learning, and compete on leaderboards.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-6 text-center">
                    <div
                        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-primary/10"
                    >
                        <Users class="h-8 w-8 text-primary" />
                    </div>
                    <h3 class="mb-2 text-lg font-semibold text-foreground">
                        Community Support
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Learn alongside others, see what they're studying, and stay motivated together.
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="container mx-auto px-4 py-12">
            <div
                class="mx-auto max-w-3xl rounded-xl bg-gradient-to-r from-primary/10 to-secondary/10 p-8 text-center"
            >
                <h3 class="mb-6 text-2xl font-bold text-foreground">
                    Join the Learning Community
                </h3>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <div class="mb-1 text-3xl font-bold text-primary">
                            {{ formatNumber(stats.active_learners) }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Active Learners
                        </div>
                    </div>
                    <div>
                        <div class="mb-1 text-3xl font-bold text-primary">
                            {{ formatNumber(stats.total_classes) }}
                        </div>
                        <div class="text-sm text-muted-foreground">Classes</div>
                    </div>
                    <div>
                        <div class="mb-1 text-3xl font-bold text-primary">
                            {{ formatNumber(stats.lessons_completed) }}
                        </div>
                        <div class="text-sm text-muted-foreground">
                            Lessons Completed
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="container mx-auto px-4 py-12">
            <div class="mx-auto max-w-2xl text-center">
                <h3 class="mb-4 text-2xl font-bold text-foreground">
                    Ready to Actually Finish Your Learning?
                </h3>
                <p class="mb-6 text-muted-foreground">
                    Join thousands of learners who are finally completing their courses, classes, and learning roadmaps.
                </p>
                <Link :href="register()">
                    <Button
                        size="lg"
                        class="bg-primary px-8 py-4 text-lg text-primary-foreground hover:bg-primary/90"
                    >
                        <Gamepad2 class="h-5 w-5" />
                        Start Learning Free
                    </Button>
                </Link>
            </div>
        </div>

        <!-- Footer -->
        <footer
            class="mt-12 border-t border-border/40 bg-background/80 backdrop-blur-sm"
        >
            <div class="container mx-auto px-4 py-6">
                <div class="text-center text-muted-foreground">
                    <p>
                        &copy; {{ currentYear }} {{ appName }}. All rights
                        reserved.
                    </p>
                </div>
            </div>
        </footer>
    </div>
</template>
