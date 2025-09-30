<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, inject } from 'vue';

interface Props {
  value: string;
  disabled?: boolean;
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  disabled: false,
});

const emit = defineEmits<{
  click: [event: MouseEvent];
}>();

const tabs = inject('tabs');

const isActive = computed(() => tabs?.activeTab.value === props.value);

const triggerClass = computed(() => 
  cn(
    'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
    isActive.value 
      ? 'bg-background text-foreground shadow' 
      : 'text-muted-foreground hover:text-foreground',
    props.class
  )
);

const handleClick = (event: MouseEvent) => {
  if (!props.disabled) {
    tabs?.setActiveTab(props.value);
    emit('click', event);
  }
};
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    :class="triggerClass"
    @click="handleClick"
  >
    <slot />
  </button>
</template>
