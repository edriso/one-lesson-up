<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Trophy, Medal, Award, Crown } from 'lucide-vue-next';

interface LeaderboardEntry {
  id: number;
  rank: number;
  user: {
    id: number;
    full_name: string;
    username: string;
    avatar?: string;
  };
  points: number;
  activities_count: number;
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
      return Medal;
    default:
      return Award;
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
    .map(name => name.charAt(0))
    .join('')
    .toUpperCase()
    .substring(0, 2);
};
</script>

<template>
  <div 
    class="flex items-center justify-between p-4 rounded-lg border transition-all duration-200"
    :class="[
      entry.rank <= 3 ? 'bg-gradient-to-r from-primary/10 to-secondary/10 border-primary/20' : 'bg-background border-border',
      isCurrentUser() ? 'ring-2 ring-primary/50 bg-primary/5' : ''
    ]">
    <div class="flex items-center gap-4">
      <div class="flex items-center justify-center w-8 h-8 rounded-full bg-primary/20">
        <component :is="getRankIcon(entry.rank)" class="h-4 w-4" :class="getRankColor(entry.rank)" />
      </div>
      <div class="flex items-center gap-3">
        <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center">
          <span class="text-sm font-semibold text-primary-foreground">
            {{ getUserInitials(entry.user?.full_name || '') }}
          </span>
        </div>
        <div>
          <h4 class="font-semibold text-foreground flex items-center gap-2">
            {{ entry.user?.full_name || 'Unknown User' }}
            <Badge v-if="isCurrentUser()" variant="outline" class="text-xs">You</Badge>
          </h4>
          <p class="text-sm text-muted-foreground">@{{ entry.user?.username || 'unknown' }}</p>
        </div>
      </div>
    </div>
    <div class="flex items-center gap-4">
      <div class="text-right">
        <p class="font-bold text-foreground">{{ entry.points }} pts</p>
        <p class="text-sm text-muted-foreground">{{ entry.activities_count }} activities</p>
      </div>
      <Badge v-if="entry.rank <= 3" variant="secondary" class="text-secondary-foreground">
        #{{ entry.rank }}
      </Badge>
    </div>
  </div>
</template>