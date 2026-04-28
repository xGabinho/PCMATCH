<template>
  <div class="flex h-screen overflow-hidden bg-dark-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r border-dark-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b border-dark-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-xs">PC</div>
        <div>
          <p class="text-text-primary font-semibold text-sm leading-none">PCMATCH</p>
          <p class="text-text-muted text-xs mt-0.5">Proveedor</p>
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
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ section.count }}</span>
        </button>
      </nav>

      <!-- Info proveedor -->
      <div class="p-4 border-t border-dark-border">
        <div class="px-3 py-2.5 mb-1">
          <p class="text-text-primary text-sm font-medium truncate">{{ user?.nombre }}</p>
          <p class="text-text-muted text-xs truncate">{{ user?.correo }}</p>
        </div>
        <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-text-muted hover:text-text-primary hover:bg-dark-card transition-all duration-150">
          ← Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b border-dark-border px-8 flex items-center justify-between sticky top-0 bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold text-text-primary">{{ currentSection.label }}</h1>
          <p class="text-xs text-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button v-if="currentSection.cta" @click="handleCta" class="btn-primary text-sm">
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

        <!-- ===== DASHBOARD ===== -->
        <template v-if="activeSection === 'dashboard'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Mis bodegas</p>
              <p class="text-3xl font-bold text-text-primary font-mono">{{ bodegas.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Bodegas activas</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ bodegas.filter(b => b.activa == 1).length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Total componentes</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ bodegas.reduce((s, b) => s + Number(b.total_componentes), 0) }}</p>
            </div>
          </div>

          <!-- Resumen de bodegas -->
          <div class="card-dark rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-dark-border">
              <h2 class="font-semibold text-text-primary">Mis bodegas</h2>
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center text-text-muted text-sm">Cargando...</div>
            <div v-else-if="bodegas.length === 0" class="px-6 py-12 text-center text-text-muted text-sm">
              No tienes bodegas asignadas aún
            </div>
            <div v-else class="divide-y divide-dark-border">
              <div v-for="b in bodegas" :key="b.id" class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-sm">🏪</div>
                  <div>
                    <p class="text-sm font-medium text-text-primary">{{ b.nombre }}</p>
                    <p class="text-xs text-text-muted">{{ b.correo }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-sm font-mono text-text-primary">{{ b.total_componentes }}</p>
                    <p class="text-xs text-text-muted">componentes</p>
                  </div>
                  <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                    {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== MIS BODEGAS ===== -->
        <template v-if="activeSection === 'bodegas'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Mis bodegas</h2>
              <input v-model="filterBodega" type="text" placeholder="Buscar..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center text-text-muted text-sm">Cargando bodegas...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Nombre','Teléfono','Correo','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin bodegas asignadas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ b.telefono || '—' }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ b.correo }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-text-primary">{{ b.total_componentes }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button @click="toggleActivoBodega(b)" class="text-xs text-text-muted hover:text-green-400 px-2 py-1 rounded hover:bg-green-400/10 transition-colors">
                           {{ b.activa == 1 ? 'Desactivar' : 'Activar' }}
                        </button>
                      <button @click="openEditBodega(b)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="openDeleteBodega(b)" class="text-xs text-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COTIZACIONES ===== -->
        <template v-if="activeSection === 'cotizaciones'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border">
              <h2 class="font-semibold text-text-primary">Cotizaciones de mis bodegas</h2>
            </div>
            <div v-if="loadingCotizaciones" class="px-6 py-12 text-center text-text-muted text-sm">Cargando...</div>
            <div v-else-if="cotizaciones.length === 0" class="px-6 py-12 text-center text-text-muted text-sm">Sin cotizaciones aún</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['#','Cliente','Perfil','Componentes','Total','Fecha']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-for="c in cotizaciones" :key="c.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-mono text-text-muted">#{{ c.id }}</td>
                  <td class="px-6 py-4 text-sm text-text-primary">{{ c.nombre }} {{ c.apellido }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ perfilLabel(c.perfil) }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-text-primary">{{ c.total_items }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-accent font-medium">${{ Number(c.total).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ formatDate(c.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Componentes de mis bodegas</h2>
              <input v-model="filterComponente" type="text" placeholder="Buscar..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingComponentes" class="px-6 py-12 text-center text-text-muted text-sm">Cargando componentes...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Componente','Categoría','Gama','Precio','Stock','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ c.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[c.gama]">{{ c.gama }}</span></td>
                  <td class="px-6 py-4 text-sm text-accent font-mono font-medium">${{ Number(c.precio).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm font-mono" :class="c.stock <= 3 ? 'text-yellow-400' : 'text-text-primary'">{{ c.stock }}</td>
                  <td class="px-6 py-4">
                    <button @click="openEditComp(c)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

      </div>
    </main>

    <!-- ===== MODAL CREAR BODEGA ===== -->
    <div v-if="showBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Agregar bodega</h2>
            <p class="text-xs text-text-muted mt-0.5">Se asignará automáticamente a tu cuenta</p>
          </div>
          <button @click="showBodegaModal = false" class="text-text-muted hover:text-text-primary text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Nombre de la bodega</label>
            <input v-model="newBodega.nombre" type="text" placeholder="Ej: Bodega Norte" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Correo electrónico</label>
            <input v-model="newBodega.correo" type="email" placeholder="bodega@email.com" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
            <input v-model="newBodega.telefono" type="tel" placeholder="+57 300 123 4567" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Contraseña de acceso</label>
            <input v-model="newBodega.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <p v-if="bodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ bodegaError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveNewBodega" :disabled="savingBodega" class="btn-primary flex-1 text-sm">{{ savingBodega ? 'Creando...' : 'Crear bodega' }}</button>
          <button @click="showBodegaModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR BODEGA ===== -->
    <div v-if="showEditBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-text-primary">Editar bodega</h2>
          <button @click="showEditBodegaModal = false" class="text-text-muted hover:text-text-primary text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
            <input v-model="editingBodega.nombre" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
            <input v-model="editingBodega.telefono" type="tel" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Estado</label>
            <div class="grid grid-cols-2 gap-3">
              <button @click="editingBodega.activa = 1" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-dark-border text-text-muted hover:border-green-500/30'">✓ Activa</button>
              <button @click="editingBodega.activa = 0" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 0 ? 'border-red-500/40 bg-red-500/10 text-red-400' : 'border-dark-border text-text-muted hover:border-red-500/30'">✕ Inactiva</button>
            </div>
          </div>
          <p v-if="editBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editBodegaError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveEditBodega" :disabled="savingEditBodega" class="btn-primary flex-1 text-sm">{{ savingEditBodega ? 'Guardando...' : 'Guardar cambios' }}</button>
          <button @click="showEditBodegaModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR BODEGA ===== -->
    <div v-if="showDeleteBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar bodega</h2>
        <p class="text-text-muted text-sm mb-1">¿Eliminar <span class="text-text-primary font-semibold">{{ deletingBodega?.nombre }}</span>?</p>
        <p class="text-xs text-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
        <p v-if="deleteBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-4">{{ deleteBodegaError }}</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteBodega" :disabled="savingDeleteBodega" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteBodega ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteBodegaModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditCompModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Editar componente</h2>
            <p class="text-xs text-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditCompModal = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
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
              <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" @click="editingComp.gama = tier"
                class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                :class="editingComp.gama === tier ? 'border-accent bg-accent/10 text-accent' : 'border-dark-border text-text-muted hover:border-accent/40'">
                {{ tier.charAt(0).toUpperCase() + tier.slice(1) }}
              </button>
            </div>
          </div>

          <p v-if="editCompError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editCompError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveEditComp" :disabled="savingEditComp" class="btn-primary flex-1 text-sm">
            {{ savingEditComp ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditCompModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const API = 'http://127.0.0.1:8000/api'
const { getToken, logout, user } = useAuth()
const router = useRouter()

function handleLogout() { logout(); router.push('/login') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function perfilLabel(p) { return ({ office: '💼 Oficina', gaming: '🎮 Gaming', design: '🎨 Diseño', study: '📚 Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('dashboard')

const sections = computed(() => [
  { id: 'dashboard',    icon: '📊', label: 'Dashboard',    description: 'Resumen general de tus bodegas',           cta: null,            count: null                  },
  { id: 'bodegas',      icon: '🏪', label: 'Mis bodegas',  description: `${bodegas.value.length} bodegas asignadas`, cta: '+ Nueva bodega', count: bodegas.value.length  },
  { id: 'componentes',  icon: '🔧', label: 'Componentes',  description: `Componentes de tus bodegas`,               cta: null,            count: componentes.value.length },
  { id: 'cotizaciones', icon: '📄', label: 'Cotizaciones', description: 'Cotizaciones de tus bodegas',              cta: null,            count: cotizaciones.value.length },
])

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

function handleCta() {
  if (activeSection.value === 'bodegas') {
    newBodega.value = { nombre: '', correo: '', telefono: '', password: '' }
    bodegaError.value = ''
    showBodegaModal.value = true
  }
}

// ── Bodegas ───────────────────────────────────────────────
const bodegas            = ref([])
const loadingBodegas     = ref(false)
const filterBodega       = ref('')
const showBodegaModal    = ref(false)
const showEditBodegaModal   = ref(false)
const showDeleteBodegaModal = ref(false)
const newBodega          = ref({ nombre: '', correo: '', telefono: '', password: '' })
const editingBodega      = ref({})
const deletingBodega     = ref(null)
const bodegaError        = ref('')
const editBodegaError    = ref('')
const savingBodega       = ref(false)
const savingEditBodega   = ref(false)
const savingDeleteBodega = ref(false)
const deleteBodegaError  = ref('')

const filteredBodegas = computed(() => {
  if (!filterBodega.value.trim()) return bodegas.value
  const q = filterBodega.value.toLowerCase()
  return bodegas.value.filter(b => b.nombre.toLowerCase().includes(q) || b.correo.toLowerCase().includes(q))
})

async function fetchBodegas() {
  loadingBodegas.value = true
  try {
    const res = await fetch(`${API}/bodegas`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) bodegas.value = data.bodegas
  } catch(e) { console.error(e) } finally { loadingBodegas.value = false }
}

async function saveNewBodega() {
  bodegaError.value = ''
  if (!newBodega.value.nombre || !newBodega.value.correo || !newBodega.value.password)
    return bodegaError.value = 'Nombre, correo y contraseña son requeridos'
  savingBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newBodega.value)
    })
    const data = await res.json()
    if (!res.ok) return bodegaError.value = data.message ?? 'Error al crear'
    await fetchBodegas()
    showBodegaModal.value = false
  } catch(e) { bodegaError.value = 'Error de conexión' } finally { savingBodega.value = false }
}

function openEditBodega(b) { editingBodega.value = { ...b }; editBodegaError.value = ''; showEditBodegaModal.value = true }

async function saveEditBodega() {
  editBodegaError.value = ''
  if (!editingBodega.value.nombre) return editBodegaError.value = 'El nombre es requerido'
  savingEditBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: editingBodega.value.id, nombre: editingBodega.value.nombre, telefono: editingBodega.value.telefono, activa: editingBodega.value.activa })
    })
    const data = await res.json()
    if (!res.ok) return editBodegaError.value = data.message ?? 'Error'
    await fetchBodegas()
    showEditBodegaModal.value = false
  } catch(e) { editBodegaError.value = 'Error de conexión' } finally { savingEditBodega.value = false }
}

function openDeleteBodega(b) { deletingBodega.value = b; deleteBodegaError.value = ''; showDeleteBodegaModal.value = true }

async function confirmDeleteBodega() {
  deleteBodegaError.value = ''
  savingDeleteBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas?id=${deletingBodega.value.id}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      deleteBodegaError.value = data.message ?? 'Error al eliminar la bodega'
      return
    }
    await fetchBodegas()
    showDeleteBodegaModal.value = false
  } catch(e) { 
    console.error(e)
    deleteBodegaError.value = 'Error de conexión'
  } finally { 
    savingDeleteBodega.value = false 
  }
}

async function toggleActivoBodega(b) {
  const activaNuevo = b.activa == 1 ? 0 : 1
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: b.id,
        nombre: b.nombre,
        activa: activaNuevo
      })
    })
    if (res.ok) {
      await fetchBodegas()
    }
  } catch(e) { console.error('Error al cambiar de estado', e) }
}

// ── Cotizaciones ──────────────────────────────────────────
const cotizaciones        = ref([])
const loadingCotizaciones = ref(false)

async function fetchCotizaciones() {
  loadingCotizaciones.value = true
  try {
    const res = await fetch(`${API}/cotizaciones`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) cotizaciones.value = data.cotizaciones
  } catch(e) { console.error(e) } finally { loadingCotizaciones.value = false }
}

// ── Componentes ───────────────────────────────────────────
const componentes       = ref([])
const loadingComponentes = ref(false)
const filterComponente  = ref('')
const showEditCompModal = ref(false)
const editingComp = ref({})
const editCompError = ref('')
const savingEditComp = ref(false)

const filteredComponentes = computed(() => {
  if (!filterComponente.value.trim()) return componentes.value
  const q = filterComponente.value.toLowerCase()
  return componentes.value.filter(c => c.nombre.toLowerCase().includes(q) || c.categoria.toLowerCase().includes(q))
})

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/componentes`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.componentes
  } catch(e) { console.error(e) } finally { loadingComponentes.value = false }
}

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editCompError.value = ''
  showEditCompModal.value = true
}

async function saveEditComp() {
  editCompError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(editingComp.value.precio) <= 0) {
    return editCompError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && Number(editingComp.value.stock) < 0) {
    return editCompError.value = 'El stock no puede ser negativo'
  }

  savingEditComp.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id:             editingComp.value.id,
        especificacion: editingComp.value.especificacion,
        gama:           editingComp.value.gama,
        precio:         editingComp.value.precio,
        stock:          editingComp.value.stock,
      })
    })
    const data = await res.json()
    if (!res.ok) return editCompError.value = data.message ?? 'Error al guardar'
    await fetchComponentes()
    showEditCompModal.value = false
  } catch (e) {
    editCompError.value = 'Error de conexión'
  } finally {
    savingEditComp.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchBodegas()
  fetchCotizaciones()
  fetchComponentes()
})
</script>
