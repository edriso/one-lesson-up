<script setup lang="ts">
import { ArrowBigUp } from 'lucide-vue-next';
import type { HTMLAttributes } from 'vue';

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
