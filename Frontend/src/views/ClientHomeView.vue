<template>
  <main class="min-h-screen theme-bg">

    <!-- Welcome Banner -->
    <section class="relative overflow-hidden border-b theme-border">
      <div class="absolute inset-0 opacity-[0.03]"
        style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
      </div>
      <div class="absolute right-0 top-0 w-[500px] h-[300px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto px-6 py-10 relative z-10">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
          <div>
            <p class="theme-text-muted text-sm mb-1">Bienvenido de vuelta 👋</p>
            <h1 class="text-3xl font-bold theme-text tracking-tight">
              Hola, <span class="text-accent">{{ user?.nombre }}</span>
            </h1>
            <p class="theme-text-muted mt-2 text-sm">¿Qué PC vas a armar hoy?</p>
          </div>

          <div class="flex items-center gap-6 w-full sm:w-auto">
            <div class="hidden md:flex items-center gap-6 pr-6 border-r theme-border">
              <div class="text-right">
                <p class="text-2xl font-bold theme-text font-mono">{{ allComponents.length }}</p>
                <p class="text-xs theme-text-muted">Componentes</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold theme-text font-mono">{{ totalBodegas }}</p>
                <p class="text-xs theme-text-muted">Bodegas</p>
              </div>
            </div>
            <router-link to="/armar" class="btn-primary text-sm px-6 py-3 flex items-center justify-center gap-2 w-full sm:w-auto">
              <Zap class="w-4 h-4" /> Armar mi PC
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 py-10">

      <!-- Search + Category + Filters -->
      <div class="flex flex-col gap-4 mb-10">
        <div class="flex flex-col md:flex-row gap-4">
          <div class="relative flex-1">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 theme-text-muted w-4 h-4" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar componente... Ej: RTX 3060, Ryzen 5, 16GB DDR4"
              class="w-full theme-card border theme-border rounded-xl pl-11 pr-4 py-3.5 text-sm theme-text placeholder:theme-text-muted focus:outline-none focus:border-accent transition-colors shadow-sm"
            />
            <button
              v-if="searchQuery"
              @click="searchQuery = ''"
              class="absolute right-4 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-lg leading-none"
            >×</button>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <button @click="showAdvancedFilters = !showAdvancedFilters" class="px-5 py-3.5 rounded-xl border theme-border theme-card theme-text hover:border-accent/40 transition-all flex items-center gap-2 font-medium text-sm shadow-sm w-full md:w-auto justify-center">
              <Settings class="w-4 h-4" /> Filtros avanzados
            </button>
          </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap mt-2">
          <button
            v-for="cat in ['Todos', ...categories]"
            :key="cat"
            @click="activeCategory = cat"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150 whitespace-nowrap min-h-[44px]"
            :class="activeCategory === cat
              ? 'bg-accent text-white shadow-lg shadow-accent/20'
              : 'theme-card border theme-border theme-text-muted hover:theme-text hover:border-accent/40 shadow-sm'"
          >
            {{ cat }}
          </button>
        </div>

        <!-- Advanced Filters Panel -->
        <div v-if="showAdvancedFilters" class="p-6 theme-card border theme-border shadow-sm rounded-xl grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-6 animate-fade-in mt-2">
          <div>
            <label class="block text-sm font-medium theme-text-muted mb-2">Gama</label>
            <select v-model="filterGama" class="theme-input">
              <option value="">Todas</option>
              <option value="alta">Alta</option>
              <option value="media">Media</option>
              <option value="baja">Baja</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text-muted mb-2">Enfoque</label>
            <select v-model="filterEnfoque" class="theme-input">
              <option value="">Todos</option>
              <option value="gaming">Gaming</option>
              <option value="diseño">Diseño</option>
              <option value="estudio">Estudio</option>
              <option value="oficina">Oficina</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text-muted mb-2">Núcleos (Mín.)</label>
            <input v-model="filterNucleos" type="number" min="1" placeholder="Ej: 6" class="theme-input" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text-muted mb-2">Hilos (Mín.)</label>
            <input v-model="filterHilos" type="number" min="1" placeholder="Ej: 12" class="theme-input" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text-muted mb-2">Frec. (GHz Mín.)</label>
            <input v-model="filterFrecuenciaMin" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="theme-input" />
          </div>
        </div>
      </div>

      

      <!-- Results count -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <p class="theme-text-muted text-sm">
          <span class="theme-text font-medium">{{ filteredComponents.length }}</span>
          componente{{ filteredComponents.length !== 1 ? 's' : '' }} encontrado{{ filteredComponents.length !== 1 ? 's' : '' }}
          <span v-if="searchQuery" class="text-accent"> · "{{ searchQuery }}"</span>
          <span v-if="activeCategory !== 'Todos'" class="text-accent"> · {{ activeCategory }}</span>
        </p>
        <div class="flex items-center gap-2">
          <span class="text-xs theme-text-muted">Ordenar por:</span>
          <select
            v-model="sortBy"
            class="theme-card border theme-border shadow-sm rounded-lg px-3 py-2 text-xs theme-text focus:outline-none focus:border-accent transition-colors"
          >
            <option value="name" class="theme-bg">Nombre</option>
            <option value="price-asc" class="theme-bg">Precio: menor a mayor</option>
            <option value="price-desc" class="theme-bg">Precio: menor a menor</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-24 theme-text-muted text-sm">
        Cargando componentes...
      </div>

      <!-- Empty state -->
      <div v-else-if="filteredComponents.length === 0" class="text-center py-24">
        <Search class="w-12 h-12 mx-auto text-accent/50 mb-4" />
        <p class="theme-text font-semibold text-lg mb-2">Sin resultados</p>
        <p class="theme-text-muted text-sm">
          No encontramos componentes
          <span v-if="searchQuery"> para "<span class="text-accent">{{ searchQuery }}</span>"</span>
        </p>
        <button @click="searchQuery = ''; activeCategory = 'Todos'" class="btn-secondary text-sm mt-6">
          Limpiar búsqueda
        </button>
      </div>

      <!-- Components Grid -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="comp in filteredComponents"
          :key="comp.id"
          class="card-dark rounded-xl flex flex-col card-hover group overflow-hidden"
        >
          <!-- Image band -->
          <div class="relative w-full h-48 theme-bg flex items-center justify-center overflow-hidden flex-shrink-0">
            <template v-if="comp.imagen_url">
              <img :src="comp.imagen_url" :alt="comp.nombre" class="w-full h-full object-contain drop-shadow-sm" />
            </template>
            <template v-else>
              <component :is="categoryIcons[comp.categoria] || Wrench" class="w-10 h-10 opacity-20" />
            </template>

            <!-- Tier badge -->
            <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[comp.gama]">
              {{ comp.gama }}
            </span>

            <!-- Stock badge -->
            <span
              class="absolute top-2 right-2 badge text-xs px-2 py-0.5"
              :class="comp.stock == 0
                ? 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/20'
                : comp.stock <= 3
                  ? 'bg-yellow-50 dark:bg-yellow-500/10 text-yellow-700 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/20'
                  : 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-500/20'"
            >
              {{ comp.stock == 0 ? 'Agotado' : comp.stock <= 3 ? 'Últimas unidades' : 'En stock' }}
            </span>
          </div>

          <!-- Content -->
          <div class="p-5 flex flex-col gap-3 flex-1">
            <div class="flex-1">
              <span class="badge text-xs bg-accent/10 text-accent border border-accent/20 mb-2 inline-block">
                {{ comp.categoria }}
              </span>
              <h3 class="text-sm font-semibold theme-text leading-snug group-hover:text-accent transition-colors">
                {{ comp.nombre }}
              </h3>
              <p class="text-xs theme-text-muted leading-relaxed mt-2">{{ comp.especificacion }}</p>
            </div>

            <div class="pt-3 border-t theme-border space-y-3">
              <div class="flex items-end justify-between">
                <div>
                  <p class="text-accent font-bold font-mono text-lg">${{ Number(comp.precio).toLocaleString() }}</p>
                  <p class="text-xs theme-text-muted mt-0.5">{{ comp.bodega }}</p>
                </div>
                <div v-if="comp.stock > 0" class="text-xs theme-text-muted font-mono">
                  {{ comp.stock }} unid.
                </div>
              </div>
              <router-link
                to="/armar"
                class="w-full text-center text-sm font-medium py-2.5 rounded-lg border theme-border theme-text-muted hover:border-accent hover:text-accent transition-all duration-150 block"
                :class="{ 'opacity-40 pointer-events-none': comp.stock == 0 }"
              >
                {{ comp.stock == 0 ? 'Sin stock' : 'Usar en mi PC →' }}
              </router-link>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Bottom CTA Banner -->
    <section class="border-t theme-border mt-8">
      <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="card-dark rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 relative overflow-hidden text-center sm:text-left">
          <div class="absolute left-0 top-0 w-64 h-full bg-accent/5 blur-3xl pointer-events-none"></div>
          <div class="relative z-10">
            <h2 class="text-2xl font-bold theme-text mb-2">¿Listo para armar tu PC?</h2>
            <p class="theme-text-muted text-sm max-w-md mx-auto sm:mx-0">
              Selecciona tu perfil de uso y te recomendamos los mejores componentes compatibles según tu presupuesto.
            </p>
          </div>
          <router-link to="/armar" class="btn-primary text-base px-8 py-4 relative z-10 w-full sm:w-auto">
            Armar mi PC →
          </router-link>
        </div>
      </div>
    </section>

  </main>
</template>

<script setup>
import { ref, computed, onMounted, markRaw } from 'vue'
import { useAuth } from '../composables/useAuth'
import { Zap, Search, Settings, Wrench, Settings as CpuIcon, Gamepad2, Save, Disc, Plug, Snowflake, Monitor } from '@lucide/vue'

import { API } from '@/config/api'
const { user } = useAuth()

const searchQuery    = ref('')
const activeCategory = ref('Todos')
const sortBy         = ref('name')
const allComponents  = ref([])
const loading        = ref(false)

const categories = ['CPU', 'GPU', 'RAM', 'Storage', 'PSU', 'Motherboard', 'Cooler', 'Case']

const categoryIcons = {
  CPU: markRaw(CpuIcon), GPU: markRaw(Gamepad2), RAM: markRaw(Save), Storage: markRaw(Disc),
  Motherboard: markRaw(Plug), PSU: markRaw(Zap), Cooler: markRaw(Snowflake), Case: markRaw(Monitor)
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const totalBodegas = computed(() => new Set(allComponents.value.map(c => c.bodega)).size)

const showAdvancedFilters = ref(false)
const filterGama = ref('')
const filterEnfoque = ref('')
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredComponents = computed(() => {
  let result = [...allComponents.value]

  if (activeCategory.value !== 'Todos') {
    result = result.filter(c => c.categoria === activeCategory.value)
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(c =>
      c.nombre.toLowerCase().includes(q) ||
      c.especificacion?.toLowerCase().includes(q) ||
      c.categoria.toLowerCase().includes(q) ||
      c.bodega.toLowerCase().includes(q)
    )
  }

  if (filterGama.value) result = result.filter(c => c.gama === filterGama.value)
  if (filterEnfoque.value) result = result.filter(c => c.enfoque_uso === filterEnfoque.value)
  if (filterNucleos.value) result = result.filter(c => c.nucleos >= Number(filterNucleos.value))
  if (filterHilos.value) result = result.filter(c => c.hilos >= Number(filterHilos.value))
  if (filterFrecuenciaMin.value) result = result.filter(c => c.frecuencia_hz >= Number(filterFrecuenciaMin.value))

  if (sortBy.value === 'price-asc')  result.sort((a, b) => a.precio - b.precio)
  if (sortBy.value === 'price-desc') result.sort((a, b) => b.precio - a.precio)
  if (sortBy.value === 'name')       result.sort((a, b) => a.nombre.localeCompare(b.nombre))

  return result
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchComponentes() {
  loading.value = true
  try {
    const res = await fetch(`${API}/componentes/publico`)
    const data = await res.json()
    if (res.ok) allComponents.value = data.componentes
  } catch(e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchComponentes)
</script>
