<template>
  <div class="flex min-h-[calc(100vh-4rem)]">

    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
      <div class="max-w-4xl mx-auto px-6 py-10">

        <!-- Stepper -->
        <div class="flex items-center gap-2 mb-10 overflow-x-auto pb-2 scrollbar-hide">
          <div
            v-for="(step, index) in steps"
            :key="step.id"
            class="flex items-center gap-2 flex-shrink-0"
          >
            <button
              @click="activeStep = index; stepSearch = ''"
              class="flex items-center gap-2.5 px-4 py-2 rounded-full text-sm font-medium transition-all duration-200"
              :class="index === activeStep
                ? 'bg-accent text-white shadow-lg shadow-accent/20'
                : selectedItems[steps[index].id]
                  ? 'bg-green-500/10 text-green-400 border border-green-500/20'
                  : index < activeStep
                    ? 'bg-red-500/10 text-red-400 border border-red-500/20'
                    : 'text-text-muted border border-dark-border hover:border-accent/40 hover:text-text-primary'"
            >
              <span v-if="selectedItems[steps[index].id] && index !== activeStep" class="text-xs">✓</span>
              <span v-else-if="!selectedItems[steps[index].id] && index < activeStep" class="text-xs">✗</span>
              <span v-else class="text-xs font-mono">{{ index + 1 }}</span>
              <span class="hidden sm:inline">{{ step.label }}</span>
              <span class="sm:hidden">{{ step.short }}</span>
            </button>
            <span v-if="index < steps.length - 1" class="text-dark-border text-sm flex-shrink-0">→</span>
          </div>
        </div>

        <!-- Step Header -->
        <div class="flex items-start justify-between gap-4 mb-6">
          <div>
            <div class="flex items-center gap-3 mb-1">
              <span class="text-2xl">{{ steps[activeStep].icon }}</span>
              <h1 class="text-2xl font-bold text-text-primary">{{ steps[activeStep].label }}</h1>
              <span v-if="selectedItems[steps[activeStep].id]" class="badge text-xs bg-green-500/10 text-green-400 border border-green-500/20">
                ✓ Seleccionado
              </span>
            </div>
            <p class="text-text-muted text-sm">{{ steps[activeStep].hint }}</p>
          </div>
          <span class="text-xs text-text-muted flex-shrink-0 mt-1">
            Paso {{ activeStep + 1 }} de {{ steps.length }}
          </span>
        </div>

        <!-- Search + Sort + Filters -->
        <div class="flex flex-col gap-3 mb-6">
          <div class="flex items-center gap-3">
            <div class="relative flex-1">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted text-sm">🔍</span>
              <input
                v-model="stepSearch"
                type="text"
                :placeholder="`Buscar ${steps[activeStep].label.toLowerCase()}...`"
                class="w-full bg-dark-card border border-dark-border rounded-xl pl-10 pr-4 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
              <button
                v-if="stepSearch"
                @click="stepSearch = ''"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary text-lg leading-none"
              >×</button>
            </div>
            <select
              v-model="stepSort"
              class="bg-dark-card border border-dark-border rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors flex-shrink-0"
            >
              <option value="default" class="bg-dark-bg">Relevancia</option>
              <option value="price-asc" class="bg-dark-bg">Precio: menor a mayor</option>
              <option value="price-desc" class="bg-dark-bg">Precio: mayor a menor</option>
              <option value="name" class="bg-dark-bg">Nombre A-Z</option>
            </select>
          </div>
          
          <div class="flex items-center gap-3 flex-wrap">
            <select v-model="filterGama" class="bg-dark-card border border-dark-border rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors">
              <option value="">Cualquier Gama</option>
              <option value="alta">Gama Alta</option>
              <option value="media">Gama Media</option>
              <option value="baja">Gama Baja</option>
            </select>
            <select v-model="filterEnfoque" class="bg-dark-card border border-dark-border rounded-xl px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors">
              <option value="">Cualquier Enfoque</option>
              <option value="gaming">Gaming</option>
              <option value="diseño">Diseño</option>
              <option value="oficina">Oficina</option>
              <option value="estudio">Estudio</option>
            </select>
            <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn-secondary text-sm px-4 py-2.5 flex items-center gap-2">
              <span>⚙️</span> Más filtros
            </button>
            <button v-if="filterGama || filterEnfoque || filterNucleos || filterHilos || filterFrecuenciaMin" @click="clearFilters" class="text-sm text-text-muted hover:text-accent transition-colors">
              Limpiar filtros
            </button>
          </div>

          <!-- Advanced Filters Panel -->
          <div v-if="showAdvancedFilters" class="p-4 bg-dark-card border border-dark-border rounded-xl grid grid-cols-1 md:grid-cols-3 gap-4 animate-fade-in mt-2">
            <div>
              <label class="block text-xs font-medium text-text-muted mb-1.5">Mínimo de Núcleos</label>
              <input v-model="filterNucleos" type="number" min="1" placeholder="Ej: 6" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-xs font-medium text-text-muted mb-1.5">Mínimo de Hilos</label>
              <input v-model="filterHilos" type="number" min="1" placeholder="Ej: 12" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-xs font-medium text-text-muted mb-1.5">Frecuencia Mín. (GHz)</label>
              <input v-model="filterFrecuenciaMin" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
        </div>

        <!-- Results count -->
        <p v-if="stepSearch" class="text-xs text-text-muted mb-4">
          {{ filteredItems.length }} resultado{{ filteredItems.length !== 1 ? 's' : '' }} para
          "<span class="text-accent">{{ stepSearch }}</span>"
        </p>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-16 card-dark rounded-xl text-text-muted text-sm">
          Cargando componentes...
        </div>

        <!-- Empty state -->
        <div v-else-if="filteredItems.length === 0" class="text-center py-16 card-dark rounded-xl">
          <div class="text-4xl mb-3">🔍</div>
          <p class="text-text-primary font-medium mb-1">Sin resultados</p>
          <p class="text-text-muted text-sm">
            <span v-if="stepSearch">No hay componentes para "<span class="text-accent">{{ stepSearch }}</span>"</span>
            <span v-else>No hay componentes disponibles para esta categoría</span>
          </p>
          <button v-if="stepSearch" @click="stepSearch = ''" class="btn-secondary text-sm mt-4">Limpiar búsqueda</button>
        </div>

        <!-- Components Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
          <ComponentCard
            v-for="item in filteredItems"
            :key="item.id"
            :name="item.nombre"
            :category="steps[activeStep].label"
            :spec="item.especificacion"
            :price="Number(item.precio)"
            :store="item.bodega"
            :tier="item.gama"
            :image="null"
            :selected="selectedItems[steps[activeStep].id]?.id === item.id"
            @click="selectItem(steps[activeStep].id, item)"
          />
        </div>

        <!-- Navigation -->
        <div class="flex items-center justify-between pt-6 border-t border-dark-border">
          <button
            @click="activeStep = Math.max(0, activeStep - 1); stepSearch = ''"
            class="btn-secondary text-sm"
            :disabled="activeStep === 0"
            :class="{ 'opacity-40 cursor-not-allowed': activeStep === 0 }"
          >
            ← Anterior
          </button>

          <div class="flex items-center gap-1.5">
            <div
              v-for="(_, i) in steps"
              :key="i"
              class="rounded-full transition-all duration-200"
              :class="i === activeStep
                ? 'w-5 h-1.5 bg-accent'
                : selectedItems[steps[i].id]
                  ? 'w-1.5 h-1.5 bg-green-500'
                  : 'w-1.5 h-1.5 bg-dark-border'"
            ></div>
          </div>

          <button
            v-if="activeStep < steps.length - 1"
            @click="activeStep = Math.min(steps.length - 1, activeStep + 1); stepSearch = ''"
            class="btn-primary text-sm"
          >
            Siguiente →
          </button>
          <router-link v-else to="/cotizacion" class="btn-primary text-sm">
            Ver cotización →
          </router-link>
        </div>

      </div>
    </div>

    <!-- Sidebar -->
    <aside class="w-80 border-l border-dark-border bg-dark-card flex-shrink-0 flex flex-col sticky top-16 h-[calc(100vh-4rem)] overflow-auto">
      <div class="p-6 border-b border-dark-border">
        <h2 class="font-semibold text-text-primary">Resumen de tu PC</h2>
        <div class="flex items-center gap-2 mt-1">
          <span class="text-xs text-text-muted">{{ Object.keys(selectedComponents).length }} / {{ steps.length }} componentes</span>
          <div class="flex-1 h-1 bg-dark-bg rounded-full overflow-hidden">
            <div
              class="h-full bg-accent rounded-full transition-all duration-300"
              :style="{ width: `${(Object.keys(selectedComponents).length / steps.length) * 100}%` }"
            ></div>
          </div>
        </div>
      </div>

      <!-- Selected Items -->
      <div class="flex-1 p-4 space-y-2 overflow-auto">
        <div v-if="Object.keys(selectedComponents).length === 0" class="text-center py-10">
          <div class="text-3xl mb-3">🖥️</div>
          <p class="text-text-muted text-sm">Aún no has seleccionado componentes</p>
        </div>

        <template v-for="step in steps" :key="step.id">
          <div
            v-if="selectedComponents[step.id]"
            class="flex items-start justify-between gap-3 p-3 rounded-lg bg-dark-bg border border-dark-border group"
          >
            <div class="flex items-start gap-2 flex-1 min-w-0">
              <span class="text-base flex-shrink-0 mt-0.5">{{ step.icon }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-xs text-text-muted uppercase tracking-wide mb-0.5">{{ step.label }}</p>
                <p class="text-sm text-text-primary font-medium leading-tight truncate">{{ selectedComponents[step.id].nombre }}</p>
                <p class="text-xs text-text-muted mt-0.5">{{ selectedComponents[step.id].bodega }}</p>
              </div>
            </div>
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
              <button
                @click="removeItem(step.id)"
                class="text-text-muted hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100 text-xs leading-none"
                title="Quitar componente"
              >✕</button>
              <div class="flex items-center gap-1">
                <button
                  @click="updateQuantity(step.id, (selectedComponents[step.id].cantidad || 1) - 1)"
                  :disabled="(selectedComponents[step.id].cantidad || 1) <= 1"
                  class="w-5 h-5 rounded border border-dark-border text-text-muted hover:text-red-400 hover:border-red-500/40 transition-colors flex items-center justify-center text-[10px] font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                >−</button>
                <span class="text-xs font-mono text-text-primary w-4 text-center">{{ selectedComponents[step.id].cantidad || 1 }}</span>
                <button
                  @click="updateQuantity(step.id, (selectedComponents[step.id].cantidad || 1) + 1)"
                  :disabled="(selectedComponents[step.id].cantidad || 1) >= (selectedComponents[step.id].stock || 999)"
                  class="w-5 h-5 rounded border border-dark-border text-text-muted hover:text-green-400 hover:border-green-500/40 transition-colors flex items-center justify-center text-[10px] font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                >+</button>
              </div>
              <p class="text-accent text-sm font-semibold font-mono">${{ (Number(selectedComponents[step.id].precio) * (selectedComponents[step.id].cantidad || 1)).toLocaleString() }}</p>
            </div>
          </div>
          <div
            v-else
            class="flex items-center gap-2 p-3 rounded-lg border border-dashed border-dark-border"
          >
            <span class="text-sm opacity-40">{{ step.icon }}</span>
            <p class="text-xs text-text-muted">{{ step.label }} sin seleccionar</p>
          </div>
        </template>
      </div>

      <!-- Total -->
      <div class="p-6 border-t border-dark-border space-y-4">
        <div class="flex items-center justify-between">
          <span class="text-text-muted text-sm">Total estimado</span>
          <span class="text-accent text-2xl font-bold font-mono">${{ totalPrice.toLocaleString() }}</span>
        </div>
        <router-link to="/cotizacion" class="btn-primary w-full text-sm text-center block">
          Ver cotización →
        </router-link>
      </div>
    </aside>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import ComponentCard from '../components/ComponentCard.vue'
import { useBuilder } from '../composables/useBuilder'

const API = '/api'
const router = useRouter()
const { steps, selectedItems, selectedComponents, totalPrice, selectItem, removeItem, updateQuantity } = useBuilder()

const activeStep    = ref(0)
const stepSearch    = ref('')
const stepSort      = ref('default')
const filterGama    = ref('')
const filterEnfoque = ref('')
const showAdvancedFilters = ref(false)
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')
const loading       = ref(false)
const componentesPorCategoria = ref({})

function clearFilters() {
  filterGama.value = ''
  filterEnfoque.value = ''
  filterNucleos.value = ''
  filterHilos.value = ''
  filterFrecuenciaMin.value = ''
}

const currentItems = computed(() => {
  const cat = steps[activeStep.value].categoria
  return componentesPorCategoria.value[cat] ?? []
})

const filteredItems = computed(() => {
  let items = [...currentItems.value]
  if (stepSearch.value.trim()) {
    const q = stepSearch.value.toLowerCase()
    items = items.filter(i =>
      i.nombre.toLowerCase().includes(q) ||
      i.especificacion?.toLowerCase().includes(q) ||
      i.bodega?.toLowerCase().includes(q)
    )
  }
  if (filterGama.value) {
    items = items.filter(i => i.gama === filterGama.value)
  }
  if (filterEnfoque.value) {
    items = items.filter(i => i.enfoque_uso === filterEnfoque.value)
  }
  if (filterNucleos.value) items = items.filter(i => i.nucleos >= Number(filterNucleos.value))
  if (filterHilos.value) items = items.filter(i => i.hilos >= Number(filterHilos.value))
  if (filterFrecuenciaMin.value) items = items.filter(i => i.frecuencia_hz >= Number(filterFrecuenciaMin.value))

  if (stepSort.value === 'price-asc')  items.sort((a, b) => a.precio - b.precio)
  if (stepSort.value === 'price-desc') items.sort((a, b) => b.precio - a.precio)
  if (stepSort.value === 'name')       items.sort((a, b) => a.nombre.localeCompare(b.nombre))
  return items
})

async function fetchTodos() {
  loading.value = true
  try {
    const res = await fetch(`${API}/componentespublico/`)
    const data = await res.json()
    if (res.ok) {
      const agrupado = {}
      for (const comp of data.componentes) {
        if (!agrupado[comp.categoria]) agrupado[comp.categoria] = []
        agrupado[comp.categoria].push(comp)
      }
      componentesPorCategoria.value = agrupado
    }
  } catch(e) { console.error(e) } finally { loading.value = false }
}

onMounted(fetchTodos)
</script>