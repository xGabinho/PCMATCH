<template>
  <div
    class="card-dark card-hover flex flex-col cursor-pointer overflow-hidden"
    :class="{ 'border-accent shadow-lg shadow-accent/10': selected }"
  >
    <!-- Image band -->
    <div class="relative w-full h-36 bg-dark-bg flex items-center justify-center overflow-hidden flex-shrink-0">
      <img
        v-if="image"
        :src="image"
        :alt="name"
        class="w-full h-full object-contain p-4"
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
    <div class="p-5 flex flex-col gap-3 flex-1">
      <div>
        <p class="text-xs text-text-muted font-medium uppercase tracking-wider mb-1">{{ category }}</p>
        <h3 class="text-text-primary font-semibold leading-snug">{{ name }}</h3>
      </div>
      <p class="text-sm text-text-muted leading-relaxed flex-1">{{ spec }}</p>
      <div class="flex items-center justify-between pt-3 border-t border-dark-border">
        <div>
          <p class="text-accent font-semibold font-mono text-lg">${{ price.toLocaleString() }}</p>
          <p class="text-xs text-text-muted mt-0.5">{{ store }}</p>
        </div>
        <slot name="action" />
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
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10   text-blue-400   border-blue-500/20',
  baja:  'bg-zinc-500/10   text-zinc-400   border-zinc-500/20',
}

const tierLabel = {
  alta:  '★ Alta gama',
  media: '◆ Media gama',
  baja:  '◇ Baja gama',
}
</script>