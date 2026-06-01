<template>
  <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-3 pointer-events-none">
    <TransitionGroup name="toast">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="pointer-events-auto flex items-start gap-3 px-4 py-3.5 rounded-xl shadow-2xl backdrop-blur-md border text-sm font-medium w-80 max-w-[calc(100vw-2rem)]"
        :class="{
          'bg-green-500/10 border-green-500/20 text-green-400': toast.type === 'success',
          'bg-red-500/10 border-red-500/20 text-red-400': toast.type === 'error',
          'bg-blue-500/10 border-blue-500/20 text-blue-400': toast.type === 'info',
        }"
      >
        <span class="text-xl leading-none mt-0.5">
          {{ toast.type === 'success' ? '✅' : toast.type === 'error' ? '🚫' : 'ℹ️' }}
        </span>
        <p class="flex-1 leading-snug pt-0.5">{{ toast.message }}</p>
        <button @click="removeToast(toast.id)" class="opacity-50 hover:opacity-100 transition-opacity p-1 -mt-1 -mr-1">
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup>
import { useToast } from '../composables/useToast'
const { toasts, removeToast } = useToast()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
</style>
