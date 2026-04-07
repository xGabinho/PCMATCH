<template>
  <div class="min-h-screen bg-dark-bg flex">

    <!-- ═══════════════════════════════════════
         SIDEBAR
    ════════════════════════════════════════ -->
    <aside class="w-64 bg-dark-card border-r border-dark-border flex flex-col fixed h-full">

      <!-- Logo -->
      <div class="p-6 border-b border-dark-border">
        <h1 class="text-xl font-bold text-accent">PCMATCH</h1>
        <p class="text-xs text-text-muted mt-1">Panel Proveedor</p>
      </div>

      <!-- Info proveedor -->
      <div class="px-4 py-3 border-b border-dark-border">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-accent font-bold text-sm">
            {{ user?.nombre?.charAt(0) }}
          </div>
          <div>
            <p class="text-text-primary text-sm font-medium">{{ user?.nombre }}</p>
            <p class="text-text-muted text-xs">Proveedor</p>
          </div>
        </div>
      </div>

      <!-- Menú -->
      <nav class="flex-1 p-3 space-y-1">
        <button
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors text-left bg-accent text-white font-medium"
        >
          <span class="text-base">🏭</span>
          <span>Mis Bodegas</span>
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

      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-text-primary">Mis Bodegas</h2>
          <p class="text-text-muted text-sm mt-1">Bodegas asignadas a tu cuenta</p>
        </div>
        <button class="btn-primary" @click="abrirModalCrear">
          + Nueva Bodega
        </button>
      </div>

      <!-- Stats -->
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
            </tr>
          </thead>
          <tbody>
            <tr v-if="cargando">
              <td colspan="5" class="px-5 py-12 text-center text-text-muted">
                <span class="animate-pulse">Cargando bodegas...</span>
              </td>
            </tr>
            <tr v-else-if="bodegas.length === 0">
              <td colspan="5" class="px-5 py-12 text-center text-text-muted">
                No tienes bodegas. ¡Crea tu primera!
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
                  class="inline-flex items-center whitespace-nowrap text-xs font-semibold px-2.5 py-1 rounded-full"
                >
                  {{ bodega.activa ? '● Activa' : '● Inactiva' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

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
                  class="input-field" placeholder="Mi Bodega Norte" />
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

const bodegas  = ref([])
const cargando = ref(false)

const modalCrear = ref({
  visible: false, loading: false, error: '',
  form: { nombre: '', correo: '', telefono: '', password: '' }
})

const toast = ref({ visible: false, mensaje: '', tipo: 'ok' })

onMounted(() => cargarBodegas())

async function cargarBodegas() {
  cargando.value = true
  try {
    const res  = await fetch(`${API}/proveedores/`, {
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    bodegas.value = Array.isArray(data) ? data : []
  } catch {
    bodegas.value = []
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
    const res  = await fetch(`${API}/proveedores/`, {
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

function cerrarSesion() {
  logout()
  router.push('/login')
}

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

.input-field {
  @apply w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-text-primary
         focus:outline-none focus:border-accent transition-colors text-sm;
}
</style>
