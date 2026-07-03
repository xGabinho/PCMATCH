<template>
  <div
    class="card-dark card-hover flex flex-col cursor-pointer overflow-hidden"
    :class="{ 'border-accent shadow-lg shadow-accent/10 dark:shadow-accent/20': selected }"
  >
    <!-- Image band -->
    <div class="relative w-full h-48 theme-bg flex items-center justify-center overflow-hidden flex-shrink-0">
      <img
        v-if="image"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain mix-blend-multiply dark:mix-blend-normal drop-shadow-sm"
      />
      <div v-else class="flex flex-col items-center gap-2 opacity-20">
        <span class="text-4xl">{{ categoryIcon }}</span>
      </div>
      <!-- Tier badge -->
      <span
        v-if="tier"
        class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-full font-medium border"
        :class="tierStyles[tier]"
      >
        {{ tierLabel[tier] }}
      </span>
      <!-- Checkmark -->
      <div
        v-if="selected"
        class="absolute top-2 right-2 w-6 h-6 rounded-full bg-accent flex items-center justify-center shadow-lg"
      >
        <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
        </svg>
      </div>
    </div>

    <!-- Content -->
    <div class="p-4 sm:p-5 flex flex-col gap-3 flex-1">
      <div>
        <p class="text-xs theme-text-muted font-medium uppercase tracking-wider mb-1">{{ category }}</p>
        <h3 class="theme-text font-semibold leading-snug line-clamp-2">{{ name }}</h3>
      </div>
      <p class="text-sm theme-text-muted leading-relaxed flex-1 line-clamp-3">{{ spec }}</p>
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t theme-border">
        <div>
          <div v-if="discountActive && discountPercentage > 0" class="flex items-center gap-1.5 mb-0.5">
            <span class="line-through text-[10px] theme-text-muted opacity-70">${{ price.toLocaleString() }}</span>
            <span class="text-[10px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold">-{{ discountPercentage }}%</span>
          </div>
          <p class="text-accent font-semibold font-mono text-lg">${{ finalPrice ? finalPrice.toLocaleString() : price.toLocaleString() }}</p>
          <p class="text-xs theme-text-muted mt-0.5">{{ store }}</p>
        </div>
        <div class="w-full sm:w-auto">
          <slot name="action" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: String,
  category: String,
  spec: String,
  price: Number,
  finalPrice: { type: Number, default: null },
  discountActive: { type: Boolean, default: false },
  discountPercentage: { type: Number, default: 0 },
  store: String,
  image: { type: String, default: null },
  tier: { type: String, default: null },
  selected: { type: Boolean, default: false }
})

const categoryIcons = {
  'CPU': '⚙️', 'Procesador': '⚙️',
  'GPU': '🎮', 'Tarjeta Gráfica': '🎮',
  'RAM': '💾', 'Memoria RAM': '💾',
  'Storage': '💿', 'Almacenamiento': '💿',
  'Motherboard': '🔌',
  'PSU': '⚡', 'Fuente de Poder': '⚡',
  'Cooler': '❄️',
  'Case': '🖥️', 'Gabinete': '🖥️',
}

const categoryIcon = computed(() => categoryIcons[props.category] ?? '🔧')

const tierStyles = {
  alta:  'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border-yellow-200 dark:border-yellow-500/20',
  media: 'bg-blue-50 dark:bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
  baja:  'bg-gray-50 dark:bg-zinc-500/10 text-gray-700 dark:text-zinc-400 border-gray-200 dark:border-zinc-500/20',
}

const tierLabel = {
  alta:  '★ Alta gama',
  media: '◆ Media gama',
  baja:  '◇ Baja gama',
}
</script>