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
          <div class="flex items-center gap-3 mb-6">
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
                    <div class="flex items-center gap-2">
                      <span class="text-sm font-mono font-medium" :class="comp.stock <= 3 ? 'text-yellow-400' : 'text-text-primary'">
                        {{ comp.stock }}
                      </span>
                      <span class="text-xs text-text-muted">unid.</span>
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

    <!-- ===== MODAL AÑADIR COMPONENTE ===== -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">

        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Añadir componente</h2>
            <p class="text-xs text-text-muted mt-0.5">Agrega un nuevo producto a tu bodega</p>
          </div>
          <button @click="closeAddModal" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>

        <div class="space-y-5">

          <!-- Select buscable de producto -->
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Producto</label>
            <div class="relative">
              <input
                v-model="productoSearch"
                @input="showProductoDropdown = true"
                @focus="showProductoDropdown = true"
                type="text"
                placeholder="Buscar producto del catálogo..."
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                :class="{ 'border-accent': newComp.producto_id }"
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
                  <span class="text-text-primary">{{ prod.nombre }}</span>
                  <span class="text-xs text-text-muted ml-3 flex-shrink-0">{{ prod.categoria }}</span>
                </button>
              </div>
              <!-- Sin resultados -->
              <div
                v-if="showProductoDropdown && productoSearch.length > 0 && productosFiltrados.length === 0"
                class="absolute top-full left-0 right-0 mt-1 bg-dark-card border border-dark-border rounded-lg shadow-xl z-20 px-4 py-3 text-sm text-text-muted"
              >
                No existe ese producto en el catálogo
              </div>
            </div>
            <!-- Categoría autocompletada -->
            <p v-if="newComp.categoria" class="text-xs text-accent mt-1.5 flex items-center gap-1">
              <span>✓</span> Categoría: {{ newComp.categoria }}
            </p>
          </div>

          <!-- Especificación -->
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Especificación técnica</label>
            <input
              v-model="newComp.especificacion"
              type="text"
              placeholder="Ej: 6 núcleos / 12 hilos · 3.7GHz · AM4"
              class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
            />
          </div>

          <!-- Gama -->
          <div>
            <label class="block text-sm font-medium text-text-primary mb-3">Gama del componente</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="tier in tiers"
                :key="tier.id"
                @click="newComp.gama = tier.id"
                class="flex flex-col items-center gap-2 p-3 rounded-xl border transition-all duration-150"
                :class="newComp.gama === tier.id
                  ? `${tier.activeBorder} ${tier.activeBg} ${tier.activeText}`
                  : 'border-dark-border text-text-muted hover:border-accent/40 hover:text-text-primary'"
              >
                <span class="text-xl">{{ tier.icon }}</span>
                <span class="text-xs font-semibold">{{ tier.label }}</span>
                <span class="text-xs opacity-70 text-center leading-tight">{{ tier.desc }}</span>
              </button>
            </div>
          </div>

          <!-- Precio y Stock -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Precio ($)</label>
              <input
                v-model="newComp.precio"
                type="number"
                placeholder="189000"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Stock inicial</label>
              <input
                v-model="newComp.stock"
                type="number"
                placeholder="10"
                min="0"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
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
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Especificación técnica</label>
            <input v-model="editingComp.especificacion" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>

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

          <div>
            <label class="block text-sm font-medium text-text-primary mb-3">Gama del componente</label>
            <div class="grid grid-cols-3 gap-3">
              <button
                v-for="tier in tiers"
                :key="tier.id"
                @click="editingComp.gama = tier.id"
                class="flex flex-col items-center gap-2 p-3 rounded-xl border transition-all duration-150"
                :class="editingComp.gama === tier.id
                  ? `${tier.activeBorder} ${tier.activeBg} ${tier.activeText}`
                  : 'border-dark-border text-text-muted hover:border-accent/40 hover:text-text-primary'"
              >
                <span class="text-xl">{{ tier.icon }}</span>
                <span class="text-xs font-semibold">{{ tier.label }}</span>
                <span class="text-xs opacity-70 text-center leading-tight">{{ tier.desc }}</span>
              </button>
            </div>
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const API = 'http://localhost/pcmatch/backend/api'

const router = useRouter()
const { logout } = useAuth()

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

const filteredComponents = computed(() => {
  let result = [...myComponents.value]
  if (filterCategory.value) result = result.filter(c => c.categoria === filterCategory.value)
  if (filterSearch.value.trim()) {
    const q = filterSearch.value.toLowerCase()
    result = result.filter(c => c.nombre.toLowerCase().includes(q) || c.especificacion?.toLowerCase().includes(q))
  }
  return result
})

const totalStock = computed(() => myComponents.value.reduce((sum, c) => sum + Number(c.stock), 0))
const stockAlerts = computed(() => myComponents.value.filter(c => c.stock <= 3))

async function fetchComponents() {
  loadingComponents.value = true
  try {
    const res = await fetch(`${API}/componentes/`, {
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
    p.categoria.toLowerCase().includes(q)
  ).slice(0, 10)
})

async function fetchCatalogo() {
  try {
    const res = await fetch(`${API}/catalogo/`)
    const data = await res.json()
    if (res.ok) catalogo.value = data.productos
  } catch (e) {
    console.error(e)
  }
}

function selectProducto(prod) {
  newComp.value.producto_id = prod.id
  newComp.value.categoria   = prod.categoria
  productoSearch.value      = prod.nombre
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
const newComp = ref({ producto_id: null, categoria: '', especificacion: '', gama: 'media', precio: '', stock: '' })

function openAddModal() {
  newComp.value = { producto_id: null, categoria: '', especificacion: '', gama: 'media', precio: '', stock: '' }
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
  if (!newComp.value.producto_id) return addError.value = 'Selecciona un producto del catálogo'
  if (!newComp.value.gama)        return addError.value = 'Selecciona una gama'
  if (!newComp.value.precio)      return addError.value = 'Ingresa un precio'

  savingAdd.value = true
  try {
    const res = await fetch(`${API}/componentes/`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        producto_id:    newComp.value.producto_id,
        especificacion: newComp.value.especificacion,
        gama:           newComp.value.gama,
        precio:         newComp.value.precio,
        stock:          newComp.value.stock || 0,
      })
    })
    const data = await res.json()
    if (!res.ok) return addError.value = data.error ?? 'Error al guardar'
    await fetchComponents()
    closeAddModal()
  } catch (e) {
    addError.value = 'Error de conexión'
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
  savingEdit.value = true
  try {
    const res = await fetch(`${API}/componentes/`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        id:             editingComp.value.id,
        especificacion: editingComp.value.especificacion,
        gama:           editingComp.value.gama,
        precio:         editingComp.value.precio,
        stock:          editingComp.value.stock,
      })
    })
    const data = await res.json()
    if (!res.ok) return editError.value = data.error ?? 'Error al guardar'
    await fetchComponents()
    showEditModal.value = false
  } catch (e) {
    editError.value = 'Error de conexión'
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
    const res = await fetch(`${API}/componentes/?id=${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    })
    if (res.ok) {
      await fetchComponents()
      showDeleteModal.value = false
    }
  } catch (e) {
    console.error(e)
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
</script>