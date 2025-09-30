<script setup lang="ts">
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle2, AlertCircle, Info, AlertTriangle } from 'lucide-vue-next';

interface Props {
  type: 'success' | 'error' | 'warning' | 'info';
  message: string;
  show?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  show: true,
});

const getIcon = () => {
  switch (props.type) {
    case 'success':
      return CheckCircle2;
    case 'error':
      return AlertCircle;
    case 'warning':
      return AlertTriangle;
    case 'info':
      return Info;
    default:
      return AlertCircle;
  }
};

const getVariant = () => {
  switch (props.type) {
    case 'error':
      return 'destructive';
    case 'warning':
      return 'default';
    default:
      return 'default';
  }
};

const getClasses = () => {
  switch (props.type) {
    case 'success':
      return 'border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200';
    case 'error':
      return ''; // Uses destructive variant
    case 'warning':
      return 'border-yellow-200 bg-yellow-50 text-yellow-800 dark:border-yellow-800 dark:bg-yellow-950 dark:text-yellow-200';
    case 'info':
      return 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200';
    default:
      return '';
  }
};
</script>

<template>
  <Alert 
    v-if="show && message" 
    :variant="getVariant()" 
    :class="getClasses()"
    class="mb-4"
  >
    <component :is="getIcon()" class="h-4 w-4" />
    <AlertDescription>{{ message }}</AlertDescription>
  </Alert>
</template>