<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Link } from '@inertiajs/vue3';
import { Award, Crown, Trophy } from 'lucide-vue-next';

interface LeaderboardEntry {
    id: number;
    rank: number;
    user: {
        id: number;
        full_name: string;
        username: string;
        avatar?: string;
    };
    points?: number;
    lessons_completed?: number;
}

interface Props {
    entry: LeaderboardEntry;
    currentUserId?: number;
}

const props = defineProps<Props>();

const getRankIcon = (rank: number) => {
    switch (rank) {
        case 1:
            return Crown;
        case 2:
            return Trophy;
        case 3:
            return Award;
        default:
            return null; // No icon for ranks 4+
    }
};

const getRankColor = (rank: number) => {
    switch (rank) {
        case 1:
            return 'text-yellow-500';
        case 2:
            return 'text-gray-400';
        case 3:
            return 'text-amber-600';
        default:
            return 'text-muted-foreground';
    }
};

const isCurrentUser = () => {
    return props.currentUserId === props.entry.user.id;
};

const getUserInitials = (fullName: string) => {
    if (!fullName) return '?';
    return fullName
        .split(' ')
        .map((name) => name.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2);
};
</script>

<template>
    <div
        class="flex items-center justify-between rounded-lg border p-4 transition-all duration-200"
        :class="[
            entry.rank <= 3
                ? 'border-primary/20 bg-gradient-to-r from-primary/10 to-secondary/10'
                : 'border-border bg-background',
            isCurrentUser() ? 'bg-primary/5 ring-2 ring-primary/50' : '',
        ]"
    >
        <div class="flex items-center gap-4">
            <div
                class="flex h-8 w-8 items-center justify-center rounded-full bg-muted"
            >
                <span class="text-sm font-bold text-muted-foreground">
                    {{ entry.rank }}
                </span>
            </div>
            <Link
                :href="`/profile/${entry.user?.username || 'unknown'}`"
                class="flex cursor-pointer items-center gap-3 transition-opacity hover:opacity-80"
            >
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/20"
                >
                    <span class="text-sm font-semibold text-primary-foreground">
                        {{ getUserInitials(entry.user?.full_name || entry.user?.username || '') }}
                    </span>
                </div>
                <div>
                    <h4
                        class="flex items-center gap-2 font-semibold text-foreground"
                    >
                        {{ entry.user?.full_name || entry.user?.username }}
                        <component
                            v-if="getRankIcon(entry.rank)"
                            :is="getRankIcon(entry.rank)"
                            class="h-4 w-4"
                            :class="getRankColor(entry.rank)"
                        />
                        <Badge
                            v-if="isCurrentUser()"
                            variant="outline"
                            class="text-xs"
                            >You</Badge
                        >
                    </h4>
                </div>
            </Link>
        </div>
        <div class="flex items-center gap-4">
            <div class="text-right">
                <!-- Show points for monthly/overall leaderboards -->
                <p
                    v-if="entry.points !== undefined"
                    class="font-bold text-foreground"
                >
                    {{ entry.points }} pts
                </p>
                <!-- Show lessons completed for daily leaderboards -->
                <p
                    v-else-if="entry.lessons_completed !== undefined"
                    class="font-bold text-foreground"
                >
                    {{ entry.lessons_completed }}
                    {{ entry.lessons_completed === 1 ? 'lesson' : 'lessons' }}
                </p>

            </div>
        </div>
    </div>
</template>
