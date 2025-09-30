<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Button } from '@/components/ui/button';
import { ChevronUp } from 'lucide-vue-next';

const isVisible = ref(false);
const isScrolling = ref(false);

const checkScroll = () => {
  isVisible.value = window.scrollY > 300;
};

const scrollToTop = () => {
  isScrolling.value = true;
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
  
  // Reset scrolling state after animation completes
  setTimeout(() => {
    isScrolling.value = false;
  }, 500);
};

onMounted(() => {
  window.addEventListener('scroll', checkScroll);
});

onUnmounted(() => {
  window.removeEventListener('scroll', checkScroll);
});
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform scale-90 opacity-0 translate-y-2"
    enter-to-class="transform scale-100 opacity-100 translate-y-0"
    leave-active-class="transition duration-300 ease-in"
    leave-from-class="transform scale-100 opacity-100 translate-y-0"
    leave-to-class="transform scale-90 opacity-0 translate-y-2"
  >
    <Button
      v-if="isVisible"
      @click="scrollToTop"
      size="icon"
      :class="[
        'fixed bottom-6 right-6 z-50 h-12 w-12 rounded-full shadow-lg hover:shadow-xl transition-all duration-300',
        'bg-primary hover:bg-primary/90 text-primary-foreground',
        'focus:ring-2 focus:ring-primary focus:ring-offset-2',
        isScrolling ? 'scale-95' : 'scale-100'
      ]"
      aria-label="Back to top"
      title="Back to top"
    >
      <ChevronUp 
        :class="[
          'h-5 w-5 transition-transform duration-200',
          isScrolling ? 'scale-110' : 'scale-100'
        ]" 
      />
    </Button>
  </Transition>
</template>