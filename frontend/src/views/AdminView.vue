<template>
  <div class="min-h-screen bg-dark-bg flex">

    <!-- ═══════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════ -->
    <aside class="w-64 bg-dark-card border-r border-dark-border flex flex-col fixed h-full">

      <!-- Logo -->
      <div class="p-6 border-b border-dark-border">
        <h1 class="text-xl font-bold text-accent">PCMATCH</h1>
        <p class="text-xs text-text-muted mt-1">Panel Administrador</p>
      </div>

      <!-- Info admin -->
      <div class="px-4 py-3 border-b border-dark-border">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold text-sm">
            {{ user?.nombre?.charAt(0) }}
          </div>
          <div>
            <p class="text-text-primary text-sm font-medium">{{ user?.nombre }}</p>
            <p class="text-text-muted text-xs">Administrador</p>
          </div>
        </div>
      </div>

      <!-- Menú -->
      <nav class="flex-1 p-3 space-y-1">
        <button
          v-for="item in secciones"
          :key="item.id"
          @click="seccionActiva = item.id"
          :class="[
            'w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors text-left',
            seccionActiva === item.id
              ? 'bg-accent text-white font-medium'
              : 'text-text-muted hover:bg-dark-bg hover:text-text-primary'
          ]"
        >
          <span class="text-base">{{ item.icon }}</span>
          <span>{{ item.label }}</span>
        </button>
      </nav>

      <!-- Logout -->
      <div class="p-4 border-t border-dark-border">
        <button @click="cerrarSesion" class="btn-ghost w-full text-sm">
          Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- ═══════════════════════════════════════
         CONTENIDO
    ════════════════════════════════════════ -->
    <main class="flex-1 ml-64 p-8">

      <!-- ─── BODEGAS ──────────────────────── -->
      <section v-if="seccionActiva === 'bodegas'">

        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-2xl font-bold text-text-primary">Gestión de Bodegas</h2>
            <p class="text-text-muted text-sm mt-1">Administra las bodegas del sistema</p>
          </div>
          <button class="btn-primary" @click="abrirModalCrear">
            + Nueva Bodega
          </button>
        </div>

        <!-- Stats rápidas -->
        <div class="grid grid-cols-3 gap-4 mb-6">
          <div class="card-dark text-center">
            <p class="text-3xl font-bold text-text-primary">{{ bodegas.length }}</p>
            <p class="text-text-muted text-sm mt-1">Total</p>
          </div>
          <div class="card-dark text-center">
            <p class="text-3xl font-bold text-green-400">{{ bodegas.filter(b => b.activa).length }}</p>
            <p class="text-text-muted text-sm mt-1">Activas</p>
          </div>
          <div class="card-dark text-center">
            <p class="text-3xl font-bold text-red-400">{{ bodegas.filter(b => !b.activa).length }}</p>
            <p class="text-text-muted text-sm mt-1">Inactivas</p>
          </div>
        </div>

        <!-- Tabla -->
        <div class="card-dark overflow-x-auto p-0">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-dark-border">
                <th class="px-5 py-3.5 text-left text-text-muted font-medium">ID</th>
                <th class="px-5 py-3.5 text-left text-text-muted font-medium">Nombre</th>
                <th class="px-5 py-3.5 text-left text-text-muted font-medium">Correo</th>
                <th class="px-5 py-3.5 text-left text-text-muted font-medium">Teléfono</th>
                <th class="px-5 py-3.5 text-left text-text-muted font-medium">Estado</th>
                <th class="px-5 py-3.5 text-right text-text-muted font-medium">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="cargando">
                <td colspan="6" class="px-5 py-12 text-center text-text-muted">
                  <span class="animate-pulse">Cargando bodegas...</span>
                </td>
              </tr>
              <tr v-else-if="bodegas.length === 0">
                <td colspan="6" class="px-5 py-12 text-center text-text-muted">
                  No hay bodegas registradas. Crea la primera.
                </td>
              </tr>
              <tr
                v-for="bodega in bodegas"
                :key="bodega.id"
                class="border-b border-dark-border hover:bg-dark-bg/50 transition-colors"
              >
                <td class="px-5 py-3.5 text-text-muted">#{{ bodega.id }}</td>
                <td class="px-5 py-3.5 text-text-primary font-medium">{{ bodega.nombre }}</td>
                <td class="px-5 py-3.5 text-text-muted">{{ bodega.correo }}</td>
                <td class="px-5 py-3.5 text-text-muted">{{ bodega.telefono || '—' }}</td>
                <td class="px-5 py-3.5">
                  <span :class="bodega.activa
                    ? 'bg-green-500/15 text-green-400 border border-green-500/30'
                    : 'bg-red-500/15 text-red-400 border border-red-500/30'"
                    class="text-xs font-semibold px-2.5 py-1 rounded-full"
                  >
                    {{ bodega.activa ? '● Activa' : '● Inactiva' }}
                  </span>
                </td>
                <td class="px-5 py-3.5">
                  <div class="flex justify-end gap-2">
                    <button
                      class="btn-ghost text-xs px-3 py-1.5"
                      @click="abrirModalEditar(bodega)"
                    >
                      Editar
                    </button>
                    <!-- RF-12: Solo aparece si está activa -->
                    <button
                      v-if="bodega.activa"
                      class="text-xs px-3 py-1.5 rounded-lg border border-red-500/40 text-red-400 hover:bg-red-500/10 transition-colors"
                      @click="abrirModalDesactivar(bodega)"
                    >
                      Desactivar
                    </button>
                    <button
                      v-else
                      class="text-xs px-3 py-1.5 rounded-lg border border-green-500/40 text-green-400 hover:bg-green-500/10 transition-colors"
                      @click="activarBodega(bodega)"
                    >
                      Activar
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- ─── OTRAS SECCIONES ──────────────── -->
      <section v-else class="flex items-center justify-center h-64">
        <div class="text-center">
          <p class="text-4xl mb-3">{{ secciones.find(s => s.id === seccionActiva)?.icon }}</p>
          <h2 class="text-xl font-bold text-text-primary">{{ secciones.find(s => s.id === seccionActiva)?.label }}</h2>
          <p class="text-text-muted mt-2">Sección en construcción</p>
        </div>
      </section>

    </main>

    <!-- ═══════════════════════════════════════
         MODAL — CREAR BODEGA
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="modalCrear.visible"
          class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
          @click.self="!modalCrear.loading && (modalCrear.visible = false)"
        >
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-text-primary mb-5">🏭 Nueva Bodega</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Nombre *</label>
                <input v-model="modalCrear.form.nombre" type="text"
                  class="input-field" placeholder="TechStore Norte" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Correo *</label>
                <input v-model="modalCrear.form.correo" type="email"
                  class="input-field" placeholder="bodega@ejemplo.com" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Teléfono</label>
                <input v-model="modalCrear.form.telefono" type="text"
                  class="input-field" placeholder="300 123 4567" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Contraseña *</label>
                <input v-model="modalCrear.form.password" type="password"
                  class="input-field" placeholder="••••••••" />
              </div>
            </div>

            <div v-if="modalCrear.error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ modalCrear.error }}
            </div>

            <div class="flex gap-3 justify-end mt-6">
              <button class="btn-ghost" @click="modalCrear.visible = false" :disabled="modalCrear.loading">
                Cancelar
              </button>
              <button class="btn-primary" @click="crearBodega" :disabled="modalCrear.loading">
                {{ modalCrear.loading ? 'Creando...' : 'Crear Bodega' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════
         MODAL — EDITAR BODEGA
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="modalEditar.visible"
          class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
          @click.self="!modalEditar.loading && (modalEditar.visible = false)"
        >
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-text-primary mb-5">✏️ Editar Bodega</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Nombre *</label>
                <input v-model="modalEditar.form.nombre" type="text" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Correo *</label>
                <input v-model="modalEditar.form.correo" type="email" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Teléfono</label>
                <input v-model="modalEditar.form.telefono" type="text" class="input-field" />
              </div>
            </div>

            <div v-if="modalEditar.error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ modalEditar.error }}
            </div>

            <div class="flex gap-3 justify-end mt-6">
              <button class="btn-ghost" @click="modalEditar.visible = false" :disabled="modalEditar.loading">
                Cancelar
              </button>
              <button class="btn-primary" @click="editarBodega" :disabled="modalEditar.loading">
                {{ modalEditar.loading ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════
         MODAL — DESACTIVAR (RF-12)
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="modalDesactivar.visible"
          class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4"
          @click.self="!modalDesactivar.loading && (modalDesactivar.visible = false)"
        >
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-4">
              <div class="w-11 h-11 rounded-full bg-red-500/15 border border-red-500/30 flex items-center justify-center text-2xl flex-shrink-0">
                ⚠️
              </div>
              <div>
                <h3 class="text-lg font-bold text-text-primary">Desactivar Bodega</h3>
                <p class="text-xs text-text-muted">Esta acción no se puede deshacer fácilmente</p>
              </div>
            </div>

            <!-- Nombre de la bodega destacado -->
            <div class="bg-dark-bg border border-dark-border rounded-lg px-4 py-3 mb-4">
              <p class="text-xs text-text-muted mb-0.5">Bodega seleccionada</p>
              <p class="text-text-primary font-semibold">{{ modalDesactivar.bodega?.nombre }}</p>
            </div>

            <!-- Consecuencias -->
            <div class="space-y-2 mb-5">
              <div class="flex items-start gap-2 text-sm">
                <span class="text-red-400 mt-0.5 flex-shrink-0">●</span>
                <span class="text-text-muted">La bodega quedará <strong class="text-red-400">inactiva</strong> y no podrá operar.</span>
              </div>
              <div class="flex items-start gap-2 text-sm">
                <span class="text-blue-400 mt-0.5 flex-shrink-0">●</span>
                <span class="text-text-muted">El historial e inventario se <strong class="text-text-primary">conservará</strong> intacto.</span>
              </div>
              <div class="flex items-start gap-2 text-sm">
                <span class="text-yellow-400 mt-0.5 flex-shrink-0">●</span>
                <span class="text-text-muted">Solo posible si la bodega <strong class="text-yellow-400">no tiene stock disponible</strong>.</span>
              </div>
            </div>

            <!-- Error (RN02 u otro) -->
            <Transition name="fade">
              <div
                v-if="modalDesactivar.error"
                class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-start gap-2"
              >
                <span class="flex-shrink-0 mt-0.5">🚫</span>
                <span>{{ modalDesactivar.error }}</span>
              </div>
            </Transition>

            <div class="flex gap-3 justify-end">
              <button
                class="btn-ghost"
                @click="modalDesactivar.visible = false"
                :disabled="modalDesactivar.loading"
              >
                Cancelar
              </button>
              <button
                class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @click="confirmarDesactivar"
                :disabled="modalDesactivar.loading"
              >
                {{ modalDesactivar.loading ? 'Desactivando...' : 'Sí, desactivar' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════
         TOAST
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="toast">
        <div
          v-if="toast.visible"
          :class="toast.tipo === 'error' ? 'bg-red-600' : 'bg-green-600'"
          class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-2xl text-white text-sm font-medium flex items-center gap-2"
        >
          <span>{{ toast.tipo === 'error' ? '✗' : '✓' }}</span>
          <span>{{ toast.mensaje }}</span>
        </div>
      </Transition>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const { getToken, logout, user } = useAuth()

const API = 'http://localhost/pcmatch/backend/api'

// ── Sidebar ─────────────────────────────────────────────
const secciones = [
  { id: 'bodegas',            icon: '🏭', label: 'Bodegas'            },
  { id: 'componentes',        icon: '🔧', label: 'Componentes'        },
  { id: 'cotizaciones',       icon: '📋', label: 'Cotizaciones'       },
  { id: 'crear-usuario',      icon: '➕', label: 'Crear Usuario'      },
  { id: 'gestionar-usuarios', icon: '👥', label: 'Gestionar Usuarios' },
]
const seccionActiva = ref('bodegas')

// ── Estado ───────────────────────────────────────────────
const bodegas = ref([])
const cargando = ref(false)

// ── Modales ──────────────────────────────────────────────
const modalCrear = ref({
  visible: false, loading: false, error: '',
  form: { nombre: '', correo: '', telefono: '', password: '' }
})

const modalEditar = ref({
  visible: false, loading: false, error: '',
  form: { id: null, nombre: '', correo: '', telefono: '' }
})

const modalDesactivar = ref({
  visible: false, loading: false, error: '',
  bodega: null
})

const toast = ref({ visible: false, mensaje: '', tipo: 'ok' })

// ── Inicio ───────────────────────────────────────────────
onMounted(() => cargarBodegas())

// ════════════════════════════════════════════════════════
// FUNCIONES
// ════════════════════════════════════════════════════════

async function cargarBodegas() {
  cargando.value = true
  try {
    const res  = await fetch(`${API}/bodegas/`, {
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    bodegas.value = Array.isArray(data) ? data : []
  } catch {
    mostrarToast('Error al cargar bodegas', 'error')
  } finally {
    cargando.value = false
  }
}

// ── Crear ────────────────────────────────────────────────
function abrirModalCrear() {
  modalCrear.value = {
    visible: true, loading: false, error: '',
    form: { nombre: '', correo: '', telefono: '', password: '' }
  }
}

async function crearBodega() {
  const { form } = modalCrear.value
  if (!form.nombre || !form.correo || !form.password) {
    modalCrear.value.error = 'Nombre, correo y contraseña son obligatorios.'
    return
  }
  modalCrear.value.loading = true
  modalCrear.value.error   = ''
  try {
    const res  = await fetch(`${API}/bodegas/`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(form)
    })
    const data = await res.json()
    if (!res.ok) { modalCrear.value.error = data.error || 'No se pudo crear.'; return }
    modalCrear.value.visible = false
    await cargarBodegas()
    mostrarToast('Bodega creada correctamente.')
  } catch {
    modalCrear.value.error = 'Error de conexión.'
  } finally {
    modalCrear.value.loading = false
  }
}

// ── Editar ───────────────────────────────────────────────
function abrirModalEditar(bodega) {
  modalEditar.value = {
    visible: true, loading: false, error: '',
    form: { id: bodega.id, nombre: bodega.nombre, correo: bodega.correo, telefono: bodega.telefono || '' }
  }
}

async function editarBodega() {
  const { form } = modalEditar.value
  if (!form.nombre || !form.correo) {
    modalEditar.value.error = 'Nombre y correo son obligatorios.'
    return
  }
  modalEditar.value.loading = true
  modalEditar.value.error   = ''
  try {
    const res  = await fetch(`${API}/bodegas/`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(form)
    })
    const data = await res.json()
    if (!res.ok) { modalEditar.value.error = data.error || 'No se pudo actualizar.'; return }
    modalEditar.value.visible = false
    const idx = bodegas.value.findIndex(b => b.id === form.id)
    if (idx !== -1) Object.assign(bodegas.value[idx], { nombre: form.nombre, correo: form.correo, telefono: form.telefono })
    mostrarToast('Bodega actualizada correctamente.')
  } catch {
    modalEditar.value.error = 'Error de conexión.'
  } finally {
    modalEditar.value.loading = false
  }
}

// ── Desactivar (RF-12) ───────────────────────────────────
function abrirModalDesactivar(bodega) {
  modalDesactivar.value = { visible: true, loading: false, error: '', bodega }
}

async function confirmarDesactivar() {
  const { bodega } = modalDesactivar.value
  if (!bodega) return
  modalDesactivar.value.loading = true
  modalDesactivar.value.error   = ''
  try {
    const res  = await fetch(`${API}/bodegas/?id=${bodega.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      modalDesactivar.value.error   = data.error || 'No se pudo desactivar.'
      modalDesactivar.value.loading = false
      return
    }
    // RN01: actualizar local sin recargar
    const idx = bodegas.value.findIndex(b => b.id === bodega.id)
    if (idx !== -1) bodegas.value[idx].activa = false
    modalDesactivar.value.visible = false
    mostrarToast(`Bodega "${bodega.nombre}" desactivada correctamente.`)
  } catch {
    modalDesactivar.value.error   = 'Error de conexión. Intenta de nuevo.'
    modalDesactivar.value.loading = false
  }
}

// ── Activar ──────────────────────────────────────────────
async function activarBodega(bodega) {
  try {
    const res  = await fetch(`${API}/bodegas/?id=${bodega.id}`, {
      method: 'PATCH',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) { mostrarToast(data.error || 'No se pudo activar.', 'error'); return }
    const idx = bodegas.value.findIndex(b => b.id === bodega.id)
    if (idx !== -1) bodegas.value[idx].activa = true
    mostrarToast(`Bodega "${bodega.nombre}" activada correctamente.`)
  } catch {
    mostrarToast('Error de conexión.', 'error')
  }
}


function cerrarSesion() {
  logout()
  router.push('/login')
}

// ── Toast ────────────────────────────────────────────────
function mostrarToast(mensaje, tipo = 'ok') {
  toast.value = { visible: true, mensaje, tipo }
  setTimeout(() => { toast.value.visible = false }, 3500)
}
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s, transform 0.2s; }
.modal-enter-from, .modal-leave-to       { opacity: 0; transform: scale(0.95); }

.toast-enter-active, .toast-leave-active { transition: opacity 0.3s, transform 0.3s; }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(10px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.input-field {
  @apply w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-text-primary
         focus:outline-none focus:border-accent transition-colors text-sm;
}
</style>
