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
          @click="cambiarSeccion(item.id)"
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

      <!-- ─── SECCIÓN: BODEGAS / PROVEEDORES ──────────────── -->
      <section v-if="seccionActiva === 'bodegas'">
        
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-2xl font-bold text-text-primary">Gestión de Proveedores y Bodegas</h2>
            <p class="text-text-muted text-sm mt-1">Administra cuentas de proveedores y visualiza sus bodegas</p>
          </div>
        </div>

        <!-- 1. TABLA DE PROVEEDORES -->
        <div class="mb-10">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-text-primary border-l-4 border-accent pl-3">Cuentas de Proveedores</h3>
            <button class="btn-primary py-2 text-sm" @click="abrirModalCrearProveedor">
              + Nuevo Proveedor
            </button>
          </div>

          <div class="card-dark overflow-x-auto p-0">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-dark-border bg-dark-bg/30">
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium w-16">ID</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Proveedor</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Correo</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Teléfono</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium w-24">Bodegas</th>
                  <th class="px-5 py-3.5 text-right text-text-muted font-medium w-40">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="cargandoProveedores">
                  <td colspan="6" class="px-5 py-8 text-center text-text-muted">
                    <span class="animate-pulse">Cargando proveedores...</span>
                  </td>
                </tr>
                <tr v-else-if="proveedores.length === 0">
                  <td colspan="6" class="px-5 py-8 text-center text-text-muted">
                    No hay proveedores registrados. Crea el primero.
                  </td>
                </tr>
                <tr
                  v-for="prov in proveedores"
                  :key="prov.id"
                  class="border-b border-dark-border hover:bg-dark-bg/50 transition-colors"
                >
                  <td class="px-5 py-3.5 text-text-muted">#{{ prov.id }}</td>
                  <td class="px-5 py-3.5 text-text-primary font-medium">{{ prov.nombre }}</td>
                  <td class="px-5 py-3.5 text-text-muted">{{ prov.correo }}</td>
                  <td class="px-5 py-3.5 text-text-muted">{{ prov.telefono || '—' }}</td>
                  <td class="px-5 py-3.5">
                    <span class="inline-flex items-center justify-center min-w-[1.5rem] bg-accent/15 text-accent border border-accent/30 text-xs font-semibold px-2.5 py-1 rounded-full">
                      {{ prov.total_bodegas || 0 }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5">
                    <div class="flex justify-end gap-2">
                      <button class="btn-ghost text-xs px-3 py-1.5" @click="abrirModalEditarProveedor(prov)">
                        Editar
                      </button>
                      <button class="text-xs px-3 py-1.5 rounded-lg border border-red-500/40 text-red-400 hover:bg-red-500/10 transition-colors" @click="eliminarProveedor(prov)">
                        Eliminar
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 2. TABLA DE BODEGAS (Global) -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-text-primary border-l-4 border-blue-500 pl-3">Bodegas (creadas por Proveedores)</h3>
            <div class="flex gap-4 text-sm">
               <span class="text-text-muted">Total: <b class="text-text-primary">{{bodegas.length}}</b></span>
               <span class="text-green-400">Activas: <b>{{bodegas.filter(b=>b.activa).length}}</b></span>
               <span class="text-red-400">Inactivas: <b>{{bodegas.filter(b=>!b.activa).length}}</b></span>
            </div>
          </div>

          <div class="card-dark overflow-x-auto p-0 border-blue-500/20 shadow-[0_0_15px_rgba(59,130,246,0.05)]">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-dark-border bg-dark-bg/30">
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium w-16">ID</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Proveedor Dueño</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Nombre Bodega</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium">Correo Bodega</th>
                  <th class="px-5 py-3.5 text-left text-text-muted font-medium w-24">Estado</th>
                  <th class="px-5 py-3.5 text-right text-text-muted font-medium w-48">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="cargandoBodegas">
                  <td colspan="6" class="px-5 py-8 text-center text-text-muted">
                    <span class="animate-pulse">Cargando bodegas...</span>
                  </td>
                </tr>
                <tr v-else-if="bodegas.length === 0">
                  <td colspan="6" class="px-5 py-8 text-center text-text-muted">
                    Ningún proveedor ha creado bodegas todavía.
                  </td>
                </tr>
                <tr
                  v-for="bodega in bodegas"
                  :key="bodega.id"
                  class="border-b border-dark-border hover:bg-dark-bg/50 transition-colors"
                >
                  <td class="px-5 py-3.5 text-text-muted">#{{ bodega.id }}</td>
                  <td class="px-5 py-3.5"><span class="text-blue-400 font-medium bg-blue-500/10 px-2 py-1 rounded">{{ bodega.proveedor_nombre || bodega.proveedor_id || '—' }}</span></td>
                  <td class="px-5 py-3.5 text-text-primary font-medium">{{ bodega.nombre }}</td>
                  <td class="px-5 py-3.5 text-text-muted">{{ bodega.correo }}</td>
                  <td class="px-5 py-3.5">
                    <span :class="bodega.activa ? 'bg-green-500/15 text-green-400 border border-green-500/30' : 'bg-red-500/15 text-red-400 border border-red-500/30'" class="inline-flex items-center whitespace-nowrap text-xs font-semibold px-2.5 py-1 rounded-full">
                      {{ bodega.activa ? '● Activa' : '● Inactiva' }}
                    </span>
                  </td>
                  <td class="px-5 py-3.5">
                    <div class="flex justify-end gap-2">
                      <button class="btn-ghost text-xs px-3 py-1.5" @click="abrirModalEditarBodega(bodega)">
                        Editar
                      </button>
                      <button v-if="bodega.activa" class="text-xs px-3 py-1.5 rounded-lg border border-red-500/40 text-red-400 hover:bg-red-500/10 transition-colors" @click="abrirModalDesactivar(bodega)">
                        Desactivar
                      </button>
                      <button v-else class="text-xs px-3 py-1.5 rounded-lg border border-green-500/40 text-green-400 hover:bg-green-500/10 transition-colors" @click="activarBodega(bodega)">
                        Activar
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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
         MODAL — CREAR PROVEEDOR
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modalCrearProv.visible" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="!modalCrearProv.loading && (modalCrearProv.visible = false)">
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-text-primary mb-5">👤 Nuevo Proveedor</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Nombre *</label>
                <input v-model="modalCrearProv.form.nombre" type="text" class="input-field" placeholder="Proveedor ABC" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Correo *</label>
                <input v-model="modalCrearProv.form.correo" type="email" class="input-field" placeholder="proveedor@ejemplo.com" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Teléfono</label>
                <input v-model="modalCrearProv.form.telefono" type="text" class="input-field" placeholder="300 123 4567" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Contraseña *</label>
                <input v-model="modalCrearProv.form.password" type="password" class="input-field" placeholder="••••••••" />
              </div>
            </div>

            <div v-if="modalCrearProv.error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ modalCrearProv.error }}
            </div>

            <div class="flex gap-3 justify-end mt-6">
              <button class="btn-ghost" @click="modalCrearProv.visible = false" :disabled="modalCrearProv.loading">Cancelar</button>
              <button class="btn-primary" @click="crearProveedor" :disabled="modalCrearProv.loading">
                {{ modalCrearProv.loading ? 'Creando...' : 'Crear Proveedor' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════
         MODAL — EDITAR PROVEEDOR
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modalEditarProv.visible" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="!modalEditarProv.loading && (modalEditarProv.visible = false)">
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-text-primary mb-5">✏️ Editar Proveedor</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Nombre *</label>
                <input v-model="modalEditarProv.form.nombre" type="text" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Correo *</label>
                <input v-model="modalEditarProv.form.correo" type="email" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Teléfono</label>
                <input v-model="modalEditarProv.form.telefono" type="text" class="input-field" />
              </div>
            </div>

            <div v-if="modalEditarProv.error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ modalEditarProv.error }}
            </div>

            <div class="flex gap-3 justify-end mt-6">
              <button class="btn-ghost" @click="modalEditarProv.visible = false" :disabled="modalEditarProv.loading">Cancelar</button>
              <button class="btn-primary" @click="editarProveedor" :disabled="modalEditarProv.loading">
                {{ modalEditarProv.loading ? 'Guardando...' : 'Guardar Cambios' }}
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
        <div v-if="modalEditarBodega.visible" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="!modalEditarBodega.loading && (modalEditarBodega.visible = false)">
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">
            <h3 class="text-lg font-bold text-text-primary mb-5">✏️ Editar Bodega</h3>

            <div class="space-y-4">
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Nombre *</label>
                <input v-model="modalEditarBodega.form.nombre" type="text" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Correo *</label>
                <input v-model="modalEditarBodega.form.correo" type="email" class="input-field" />
              </div>
              <div>
                <label class="block text-xs text-text-muted mb-1.5 font-medium uppercase tracking-wide">Teléfono</label>
                <input v-model="modalEditarBodega.form.telefono" type="text" class="input-field" />
              </div>
            </div>

            <div v-if="modalEditarBodega.error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
              {{ modalEditarBodega.error }}
            </div>

            <div class="flex gap-3 justify-end mt-6">
              <button class="btn-ghost" @click="modalEditarBodega.visible = false" :disabled="modalEditarBodega.loading">Cancelar</button>
              <button class="btn-primary" @click="editarBodega" :disabled="modalEditarBodega.loading">
                {{ modalEditarBodega.loading ? 'Guardando...' : 'Guardar Cambios' }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ═══════════════════════════════════════
         MODAL — DESACTIVAR BODEGA
    ════════════════════════════════════════ -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="modalDesactivar.visible" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4" @click.self="!modalDesactivar.loading && (modalDesactivar.visible = false)">
          <div class="bg-dark-card border border-dark-border rounded-2xl w-full max-w-md p-6 shadow-2xl">

            <div class="flex items-center gap-3 mb-4">
              <div class="w-11 h-11 rounded-full bg-red-500/15 border border-red-500/30 flex items-center justify-center text-2xl flex-shrink-0">⚠️</div>
              <div>
                <h3 class="text-lg font-bold text-text-primary">Desactivar Bodega</h3>
                <p class="text-xs text-text-muted">Esta acción no se puede deshacer fácilmente</p>
              </div>
            </div>

            <div class="bg-dark-bg border border-dark-border rounded-lg px-4 py-3 mb-4">
              <p class="text-xs text-text-muted mb-0.5">Bodega seleccionada</p>
              <p class="text-text-primary font-semibold">{{ modalDesactivar.bodega?.nombre }}</p>
            </div>

            <div class="space-y-2 mb-5">
              <div class="flex items-start gap-2 text-sm"><span class="text-red-400 mt-0.5 flex-shrink-0">●</span><span class="text-text-muted">La bodega quedará <strong class="text-red-400">inactiva</strong>.</span></div>
              <div class="flex items-start gap-2 text-sm"><span class="text-blue-400 mt-0.5 flex-shrink-0">●</span><span class="text-text-muted">El historial e inventario se <strong class="text-text-primary">conservará</strong> intacto.</span></div>
              <div class="flex items-start gap-2 text-sm"><span class="text-yellow-400 mt-0.5 flex-shrink-0">●</span><span class="text-text-muted">Solo posible si la bodega <strong class="text-yellow-400">no tiene stock disponible</strong>.</span></div>
            </div>

            <Transition name="fade">
              <div v-if="modalDesactivar.error" class="mb-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm flex items-start gap-2">
                <span class="flex-shrink-0 mt-0.5">🚫</span><span>{{ modalDesactivar.error }}</span>
              </div>
            </Transition>

            <div class="flex gap-3 justify-end">
              <button class="btn-ghost" @click="modalDesactivar.visible = false" :disabled="modalDesactivar.loading">Cancelar</button>
              <button class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed" @click="confirmarDesactivar" :disabled="modalDesactivar.loading">
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
        <div v-if="toast.visible" :class="toast.tipo === 'error' ? 'bg-red-600' : 'bg-green-600'" class="fixed bottom-6 right-6 z-50 px-5 py-3 rounded-xl shadow-2xl text-white text-sm font-medium flex items-center gap-2">
          <span>{{ toast.tipo === 'error' ? '✗' : '✓' }}</span><span>{{ toast.mensaje }}</span>
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
  { id: 'bodegas',           icon: '🏭', label: 'Bodegas'           },
  { id: 'componentes',       icon: '🔧', label: 'Componentes'       },
  { id: 'cotizaciones',      icon: '📋', label: 'Cotizaciones'      },
  { id: 'crear-usuario',     icon: '➕', label: 'Crear Usuario'     },
  { id: 'gestionar-usuarios',icon: '👥', label: 'Gestionar Usuarios'},
]
const seccionActiva = ref('bodegas')

// ── Estado ───────────────────────────────────────────────
const proveedores        = ref([])
const cargandoProveedores = ref(false)
const bodegas            = ref([])
const cargandoBodegas    = ref(false)

// ── Modales Proveedores ──────────────────────────────────
const modalCrearProv = ref({
  visible: false, loading: false, error: '',
  form: { nombre: '', correo: '', telefono: '', password: '' }
})

const modalEditarProv = ref({
  visible: false, loading: false, error: '',
  form: { id: null, nombre: '', correo: '', telefono: '' }
})

// ── Modales Bodegas ──────────────────────────────────────
const modalEditarBodega = ref({
  visible: false, loading: false, error: '',
  form: { id: null, nombre: '', correo: '', telefono: '' }
})

const modalDesactivar = ref({
  visible: false, loading: false, error: '',
  bodega: null
})

const toast = ref({ visible: false, mensaje: '', tipo: 'ok' })

// ── Inicio ───────────────────────────────────────────────
onMounted(() => {
  cargarProveedores()
  cargarBodegas()
})

function cambiarSeccion(id) {
  seccionActiva.value = id
  if (id === 'bodegas') {
    if (proveedores.value.length === 0) cargarProveedores()
    if (bodegas.value.length === 0) cargarBodegas()
  }
}

// ════════════════════════════════════════════════════════
// PROVEEDORES
// ════════════════════════════════════════════════════════

async function cargarProveedores() {
  cargandoProveedores.value = true
  try {
    const res  = await fetch(`${API}/admin-proveedores/`, {
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    proveedores.value = Array.isArray(data) ? data : []
  } catch {
    mostrarToast('Error al cargar proveedores', 'error')
  } finally {
    cargandoProveedores.value = false
  }
}

function abrirModalCrearProveedor() {
  modalCrearProv.value = {
    visible: true, loading: false, error: '',
    form: { nombre: '', correo: '', telefono: '', password: '' }
  }
}

async function crearProveedor() {
  const { form } = modalCrearProv.value
  if (!form.nombre || !form.correo || !form.password) {
    modalCrearProv.value.error = 'Nombre, correo y contraseña son obligatorios.'
    return
  }
  modalCrearProv.value.loading = true
  modalCrearProv.value.error   = ''
  try {
    const res = await fetch(`${API}/admin-proveedores/`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(form)
    })
    const data = await res.json()
    if (!res.ok) { modalCrearProv.value.error = data.error || 'No se pudo crear.'; return }
    modalCrearProv.value.visible = false
    await cargarProveedores()
    mostrarToast('Proveedor creado correctamente.')
  } catch {
    modalCrearProv.value.error = 'Error de conexión.'
  } finally {
    modalCrearProv.value.loading = false
  }
}

function abrirModalEditarProveedor(prov) {
  modalEditarProv.value = {
    visible: true, loading: false, error: '',
    form: { id: prov.id, nombre: prov.nombre, correo: prov.correo, telefono: prov.telefono || '' }
  }
}

async function editarProveedor() {
  const { form } = modalEditarProv.value
  if (!form.nombre || !form.correo) {
    modalEditarProv.value.error = 'Nombre y correo son obligatorios.'
    return
  }
  modalEditarProv.value.loading = true
  modalEditarProv.value.error   = ''
  try {
    const res = await fetch(`${API}/admin-proveedores/`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(form)
    })
    const data = await res.json()
    if (!res.ok) { modalEditarProv.value.error = data.error || 'No se pudo actualizar.'; return }
    modalEditarProv.value.visible = false
    const idx = proveedores.value.findIndex(p => p.id === form.id)
    if (idx !== -1) Object.assign(proveedores.value[idx], { nombre: form.nombre, correo: form.correo, telefono: form.telefono })
    
    // Recargar bodegas para actualizar los nombres de proveedores mostrados
    cargarBodegas()
    
    mostrarToast('Proveedor actualizado correctamente.')
  } catch {
    modalEditarProv.value.error = 'Error de conexión.'
  } finally {
    modalEditarProv.value.loading = false
  }
}

async function eliminarProveedor(prov) {
  if (!confirm(`¿Eliminar al proveedor "${prov.nombre}"?`)) return
  try {
    const res = await fetch(`${API}/admin-proveedores/?id=${prov.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) { mostrarToast(data.error || 'No se pudo eliminar.', 'error'); return }
    proveedores.value = proveedores.value.filter(p => p.id !== prov.id)
    // Recargar bodegas para desaparecer las bodegas eliminadas asosciadas a ese proveedor
    cargarBodegas()
    mostrarToast(`Proveedor "${prov.nombre}" eliminado.`)
  } catch {
    mostrarToast('Error de conexión.', 'error')
  }
}

// ════════════════════════════════════════════════════════
// BODEGAS
// ════════════════════════════════════════════════════════

async function cargarBodegas() {
  cargandoBodegas.value = true
  try {
    const res  = await fetch(`${API}/bodegas/`, {
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    bodegas.value = Array.isArray(data) ? data : []
  } catch {
    mostrarToast('Error al cargar bodegas', 'error')
  } finally {
    cargandoBodegas.value = false
  }
}

function abrirModalEditarBodega(bodega) {
  modalEditarBodega.value = {
    visible: true, loading: false, error: '',
    form: { id: bodega.id, nombre: bodega.nombre, correo: bodega.correo, telefono: bodega.telefono || '' }
  }
}

async function editarBodega() {
  const { form } = modalEditarBodega.value
  if (!form.nombre || !form.correo) {
    modalEditarBodega.value.error = 'Nombre y correo son obligatorios.'
    return
  }
  modalEditarBodega.value.loading = true
  modalEditarBodega.value.error   = ''
  try {
    const res = await fetch(`${API}/bodegas/`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(form)
    })
    const data = await res.json()
    if (!res.ok) { modalEditarBodega.value.error = data.error || 'No se pudo actualizar.'; return }
    modalEditarBodega.value.visible = false
    const idx = bodegas.value.findIndex(b => b.id === form.id)
    if (idx !== -1) Object.assign(bodegas.value[idx], { nombre: form.nombre, correo: form.correo, telefono: form.telefono })
    mostrarToast('Bodega actualizada correctamente.')
  } catch {
    modalEditarBodega.value.error = 'Error de conexión.'
  } finally {
    modalEditarBodega.value.loading = false
  }
}

// ── Desactivar ───────────────────────────────────────────
function abrirModalDesactivar(bodega) {
  modalDesactivar.value = { visible: true, loading: false, error: '', bodega }
}

async function confirmarDesactivar() {
  const { bodega } = modalDesactivar.value
  if (!bodega) return
  modalDesactivar.value.loading = true
  modalDesactivar.value.error   = ''
  try {
    const res = await fetch(`${API}/bodegas/?id=${bodega.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      modalDesactivar.value.error   = data.error || 'No se pudo desactivar.'
      modalDesactivar.value.loading = false
      return
    }
    const idx = bodegas.value.findIndex(b => b.id === bodega.id)
    if (idx !== -1) bodegas.value[idx].activa = false
    modalDesactivar.value.visible = false
    mostrarToast(`Bodega "${bodega.nombre}" desactivada correctamente.`)
  } catch {
    modalDesactivar.value.error   = 'Error de conexión. Intenta de nuevo.'
    modalDesactivar.value.loading = false
  }
}

async function activarBodega(bodega) {
  try {
    const res = await fetch(`${API}/bodegas/?id=${bodega.id}`, {
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

// ── Generales ────────────────────────────────────────────
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

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

.input-field {
  @apply w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-text-primary
         focus:outline-none focus:border-accent transition-colors text-sm;
}
</style>
