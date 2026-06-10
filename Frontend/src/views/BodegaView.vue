<template>
  <div class="flex h-screen overflow-hidden bg-dark-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r border-dark-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b border-dark-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">🏪</div>
        <div>
          <p class="text-text-primary font-semibold text-sm leading-none">{{ bodegaNombre }}</p>
          <p class="text-text-muted text-xs mt-0.5">Gestor de bodega</p>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-left"
          :class="activeSection === section.id
            ? 'bg-accent/10 text-accent border border-accent/20'
            : 'text-text-muted hover:text-text-primary hover:bg-dark-card'"
        >
          <span>{{ section.icon }}</span>
          {{ section.label }}
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ myComponents.length }}</span>
        </button>
      </nav>

      <div class="p-3 border-t border-dark-border space-y-1">
        <div class="px-3 py-2.5 rounded-lg bg-dark-card border border-dark-border">
          <p class="text-xs text-text-muted">Sesión activa</p>
          <p class="text-sm font-medium text-text-primary mt-0.5">{{ bodegaCorreo }}</p>
        </div>
        <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-text-muted hover:text-text-primary hover:bg-dark-card transition-all duration-150">
          ← Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b border-dark-border px-8 flex items-center justify-between sticky top-0 bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold text-text-primary">{{ currentSection.label }}</h1>
          <p class="text-xs text-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button v-if="activeSection === 'componentes'" @click="openAddModal" class="btn-primary text-sm">
          + Añadir componente
        </button>
      </div>

      <div class="p-8">

        <!-- ===== DASHBOARD ===== -->
        <template v-if="activeSection === 'dashboard'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Componentes activos</p>
              <p class="text-3xl font-bold font-mono text-text-primary">{{ myComponents.length }}</p>
              <p class="text-xs text-text-muted mt-1">En catálogo de cotizaciones</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Stock total</p>
              <p class="text-3xl font-bold font-mono text-accent">{{ totalStock }}</p>
              <p class="text-xs text-text-muted mt-1">Unidades disponibles</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Alertas de stock</p>
              <p class="text-3xl font-bold font-mono text-yellow-400">{{ stockAlerts.length }}</p>
              <p class="text-xs text-text-muted mt-1">Requieren atención</p>
            </div>
          </div>

          <!-- Stock alerts -->
          <div class="card-dark rounded-xl overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-dark-border flex items-center gap-3">
              <span class="text-yellow-400">⚠️</span>
              <h2 class="font-semibold text-text-primary">Alertas de stock bajo</h2>
            </div>
            <div v-if="stockAlerts.length === 0" class="px-6 py-8 text-center text-text-muted text-sm">
              Sin alertas de stock
            </div>
            <div v-else class="divide-y divide-dark-border">
              <div v-for="alert in stockAlerts" :key="alert.id" class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ alert.categoria }}</span>
                  <span class="text-sm text-text-primary">{{ alert.nombre }}</span>
                </div>
                <div class="flex items-center gap-6">
                  <div class="text-right">
                    <p class="text-xs text-text-muted">Stock actual</p>
                    <p class="text-sm font-mono font-semibold text-yellow-400">{{ alert.stock }} unid.</p>
                  </div>
                  <button @click="openEditComp(alert)" class="btn-secondary text-xs px-3 py-1.5">
                    Actualizar stock
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">

          <!-- Filters -->
          <div class="flex flex-col gap-3 mb-6">
            <div class="flex items-center gap-3">
              <input
                v-model="filterSearch"
                type="text"
                placeholder="Buscar componente..."
                class="bg-dark-card border border-dark-border rounded-lg px-4 py-2.5 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors flex-1 max-w-xs"
              />
              <select
                v-model="filterCategory"
                class="bg-dark-card border border-dark-border rounded-lg px-4 py-2.5 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors"
              >
                <option value="" class="bg-dark-bg">Todas las categorías</option>
                <option v-for="cat in categories" :key="cat" :value="cat" class="bg-dark-bg">{{ cat }}</option>
              </select>
              <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn-secondary text-sm px-4 py-2.5 flex items-center gap-2">
                <span>⚙️</span> Filtros avanzados
              </button>
            </div>
            
            <!-- Advanced Filters Panel -->
            <div v-if="showAdvancedFilters" class="p-4 bg-dark-card border border-dark-border rounded-xl grid grid-cols-2 md:grid-cols-5 gap-4 animate-fade-in">
              <div>
                <label class="block text-xs font-medium text-text-muted mb-1.5">Gama</label>
                <select v-model="filterGama" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors">
                  <option value="">Todas</option>
                  <option value="alta">Alta</option>
                  <option value="media">Media</option>
                  <option value="baja">Baja</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-text-muted mb-1.5">Enfoque</label>
                <select v-model="filterEnfoque" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors">
                  <option value="">Todos</option>
                  <option value="gaming">Gaming</option>
                  <option value="diseño">Diseño</option>
                  <option value="estudio">Estudio</option>
                  <option value="oficina">Oficina</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-text-muted mb-1.5">Núcleos</label>
                <input v-model="filterNucleos" type="number" min="1" placeholder="Ej: 6" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-medium text-text-muted mb-1.5">Hilos</label>
                <input v-model="filterHilos" type="number" min="1" placeholder="Ej: 12" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-medium text-text-muted mb-1.5">Frec. mínima (GHz)</label>
                <input v-model="filterFrecuenciaMin" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full bg-dark-bg border border-dark-border rounded-lg px-3 py-2 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
              </div>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loadingComponents" class="text-center py-16 text-text-muted text-sm">
            Cargando componentes...
          </div>

          <!-- Table -->
          <div v-else class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <table class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr>
                  <th v-for="h in ['Componente', 'Categoría', 'Especificación', 'Gama', 'Precio', 'Stock', 'Estado', 'Acciones']"
                    :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">
                    {{ h }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponents.length === 0">
                  <td colspan="8" class="px-6 py-12 text-center text-text-muted text-sm">Sin componentes</td>
                </tr>
                <tr v-for="comp in filteredComponents" :key="comp.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ comp.nombre }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ comp.categoria }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-text-muted max-w-48 truncate">{{ comp.especificacion }}</td>
                  <td class="px-6 py-4">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[comp.gama]">
                      {{ comp.gama }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-accent font-mono font-semibold">${{ Number(comp.precio).toLocaleString() }}</td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1.5">
                      <button @click="quickAdjust(comp, 'decrementar', 1)" :disabled="comp.stock <= 0 || comp._adjusting" class="w-7 h-7 rounded-lg border border-dark-border bg-dark-bg text-text-muted hover:text-red-400 hover:border-red-500/40 transition-colors flex items-center justify-center text-sm font-bold disabled:opacity-30 disabled:cursor-not-allowed">−</button>
                      <input
                        type="number"
                        :value="stockQty[comp.id] ?? 1"
                        @input="stockQty[comp.id] = Math.max(1, parseInt($event.target.value) || 1)"
                        min="1"
                        class="w-12 h-7 bg-dark-bg border border-dark-border rounded-lg text-center text-xs font-mono text-text-primary focus:outline-none focus:border-accent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                      />
                      <button @click="quickAdjust(comp, 'incrementar', 1)" :disabled="comp._adjusting" class="w-7 h-7 rounded-lg border border-dark-border bg-dark-bg text-text-muted hover:text-green-400 hover:border-green-500/40 transition-colors flex items-center justify-center text-sm font-bold disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                      <span class="text-sm font-mono font-semibold ml-1" :class="comp.stock <= 3 ? 'text-yellow-400' : 'text-accent'">{{ comp.stock }}</span>
                      <div class="flex gap-0.5 ml-1">
                        <button @click="quickAdjust(comp, 'incrementar', stockQty[comp.id] ?? 1)" :disabled="comp._adjusting" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-500/10 text-green-400 border border-green-500/20 hover:bg-green-500/20 transition-colors disabled:opacity-30" title="Agregar cantidad">+{{ stockQty[comp.id] ?? 1 }}</button>
                        <button @click="quickAdjust(comp, 'decrementar', stockQty[comp.id] ?? 1)" :disabled="comp.stock < (stockQty[comp.id] ?? 1) || comp._adjusting" class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors disabled:opacity-30" title="Retirar cantidad">−{{ stockQty[comp.id] ?? 1 }}</button>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1"
                      :class="comp.stock === 0
                        ? 'bg-red-500/10 text-red-400 border border-red-500/20'
                        : comp.stock <= 3
                          ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20'
                          : 'bg-green-500/10 text-green-400 border border-green-500/20'">
                      {{ comp.stock === 0 ? 'Sin stock' : comp.stock <= 3 ? 'Stock bajo' : 'Disponible' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <button @click="openEditComp(comp)" class="text-xs text-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">Editar</button>
                      <button @click="openDeleteComp(comp)" class="text-xs text-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

      </div>
    </main>

    <!-- ==== MODAL AÑADIR COMPONENTE ==== -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">

        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Añadir componente al inventario</h2>
            <p class="text-xs text-text-muted mt-0.5">Selecciona un componente del catálogo maestro</p>
          </div>
          <button @click="closeAddModal" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>

        <div class="space-y-5">

          <!-- Select buscable de producto maestro -->
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Componente maestro</label>
            <div class="relative">
              <input
                v-model="productoSearch"
                @input="showProductoDropdown = true"
                @focus="showProductoDropdown = true"
                type="text"
                placeholder="Buscar componente (ej: Ryzen 5)..."
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                :class="{ 'border-accent': newComp.master_component_id }"
                autocomplete="off"
              />
              <!-- Dropdown -->
              <div
                v-if="showProductoDropdown && productosFiltrados.length > 0"
                class="absolute top-full left-0 right-0 mt-1 bg-dark-card border border-dark-border rounded-lg shadow-xl z-20 max-h-52 overflow-y-auto"
              >
                <button
                  v-for="prod in productosFiltrados"
                  :key="prod.id"
                  @click="selectProducto(prod)"
                  class="w-full flex items-center justify-between px-4 py-2.5 text-sm hover:bg-dark-bg transition-colors text-left"
                >
                  <div class="flex-1 overflow-hidden pr-2">
                    <span class="text-text-primary block truncate">{{ prod.nombre }}</span>
                    <span class="text-xs text-text-muted block mt-0.5 truncate">{{ prod.especificacion }}</span>
                  </div>
                  <span class="text-xs text-accent ml-2 flex-shrink-0">{{ prod.categoria }}</span>
                </button>
              </div>
              <!-- Sin resultados -->
              <div
                v-if="showProductoDropdown && productoSearch.length > 0 && productosFiltrados.length === 0"
                class="absolute top-full left-0 right-0 mt-1 bg-dark-card border border-dark-border rounded-lg shadow-xl z-20 px-4 py-3 text-sm text-text-muted"
              >
                No se encontraron componentes
              </div>
            </div>
            <!-- Selección actual -->
            <div v-if="newComp.master_component_id" class="mt-2 p-3 bg-dark-card border border-dark-border rounded-lg flex flex-col gap-1">
              <span class="text-sm font-medium text-text-primary flex items-center gap-2"><span class="text-accent text-xs">✓</span> {{ newComp.nombre }}</span>
              <span class="text-xs text-text-muted">{{ newComp.especificacion }}</span>
            </div>
          </div>

          <p v-if="addError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ addError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveNewComp" :disabled="savingAdd" class="btn-primary flex-1 text-sm">
            {{ savingAdd ? 'Guardando...' : 'Guardar componente' }}
          </button>
          <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">

        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Editar componente</h2>
            <p class="text-xs text-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditModal = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>

        <div class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Precio ($)</label>
              <input v-model="editingComp.precio" type="number" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Stock</label>
              <input v-model="editingComp.stock" type="number" min="0" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
          
          <div class="p-3 bg-accent/5 border border-accent/20 rounded-lg mt-4">
            <p class="text-xs text-text-muted">Las especificaciones técnicas solo pueden ser modificadas por un Administrador desde el Catálogo Maestro.</p>
          </div>

          <p v-if="editError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveEditComp" :disabled="savingEdit" class="btn-primary flex-1 text-sm">
            {{ savingEdit ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR COMPONENTE ===== -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar componente</h2>
        <p class="text-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="text-text-primary font-semibold mb-2">{{ deletingComp?.nombre }}?</p>
        <p class="text-xs text-text-muted mb-6 px-4">Este componente dejará de aparecer en el catálogo de cotizaciones.</p>
        <div class="flex gap-3">
          <button @click="confirmDelete" :disabled="savingDelete" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDelete ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const API = '/api'

const router = useRouter()
const { logout } = useAuth()
const toast = useToast()

function handleLogout() {
  logout()
  router.push('/login')
}

// Datos de sesión — luego vendrán de useAuth
const token = localStorage.getItem('token') ?? ''
const usuario = JSON.parse(localStorage.getItem('usuario') ?? '{}')
const bodegaNombre = usuario.nombre ?? 'Bodega'
const bodegaCorreo = usuario.correo ?? ''

// Secciones
const activeSection = ref('dashboard')
const sections = [
  { id: 'dashboard',   icon: '📊', label: 'Dashboard',       description: 'Resumen de tu bodega',          count: null },
  { id: 'componentes', icon: '🔧', label: 'Mis componentes', description: 'Gestiona tu catálogo y stock',  count: true },
]
const currentSection = computed(() => sections.find(s => s.id === activeSection.value))

const categories = ['CPU', 'GPU', 'RAM', 'Storage', 'PSU', 'Motherboard', 'Cooler', 'Case']

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const tiers = [
  { id: 'alta',  icon: '🚀', label: 'Alta gama',  desc: 'Top rendimiento',       activeBorder: 'border-purple-500/60', activeBg: 'bg-purple-500/10', activeText: 'text-purple-400' },
  { id: 'media', icon: '⚡', label: 'Gama media', desc: 'Relación precio-valor',  activeBorder: 'border-accent/60',    activeBg: 'bg-accent/10',    activeText: 'text-accent'     },
  { id: 'baja',  icon: '💡', label: 'Gama baja',  desc: 'Económico y funcional', activeBorder: 'border-green-500/60', activeBg: 'bg-green-500/10', activeText: 'text-green-400'  },
]

// ── Componentes ──────────────────────────────────────────
const myComponents = ref([])
const loadingComponents = ref(false)
const filterSearch = ref('')
const filterCategory = ref('')

const showAdvancedFilters = ref(false)
const filterGama = ref('')
const filterEnfoque = ref('')
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')

const filteredComponents = computed(() => {
  let result = [...myComponents.value]
  if (filterCategory.value) result = result.filter(c => c.categoria === filterCategory.value)
  if (filterSearch.value.trim()) {
    const q = filterSearch.value.toLowerCase()
    result = result.filter(c => c.nombre.toLowerCase().includes(q) || c.especificacion?.toLowerCase().includes(q))
  }
  if (filterGama.value) result = result.filter(c => c.gama === filterGama.value)
  if (filterEnfoque.value) result = result.filter(c => c.enfoque_uso === filterEnfoque.value)
  if (filterNucleos.value) result = result.filter(c => c.nucleos == filterNucleos.value)
  if (filterHilos.value) result = result.filter(c => c.hilos == filterHilos.value)
  if (filterFrecuenciaMin.value) result = result.filter(c => c.frecuencia_hz >= Number(filterFrecuenciaMin.value))
  return result
})

const totalStock = computed(() => myComponents.value.reduce((sum, c) => sum + Number(c.stock), 0))
const stockAlerts = computed(() => myComponents.value.filter(c => c.stock <= 3))

// ── Ajuste rápido de stock ────────────────────────────────
const stockQty = ref({})

async function quickAdjust(comp, operacion, cantidad) {
  comp._adjusting = true
  try {
    const res = await fetch(`${API}/componentes/stock`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({ id: comp.id, cantidad, operacion })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al ajustar stock')
      return
    }
    comp.stock = data.nuevo_stock
    toast.success(`Stock ${operacion === 'incrementar' ? 'aumentado' : 'reducido'} (${operacion === 'incrementar' ? '+' : '-'}${cantidad})`)
  } catch (e) {
    toast.error('Error de conexión')
  } finally {
    comp._adjusting = false
  }
}

async function fetchComponents() {
  loadingComponents.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    const data = await res.json()
    if (res.ok) myComponents.value = data.componentes
  } catch (e) {
    console.error(e)
  } finally {
    loadingComponents.value = false
  }
}

// ── Catálogo para el select buscable ─────────────────────
const catalogo = ref([])
const productoSearch = ref('')
const showProductoDropdown = ref(false)

const productosFiltrados = computed(() => {
  if (!productoSearch.value.trim()) return catalogo.value.slice(0, 10)
  const q = productoSearch.value.toLowerCase()
  return catalogo.value.filter(p =>
    p.nombre.toLowerCase().includes(q) ||
    p.especificacion?.toLowerCase().includes(q) ||
    p.categoria.toLowerCase().includes(q)
  ).slice(0, 10)
})

async function fetchCatalogo() {
  try {
    const res = await fetch(`${API}/componentes/maestros`, {
      headers: { Authorization: `Bearer ${token}` }
    })
    const data = await res.json()
    if (res.ok) catalogo.value = data.componentes
  } catch (e) {
    console.error(e)
  }
}

function selectProducto(prod) {
  newComp.value.master_component_id = prod.id
  newComp.value.nombre = prod.nombre
  newComp.value.especificacion = prod.especificacion
  productoSearch.value = prod.nombre
  showProductoDropdown.value = false
}

// Cerrar dropdown al hacer click fuera
function handleClickOutside(e) {
  if (!e.target.closest('.relative')) showProductoDropdown.value = false
}

// ── Modal Añadir ──────────────────────────────────────────
const showAddModal = ref(false)
const addError = ref('')
const savingAdd = ref(false)
const newComp = ref({ master_component_id: null, nombre: '', especificacion: '', precio: '', stock: '' })

function openAddModal() {
  newComp.value = { master_component_id: null, nombre: '', especificacion: '', precio: '', stock: '' }
  productoSearch.value = ''
  addError.value = ''
  showAddModal.value = true
}

function closeAddModal() {
  showAddModal.value = false
  showProductoDropdown.value = false
}

async function saveNewComp() {
  addError.value = ''
  if (!newComp.value.master_component_id) return addError.value = 'Selecciona un componente del catálogo'
  if (!newComp.value.precio || Number(newComp.value.precio) <= 0) return addError.value = 'El precio debe ser mayor a 0'
  if (newComp.value.stock === '' || newComp.value.stock === null || newComp.value.stock === undefined) return addError.value = 'El stock inicial es obligatorio'
  if (!Number.isInteger(Number(newComp.value.stock))) return addError.value = 'El stock debe ser un número entero sin decimales'
  if (Number(newComp.value.stock) < 0) return addError.value = 'El stock no puede ser negativo'

  savingAdd.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        master_component_id: newComp.value.master_component_id,
        precio:              newComp.value.precio,
        stock:               Number(newComp.value.stock),
      })
    })
    const data = await res.json()
    if (!res.ok) {
      const msg = data.message ?? 'Error al guardar el componente'
      toast.error(msg)
      return addError.value = msg
    }
    await fetchComponents()
    closeAddModal()
    toast.success('Componente añadido exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    addError.value = 'Error de conexión con el servidor'
  } finally {
    savingAdd.value = false
  }
}

// ── Modal Editar ──────────────────────────────────────────
const showEditModal = ref(false)
const editingComp = ref({})
const editError = ref('')
const savingEdit = ref(false)

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editError.value = ''
  showEditModal.value = true
}

async function saveEditComp() {
  editError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(editingComp.value.precio) <= 0) {
    return editError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && editingComp.value.stock !== '' && !Number.isInteger(Number(editingComp.value.stock))) {
    return editError.value = 'El stock debe ser un número entero sin decimales'
  }
  if (editingComp.value.stock !== undefined && Number(editingComp.value.stock) < 0) {
    return editError.value = 'El stock no puede ser negativo'
  }

  savingEdit.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        id:             editingComp.value.id,
        precio:         editingComp.value.precio,
        stock:          editingComp.value.stock,
      })
    })
    const data = await res.json()
    if (!res.ok) {
      const msg = data.message ?? 'Error al guardar los cambios'
      toast.error(msg)
      return editError.value = msg
    }
    await fetchComponents()
    showEditModal.value = false
    toast.success('Componente actualizado exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    editError.value = 'Error de conexión con el servidor'
  } finally {
    savingEdit.value = false
  }
}

// ── Modal Eliminar ────────────────────────────────────────
const showDeleteModal = ref(false)
const deletingComp = ref(null)
const savingDelete = ref(false)

function openDeleteComp(comp) {
  deletingComp.value = comp
  showDeleteModal.value = true
}

async function confirmDelete() {
  savingDelete.value = true
  try {
    const res = await fetch(`${API}/componentes?id=${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    })
    const data = await res.json()
    if (res.ok) {
      await fetchComponents()
      showDeleteModal.value = false
      toast.success('Componente eliminado exitosamente')
    } else {
      const msg = data.message ?? 'Error al eliminar componente'
      toast.error(msg)
    }
  } catch (e) {
    console.error(e)
    toast.error('Error de conexión al eliminar')
  } finally {
    savingDelete.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchComponents()
  fetchCatalogo()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Bloquear caracteres no numéricos en inputs de precio (permite punto decimal)
function blockInvalidChars(e) {
  if (['e', 'E', '+', '-'].includes(e.key)) {
    e.preventDefault()
  }
}

// Bloquear caracteres no numéricos en inputs de stock (solo enteros)
function blockInvalidCharsStock(e) {
  if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
    e.preventDefault()
  }
}
</script>