<script setup lang="ts">
import { BadgeCheck } from 'lucide-vue-next';
import LeaderboardEntry from './LeaderboardEntry.vue';

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
    entries: LeaderboardEntry[];
    currentUserId?: number;
    emptyMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    emptyMessage: 'No activities yet',
});

const { entries, currentUserId, emptyMessage } = props;
</script>

<template>
    <div
        v-if="entries.length === 0"
        class="py-8 text-center text-muted-foreground"
    >
        <BadgeCheck class="mx-auto mb-4 h-12 w-12 opacity-50" />
        <p>{{ emptyMessage }}</p>
    </div>
    <div v-else class="space-y-3">
        <LeaderboardEntry
            v-for="entry in entries"
            :key="entry.id"
            :entry="entry"
            :current-user-id="currentUserId"
        />
    </div>
</template>
