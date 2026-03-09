<template>
  <button
    :class="[
      'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition-all duration-200 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed',
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

const variantClasses = computed(() => ({
  primary: 'bg-accent text-white hover:bg-blue-500 hover:shadow-lg hover:shadow-blue-500/20',
  secondary: 'border border-dark-border text-text-primary hover:border-accent hover:text-accent',
  ghost: 'text-text-muted hover:text-text-primary hover:bg-dark-card',
  danger: 'bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20',
}[props.variant]))
</script>