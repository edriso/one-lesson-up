<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ArrowUp } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <ArrowUp :class="className" v-bind="$attrs" />
</template>
