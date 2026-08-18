<template>
  <section class="py-16 border-t theme-border">
    <div class="max-w-7xl mx-auto px-6">

      <!-- Header -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-accent/20 bg-accent/5 text-accent text-xs font-medium mb-4">
          <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
          Los más populares
        </div>
        <h2 class="text-3xl font-bold theme-text tracking-tight mb-2">Más vendidos por categoría</h2>
        <p class="theme-text-muted text-sm max-w-lg mx-auto">
          Descubre los componentes que otros usuarios eligen según su tipo de uso.
        </p>
      </div>

      <!-- Tabs -->
      <div class="flex items-center justify-center gap-2 flex-wrap mb-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          @click="activeTab = tab.key"
          class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200"
          :class="activeTab === tab.key
            ? 'bg-accent text-white shadow-lg shadow-accent/20'
            : 'theme-card border theme-border theme-text-muted hover:theme-text hover:border-accent/40'"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          {{ tab.label }}
        </button>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-16">
        <div class="inline-flex items-center gap-3 theme-text-muted text-sm">
          <svg class="animate-spin h-5 w-5 text-accent" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
          </svg>
          Cargando más vendidos...
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="currentItems.length === 0" class="text-center py-16">
        <Package class="w-10 h-10 mx-auto mb-3 text-text-muted" stroke-width="1.5" />
        <p class="theme-text font-medium mb-1">Sin datos aún</p>
        <p class="theme-text-muted text-sm">Todavía no tenemos suficientes datos de ventas para esta categoría.</p>
      </div>

      <!-- Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 animate-fade-in">
        <div
          v-for="(comp, idx) in currentItems"
          :key="comp.id"
          class="card-dark rounded-xl flex flex-col card-hover group overflow-hidden relative"
        >
          <!-- Rank badge -->
          <div v-if="idx < 3" class="absolute top-3 left-3 z-10 w-7 h-7 rounded-lg flex items-center justify-center text-xs font-bold"
            :class="idx === 0 ? 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30'
                   : idx === 1 ? 'bg-zinc-400/20 text-zinc-300 border border-zinc-400/30'
                   : 'bg-amber-700/20 text-amber-600 border border-amber-700/30'"
          >
            #{{ idx + 1 }}
          </div>

          <!-- Image -->
          <div class="relative w-full h-40 theme-bg flex items-center justify-center overflow-hidden flex-shrink-0">
            <template v-if="comp.imagen_url">
              <img :src="comp.imagen_url" :alt="comp.nombre" class="w-full h-full object-contain drop-shadow-sm" />
            </template>
            <template v-else>
              <component :is="categoryIcons[comp.categoria] ?? Wrench" class="w-10 h-10 opacity-20" />
            </template>

            <span class="absolute top-2 right-2 text-xs px-2 py-0.5 rounded-full font-medium border"
              :class="tierStyles[comp.gama]"
            >
              {{ comp.gama }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-4 flex flex-col gap-2 flex-1">
            <span class="badge text-xs bg-accent/10 text-accent border border-accent/20 self-start">
              {{ comp.categoria }}
            </span>
            <h3 class="text-sm font-semibold theme-text leading-snug group-hover:text-accent transition-colors">
              {{ comp.nombre }}
            </h3>
            <p class="text-xs theme-text-muted leading-relaxed line-clamp-2">{{ comp.especificacion }}</p>

            <div class="mt-auto pt-3 border-t theme-border flex items-end justify-between">
              <div>
                <p class="text-accent font-bold font-mono text-lg">${{ Number(comp.precio).toLocaleString() }}</p>
                <p class="text-xs theme-text-muted">{{ comp.bodega }}</p>
              </div>
              <div v-if="comp.veces_cotizado > 0" class="flex items-center gap-1 text-xs theme-text-muted">
                <Flame class="w-3 h-3 text-orange-500" />
                {{ comp.veces_cotizado }} cotización{{ comp.veces_cotizado !== 1 ? 'es' : '' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { Gamepad2, BookOpen, Briefcase, Palette, Settings, Save, Disc, Plug, Zap, Snowflake, Monitor, Package, Flame, Wrench } from 'lucide-vue-next';

import { ref, computed, onMounted } from 'vue'


const API = '/api'

const tabs = [
  { key: 'gaming',  icon: Gamepad2, label: 'Gaming' },
  { key: 'estudio', icon: BookOpen, label: 'Estudio' },
  { key: 'oficina', icon: Briefcase, label: 'Oficina' },
  { key: 'diseño',  icon: Palette, label: 'Diseño' },
]

const activeTab = ref('gaming')
const loading = ref(false)
const masVendidos = ref({})

const categoryIcons = {
  CPU: Settings, GPU: Gamepad2, RAM: Save, Storage: Disc,
  Motherboard: Plug, PSU: Zap, Cooler: Snowflake, Case: Monitor
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const currentItems = computed(() => masVendidos.value[activeTab.value] ?? [])

async function fetchMasVendidos() {
  loading.value = true
  try {
    const res = await fetch(`${API}/recomendaciones/mas-vendidos`)
    const data = await res.json()
    if (res.ok && data.success) {
      masVendidos.value = data.mas_vendidos
    }
  } catch (e) {
    console.error('Error fetching mas vendidos:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchMasVendidos)
</script>
