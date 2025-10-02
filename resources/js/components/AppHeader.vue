<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Home, Award, Rss } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user as User | undefined);

const appName = import.meta.env.VITE_APP_NAME || 'One Lesson Up';

const navLinks = [
    {
        name: 'Home',
        href: '/',
        icon: Home,
    },
    {
        name: 'Classes',
        href: '/classes',
        icon: BookOpen,
    },
    {
        name: 'Feeds',
        href: '/feeds',
        icon: Rss,
    },
    {
        name: 'Leaderboard',
        href: '/leaderboard',
        icon: Award,
    },
];

const isActive = (href: string) => {
    const currentPath = page.url;
    if (href === '/') {
        return currentPath === '/';
    }
    return currentPath.startsWith(href);
};
</script>

<template>
    <header class="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div class="container mx-auto flex h-16 items-center justify-between px-4">
            <!-- Left: Logo and App Name -->
            <div class="flex items-center gap-2">
                <Link href="/" class="flex items-center gap-2 transition-opacity hover:opacity-80">
                    <AppLogo class="h-8 w-8" />
                    <span class="text-xl font-bold text-foreground">{{ appName }}</span>
                </Link>
            </div>

            <!-- Middle: Navigation Links -->
            <nav class="hidden md:flex items-center gap-1">
                <Link
                    v-for="link in navLinks"
                    :key="link.href"
                    :href="link.href"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors rounded-md"
                    :class="[
                        isActive(link.href)
                            ? 'bg-primary'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                    ]"
                >
                    <component :is="link.icon" class="h-4 w-4" />
                    {{ link.name }}
                </Link>
            </nav>

            <!-- Right: User Avatar and Menu -->
            <div v-if="user" class="flex items-center gap-2">
                <!-- Points Badge -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-secondary/20 border border-secondary/30">
                    <Award class="h-4 w-4 text-secondary-foreground" />
                    <span class="text-sm font-semibold text-secondary-foreground">{{ user.points || 0 }}</span>
                </div>

                <!-- User Dropdown Menu -->
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <Button
                            variant="ghost"
                            class="relative h-10 w-10 rounded-full"
                        >
                            <Avatar class="h-10 w-10 border-2 border-primary/20">
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.full_name"
                                />
                                <AvatarFallback class="bg-primary text-primary-foreground">
                                    {{ (user.full_name || 'U').charAt(0).toUpperCase() }}
                                </AvatarFallback>
                            </Avatar>
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent class="w-56" align="end">
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <!-- Right: Auth Links (for guests) -->
            <div v-else class="flex items-center gap-2">
                <Link href="/login">
                    <Button variant="ghost">Log in</Button>
                </Link>
                <Link href="/register">
                    <Button variant="default">Sign up</Button>
                </Link>
            </div>
        </div>

        <!-- Mobile Navigation (Bottom) -->
        <div class="md:hidden border-t bg-background">
            <nav class="flex items-center justify-around px-2 py-2">
                <Link
                    v-for="link in navLinks"
                    :key="link.href"
                    :href="link.href"
                    class="flex flex-col items-center gap-1 px-3 py-2 text-xs font-medium transition-colors rounded-md min-w-[60px]"
                    :class="[
                        isActive(link.href)
                            ? 'text-primary'
                            : 'text-muted-foreground'
                    ]"
                >
                    <component :is="link.icon" class="h-5 w-5" />
                    <span>{{ link.name }}</span>
                </Link>
            </nav>
        </div>
    </header>
</template>
