<script setup lang="ts">
import LearningCalendar from '@/components/LearningCalendar.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Briefcase,
    Clock,
    ExternalLink,
    GraduationCap,
    Lock,
    Target,
    TrendingUp,
} from 'lucide-vue-next';

interface ProfileUser {
    id: number;
    full_name: string;
    username: string;
    bio?: string;
    title?: string;
    website_url?: string;
    avatar?: string;
    points: number;
    joined_at: string;
    is_public: boolean;
    week_starts_on?: number;
}

interface Activity {
    id: number;
    type: string;
    description: string;
    points_earned: number;
    created_at: string;
}

interface CompletedClass {
    id: number;
    title: string;
    completed_at: string;
    points_earned: number;
    lessons_count: number;
}

interface CalendarDay {
    date: string;
    activities_count: number;
    points_earned: number;
    lessons_completed: number;
}

interface Props {
    user?: ProfileUser;
    activities?: Activity[];
    completed_classes?: CompletedClass[];
    calendar_data?: CalendarDay[];
    stats?: {
        total_points: number;
        total_lessons_completed: number;
        total_classes_completed: number;
    };
    is_own_profile?: boolean;
}

withDefaults(defineProps<Props>(), {
    user: undefined,
    activities: () => [],
    completed_classes: () => [],
    calendar_data: () => [],
    stats: () => ({
        total_points: 0,
        total_lessons_completed: 0,
        total_classes_completed: 0,
    }),
    is_own_profile: false,
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
};

const formatShortDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const getActivityIcon = (type: string) => {
    switch (type) {
        case 'lesson_completed':
            return GraduationCap;
        case 'course_completed':
            return BadgeCheck;
        case 'course_started':
            return Target;
        default:
            return TrendingUp;
    }
};
</script>

<template>
    <Head :title="user ? `${user.full_name} (@${user.username})` : 'Profile'" />

    <AppLayout>
        <div v-if="!user" class="py-12 text-center">
            <p class="text-muted-foreground">User not found</p>
        </div>

        <div v-else class="space-y-6">
            <!-- Private Profile Message -->
            <Card
                v-if="!user.is_public && !is_own_profile"
                class="mb-8 border-muted bg-muted/50"
            >
                <CardContent class="p-6">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-muted"
                        >
                            <Lock class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-foreground">
                                This account is private
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                This user has chosen to keep their profile
                                private. Their activities and progress are not
                                visible to other users.
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Profile Header -->
            <Card v-else class="mb-8 border-primary/20">
                <CardContent class="p-8">
                    <div class="flex flex-col gap-6 md:flex-row">
                        <!-- Profile Picture -->
                        <div class="flex-shrink-0">
                            <div
                                class="flex h-24 w-24 items-center justify-center rounded-full bg-primary/20"
                            >
                                <img
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.full_name"
                                    class="h-24 w-24 rounded-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-2xl font-bold text-primary-foreground"
                                >
                                    {{ user.full_name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Profile Info -->
                        <div class="flex-1">
                            <div
                                class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between"
                            >
                                <div>
                                    <h1
                                        class="text-3xl font-bold text-foreground"
                                    >
                                        {{ user.full_name }}
                                    </h1>
                                    <p class="text-muted-foreground">
                                        @{{ user.username }}
                                    </p>
                                    <p
                                        v-if="user.bio"
                                        class="mt-2 text-foreground"
                                    >
                                        {{ user.bio }}
                                    </p>
                                    <div
                                        v-if="user.title"
                                        class="mt-2 flex items-center gap-2"
                                    >
                                        <Briefcase
                                            class="h-4 w-4 text-muted-foreground"
                                        />
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >{{ user.title }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Stats -->
                                <div class="flex flex-wrap gap-4">
                                    <div class="text-center">
                                        <div
                                            class="text-2xl font-bold text-primary"
                                        >
                                            {{ user.points }}
                                        </div>
                                        <div
                                            class="text-sm text-muted-foreground"
                                        >
                                            Points
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Social Links -->
                            <div class="mt-4 flex flex-wrap gap-4">
                                <Button
                                    v-if="user.website_url"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <a
                                        :href="user.website_url"
                                        target="_blank"
                                        class="flex items-center gap-2"
                                    >
                                        <ExternalLink class="h-4 w-4" />
                                        Website
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Tabs (only show for public profiles) -->
            <Tabs
                v-if="user.is_public || is_own_profile"
                default-value="overview"
                class="w-full"
            >
                <TabsList class="grid w-full grid-cols-4">
                    <TabsTrigger value="overview">Overview</TabsTrigger>
                    <TabsTrigger value="calendar">Calendar</TabsTrigger>
                    <TabsTrigger value="classes">Classes</TabsTrigger>
                    <TabsTrigger value="activities">Activities</TabsTrigger>
                </TabsList>

                <!-- Overview Tab -->
                <TabsContent value="overview" class="mt-6">
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <!-- Stats Cards -->
                        <div class="space-y-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle class="flex items-center gap-2">
                                        Learning Stats
                                    </CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-4">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Total Points</span
                                        >
                                        <span class="font-semibold">{{
                                            stats.total_points
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Lessons Completed</span
                                        >
                                        <span class="font-semibold">{{
                                            stats.total_lessons_completed
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            class="text-sm text-muted-foreground"
                                            >Classes Completed</span
                                        >
                                        <span class="font-semibold">{{
                                            stats.total_classes_completed
                                        }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Recent Activities -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <TrendingUp class="h-5 w-5 text-primary" />
                                    Recent Activities
                                </CardTitle>
                                <CardDescription>
                                    Latest learning progress
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div
                                    v-if="activities.length === 0"
                                    class="py-4 text-center text-muted-foreground"
                                >
                                    <p class="text-sm">No recent activities</p>
                                </div>
                                <div v-else class="space-y-3">
                                    <div
                                        v-for="activity in activities.slice(
                                            0,
                                            5,
                                        )"
                                        :key="activity.id"
                                        class="flex items-start gap-3 rounded-lg bg-muted/30 p-3"
                                    >
                                        <component
                                            :is="getActivityIcon(activity.type)"
                                            class="mt-0.5 h-4 w-4 flex-shrink-0 text-primary"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="text-sm font-medium text-foreground"
                                            >
                                                {{ activity.description }}
                                            </p>
                                            <div
                                                class="mt-1 flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-xs text-muted-foreground"
                                                    >{{
                                                        formatShortDate(
                                                            activity.created_at,
                                                        )
                                                    }}</span
                                                >
                                                <span
                                                    v-if="
                                                        activity.points_earned >
                                                        0
                                                    "
                                                    class="text-xs font-medium text-secondary"
                                                >
                                                    +{{
                                                        activity.points_earned
                                                    }}
                                                    pts
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </TabsContent>

                <!-- Calendar Tab -->
                <TabsContent value="calendar" class="mt-6">
                    <LearningCalendar
                        :calendar_data="calendar_data"
                        :week_starts_on="user?.week_starts_on || 0"
                    />
                </TabsContent>

                <!-- Classes Tab -->
                <TabsContent value="classes" class="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <GraduationCap class="h-5 w-5 text-primary" />
                                Completed Classes
                            </CardTitle>
                            <CardDescription>
                                Classes you've successfully completed
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="completed_classes.length === 0"
                                class="py-8 text-center text-muted-foreground"
                            >
                                <GraduationCap
                                    class="mx-auto mb-4 h-12 w-12 opacity-50"
                                />
                                <p>No completed classes yet</p>
                                <p class="text-sm">
                                    Start learning to see your progress here!
                                </p>
                            </div>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="classItem in completed_classes"
                                    :key="classItem.id"
                                    class="flex items-center justify-between rounded-lg border bg-background p-4"
                                >
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/20"
                                        >
                                            <GraduationCap
                                                class="h-6 w-6 text-primary"
                                            />
                                        </div>
                                        <div>
                                            <h4
                                                class="font-semibold text-foreground"
                                            >
                                                {{ classItem.title }}
                                            </h4>
                                            <p
                                                class="text-sm text-muted-foreground"
                                            >
                                                Completed
                                                {{
                                                    formatDate(
                                                        classItem.completed_at,
                                                    )
                                                }}
                                                • {{ classItem.lessons_count }}
                                                {{
                                                    classItem.lessons_count ===
                                                    1
                                                        ? 'lesson'
                                                        : 'lessons'
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <div class="text-right">
                                            <p
                                                class="font-bold text-foreground"
                                            >
                                                {{ classItem.points_earned }}
                                                pts
                                            </p>
                                            <Badge
                                                variant="secondary"
                                                class="text-secondary-foreground"
                                            >
                                                Completed
                                            </Badge>
                                        </div>
                                        <Button variant="outline" size="sm">
                                            View Class
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Activities Tab -->
                <TabsContent value="activities" class="mt-6">
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Clock class="h-5 w-5 text-primary" />
                                All Activities
                            </CardTitle>
                            <CardDescription>
                                Complete history of your learning activities
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="activities.length === 0"
                                class="py-8 text-center text-muted-foreground"
                            >
                                <Clock
                                    class="mx-auto mb-4 h-12 w-12 opacity-50"
                                />
                                <p>No activities yet</p>
                                <p class="text-sm">
                                    Start learning to see your activities here!
                                </p>
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="activity in activities"
                                    :key="activity.id"
                                    class="flex items-start gap-3 rounded-lg border bg-background p-4"
                                >
                                    <component
                                        :is="getActivityIcon(activity.type)"
                                        class="mt-0.5 h-5 w-5 flex-shrink-0 text-primary"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="font-medium text-foreground">
                                            {{ activity.description }}
                                        </p>
                                        <div
                                            class="mt-2 flex items-center gap-4"
                                        >
                                            <span
                                                class="text-sm text-muted-foreground"
                                                >{{
                                                    formatDate(
                                                        activity.created_at,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                v-if="
                                                    activity.points_earned > 0
                                                "
                                                class="text-sm font-medium text-secondary"
                                            >
                                                +{{ activity.points_earned }}
                                                points
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
