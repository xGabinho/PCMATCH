<template>
  <Teleport to="body">
    <div class="fixed top-6 right-6 z-[999999] flex flex-col gap-3 pointer-events-none items-end max-w-sm w-full px-4 sm:px-0">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-start gap-3.5 px-4 py-3.5 rounded-xl shadow-2xl backdrop-blur-xl border text-sm font-medium w-full transition-all duration-300 animate-slide-up"
          :class="[
            toast.type === 'success' 
              ? 'bg-[#0f1f17]/95 border-emerald-500/50 text-emerald-300 shadow-emerald-950/50 ring-1 ring-emerald-500/20' 
              : '',
            toast.type === 'error' 
              ? 'bg-[#241115]/95 border-rose-500/50 text-rose-300 shadow-rose-950/50 ring-1 ring-rose-500/20' 
              : '',
            toast.type === 'info' 
              ? 'bg-[#0f1b2b]/95 border-blue-500/50 text-blue-300 shadow-blue-950/50 ring-1 ring-blue-500/20' 
              : ''
          ]"
        >
          <div class="flex-shrink-0 mt-0.5 p-1 rounded-lg"
            :class="[
              toast.type === 'success' ? 'bg-emerald-500/20 text-emerald-400' : '',
              toast.type === 'error' ? 'bg-rose-500/20 text-rose-400' : '',
              toast.type === 'info' ? 'bg-blue-500/20 text-blue-400' : ''
            ]">
            <Check v-if="toast.type === 'success'" class="w-4 h-4 stroke-[2.5]" />
            <AlertCircle v-else-if="toast.type === 'error'" class="w-4 h-4 stroke-[2.5]" />
            <Info v-else class="w-4 h-4 stroke-[2.5]" />
          </div>
          <div class="flex-1 min-w-0 pt-0.5">
            <p class="font-semibold text-xs uppercase tracking-wider opacity-70 mb-0.5">
              {{ toast.type === 'success' ? 'Éxito' : toast.type === 'error' ? 'Error' : 'Aviso' }}
            </p>
            <p class="leading-snug text-white font-medium text-sm break-words">{{ toast.message }}</p>
          </div>
          <button @click="removeToast(toast.id)" class="opacity-60 hover:opacity-100 transition-opacity p-1 text-white hover:bg-white/10 rounded-lg flex items-center justify-center flex-shrink-0">
            <X class="w-4 h-4" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { Check, AlertCircle, Info, X } from 'lucide-vue-next';
import { useToast } from '../composables/useToast'

const { toasts, removeToast } = useToast()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(40px) scale(0.95);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(40px) scale(0.95);
}
</style>
