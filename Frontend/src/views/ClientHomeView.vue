<template>
  <main class="min-h-screen bg-dark-bg">

    <!-- Welcome Banner -->
    <section class="relative overflow-hidden border-b border-dark-border">
      <div class="absolute inset-0 opacity-[0.03]"
        style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
      </div>
      <div class="absolute right-0 top-0 w-[500px] h-[300px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto px-6 py-10 relative z-10">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-text-muted text-sm mb-1">Bienvenido de vuelta 👋</p>
            <h1 class="text-3xl font-bold text-text-primary tracking-tight">
              Hola, <span class="text-accent">{{ user?.nombre }}</span>
            </h1>
            <p class="text-text-muted mt-2 text-sm">¿Qué PC vas a armar hoy?</p>
          </div>

          <div class="flex items-center gap-6">
            <div class="hidden md:flex items-center gap-6 pr-6 border-r border-dark-border">
              <div class="text-right">
                <p class="text-2xl font-bold text-text-primary font-mono">{{ allComponents.length }}</p>
                <p class="text-xs text-text-muted">Componentes</p>
              </div>
              <div class="text-right">
                <p class="text-2xl font-bold text-text-primary font-mono">{{ totalBodegas }}</p>
                <p class="text-xs text-text-muted">Bodegas</p>
              </div>
            </div>
            <router-link to="/armar" class="btn-primary text-sm px-6 py-3 flex items-center gap-2">
              <span>⚡</span> Armar mi PC
            </router-link>
          </div>
        </div>
      </div>
    </section>

    <div class="max-w-7xl mx-auto px-6 py-10">

      <!-- Search + Filters -->
      <div class="flex flex-col md:flex-row gap-4 mb-10">
        <div class="relative flex-1">
          <span class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted text-sm">🔍</span>
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar componente... Ej: RTX 3060, Ryzen 5, 16GB DDR4"
            class="w-full bg-dark-card border border-dark-border rounded-xl pl-11 pr-4 py-3.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
          />
          <button
            v-if="searchQuery"
            @click="searchQuery = ''"
            class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors text-lg leading-none"
          >×</button>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
          <button
            v-for="cat in ['Todos', ...categories]"
            :key="cat"
            @click="activeCategory = cat"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150 whitespace-nowrap"
            :class="activeCategory === cat
              ? 'bg-accent text-white shadow-lg shadow-accent/20'
              : 'bg-dark-card border border-dark-border text-text-muted hover:text-text-primary hover:border-accent/40'"
          >
            {{ cat }}
          </button>
        </div>
      </div>

      

      <!-- Results count -->
      <div class="flex items-center justify-between mb-6">
        <p class="text-text-muted text-sm">
          <span class="text-text-primary font-medium">{{ filteredComponents.length }}</span>
          componente{{ filteredComponents.length !== 1 ? 's' : '' }} encontrado{{ filteredComponents.length !== 1 ? 's' : '' }}
          <span v-if="searchQuery" class="text-accent"> · "{{ searchQuery }}"</span>
          <span v-if="activeCategory !== 'Todos'" class="text-accent"> · {{ activeCategory }}</span>
        </p>
        <div class="flex items-center gap-2">
          <span class="text-xs text-text-muted">Ordenar por:</span>
          <select
            v-model="sortBy"
            class="bg-dark-card border border-dark-border rounded-lg px-3 py-1.5 text-xs text-text-primary focus:outline-none focus:border-accent transition-colors"
          >
            <option value="name" class="bg-dark-bg">Nombre</option>
            <option value="price-asc" class="bg-dark-bg">Precio: menor a mayor</option>
            <option value="price-desc" class="bg-dark-bg">Precio: mayor a menor</option>
          </select>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-24 text-text-muted text-sm">
        Cargando componentes...
      </div>

      <!-- Empty state -->
      <div v-else-if="filteredComponents.length === 0" class="text-center py-24">
        <div class="text-5xl mb-4">🔍</div>
        <p class="text-text-primary font-semibold text-lg mb-2">Sin resultados</p>
        <p class="text-text-muted text-sm">
          No encontramos componentes
          <span v-if="searchQuery"> para "<span class="text-accent">{{ searchQuery }}</span>"</span>
        </p>
        <button @click="searchQuery = ''; activeCategory = 'Todos'" class="btn-secondary text-sm mt-6">
          Limpiar búsqueda
        </button>
      </div>

      <!-- Components Grid -->
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <div
          v-for="comp in filteredComponents"
          :key="comp.id"
          class="card-dark rounded-xl flex flex-col card-hover group overflow-hidden"
        >
          <!-- Image band -->
          <div class="relative w-full h-36 bg-dark-bg flex items-center justify-center overflow-hidden flex-shrink-0">
            <span class="text-4xl opacity-20">{{ categoryIcons[comp.categoria] ?? '🔧' }}</span>

            <!-- Tier badge -->
            <span class="absolute top-2 left-2 text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[comp.gama]">
              {{ comp.gama }}
            </span>

            <!-- Stock badge -->
            <span
              class="absolute top-2 right-2 badge text-xs px-2 py-0.5"
              :class="comp.stock == 0
                ? 'bg-red-500/10 text-red-400 border border-red-500/20'
                : comp.stock <= 3
                  ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20'
                  : 'bg-green-500/10 text-green-400 border border-green-500/20'"
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
              <h3 class="text-sm font-semibold text-text-primary leading-snug group-hover:text-accent transition-colors">
                {{ comp.nombre }}
              </h3>
              <p class="text-xs text-text-muted leading-relaxed mt-2">{{ comp.especificacion }}</p>
            </div>

            <div class="pt-3 border-t border-dark-border space-y-3">
              <div class="flex items-end justify-between">
                <div>
                  <p class="text-accent font-bold font-mono text-lg">${{ Number(comp.precio).toLocaleString() }}</p>
                  <p class="text-xs text-text-muted mt-0.5">{{ comp.bodega }}</p>
                </div>
                <div v-if="comp.stock > 0" class="text-xs text-text-muted font-mono">
                  {{ comp.stock }} unid.
                </div>
              </div>
              <router-link
                to="/armar"
                class="w-full text-center text-sm font-medium py-2 rounded-lg border border-dark-border text-text-muted hover:border-accent hover:text-accent transition-all duration-150 block"
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
    <section class="border-t border-dark-border mt-8">
      <div class="max-w-7xl mx-auto px-6 py-12">
        <div class="card-dark rounded-2xl p-8 flex items-center justify-between relative overflow-hidden">
          <div class="absolute left-0 top-0 w-64 h-full bg-accent/5 blur-3xl pointer-events-none"></div>
          <div class="relative z-10">
            <h2 class="text-2xl font-bold text-text-primary mb-2">¿Listo para armar tu PC?</h2>
            <p class="text-text-muted text-sm max-w-md">
              Selecciona tu perfil de uso y te recomendamos los mejores componentes compatibles según tu presupuesto.
            </p>
          </div>
          <router-link to="/armar" class="btn-primary text-base px-8 py-4 relative z-10 flex-shrink-0">
            Armar mi PC →
          </router-link>
        </div>
      </div>
    </section>

  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'

const API = 'http://127.0.0.1:8000/api'
const { user } = useAuth()

const searchQuery    = ref('')
const activeCategory = ref('Todos')
const sortBy         = ref('name')
const allComponents  = ref([])
const loading        = ref(false)

const categories = ['CPU', 'GPU', 'RAM', 'Storage', 'PSU', 'Motherboard', 'Cooler', 'Case']

const categoryIcons = {
  CPU: '⚙️', GPU: '🎮', RAM: '💾', Storage: '💿',
  Motherboard: '🔌', PSU: '⚡', Cooler: '❄️', Case: '🖥️'
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const totalBodegas = computed(() => new Set(allComponents.value.map(c => c.bodega)).size)

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

  if (sortBy.value === 'price-asc')  result.sort((a, b) => a.precio - b.precio)
  if (sortBy.value === 'price-desc') result.sort((a, b) => b.precio - a.precio)
  if (sortBy.value === 'name')       result.sort((a, b) => a.nombre.localeCompare(b.nombre))

  return result
})

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