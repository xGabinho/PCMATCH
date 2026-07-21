<template>
  <div class="fixed bottom-6 left-6 right-6 sm:bottom-auto sm:top-6 sm:left-auto sm:right-6 z-[9999] flex flex-col gap-3 pointer-events-none items-center sm:items-end">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3 px-4 py-3.5 rounded-xl shadow-2xl backdrop-blur-md border text-sm font-medium w-full sm:w-80 max-w-sm transition-colors duration-200"
        :class="[
          toast.type === 'success' ? 'bg-green-500/10 border-green-500/20 text-green-600 dark:text-green-400 theme-bg' : '',
          toast.type === 'error' ? 'bg-red-500/10 border-red-500/20 text-red-600 dark:text-red-400 theme-bg' : '',
          toast.type === 'info' ? 'bg-blue-500/10 border-blue-500/20 text-blue-600 dark:text-blue-400 theme-bg' : '',
          isDark ? 'shadow-black/50' : 'shadow-gray-200'
        ]"
      >
        <div class="flex-shrink-0 text-lg mt-0.5">
          <Check v-if="toast.type === 'success'" class="w-5 h-5" />
          <AlertCircle v-else-if="toast.type === 'error'" class="w-5 h-5" />
          <Info v-else class="w-5 h-5" />
        </div>
        <p class="flex-1 leading-snug pt-0.5 theme-text">{{ toast.message }}</p>
        <button @click="removeToast(toast.id)" class="opacity-50 hover:opacity-100 transition-opacity p-2 -mt-2 -mr-2 theme-text min-h-[44px] min-w-[44px] flex items-center justify-center">
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { Check, AlertCircle, Info } from 'lucide-vue-next';

import { useToast } from '../composables/useToast'
import { useTheme } from '../composables/useTheme'


const { toasts, removeToast } = useToast()
const { isDark } = useTheme()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
@media (min-width: 640px) {
  .toast-enter-from {
    transform: translateX(100%) scale(0.9);
  }
}
.toast-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
