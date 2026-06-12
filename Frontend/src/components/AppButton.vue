<template>
  <button
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed min-h-[44px] focus-visible:ring-2 focus-visible:ring-accent focus-visible:outline-none',
      sizeClasses,
      variantClasses
    ]"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: v => ['primary', 'secondary', 'ghost', 'danger'].includes(v)
  },
  size: {
    type: String,
    default: 'md',
    validator: v => ['sm', 'md', 'lg'].includes(v)
  }
})

const sizeClasses = computed(() => ({
  sm: 'px-4 py-2 text-sm',
  md: 'px-6 py-3 text-sm',
  lg: 'px-8 py-4 text-base',
}[props.size]))

// Theme-aware variant classes
const variantClasses = computed(() => ({
  primary: 'bg-accent text-white hover:bg-blue-500 hover:shadow-lg hover:shadow-blue-500/20',
  secondary: 'border theme-border theme-text hover:border-accent hover:text-accent bg-transparent',
  ghost: 'theme-text-muted hover:theme-text hover:theme-card bg-transparent',
  danger: 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20 hover:bg-red-100 dark:hover:bg-red-500/20',
}[props.variant]))
</script>