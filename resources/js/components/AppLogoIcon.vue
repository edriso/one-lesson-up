<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { ArrowBigUp } from 'lucide-vue-next';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <ArrowBigUp :class="className" v-bind="$attrs" />
</template>
