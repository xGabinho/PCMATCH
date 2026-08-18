<template>
  <main class="max-w-4xl mx-auto px-6 pt-28 pb-12">

    <!-- Sin componentes -->
    <div v-if="Object.keys(selectedComponents).length === 0" class="text-center py-24">
      <Monitor class="w-16 h-16 mx-auto mb-4 text-accent/50" />
      <p class="theme-text font-semibold text-lg mb-2">No hay componentes seleccionados</p>
      <p class="theme-text-muted text-sm mb-6">Vuelve al armador y selecciona los componentes de tu PC</p>
      <router-link to="/armar" class="btn-primary text-sm">← Ir al armador</router-link>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 mb-10">
        <div>
          <p class="text-accent text-sm font-medium uppercase tracking-widest mb-2">Cotización</p>
          <h1 class="text-3xl font-bold theme-text">Resumen de tu PC</h1>
          <p class="theme-text-muted mt-2">Generado el {{ today }}</p>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <router-link to="/armar" class="btn-secondary text-sm flex-1 sm:flex-none text-center">← Editar build</router-link>
          <button @click="saveCotizacion" :disabled="saving" class="btn-primary text-sm flex-1 sm:flex-none flex items-center justify-center gap-2">
            <template v-if="saving">Guardando...</template>
            <template v-else><Save class="w-4 h-4" /> Guardar cotización</template>
          </button>
        </div>
      </div>

      <!-- Success -->
      <div v-if="saveSuccess" class="mb-6 px-4 py-3 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
        <p class="font-bold mb-1 flex items-center gap-1"><Check class="w-4 h-4" /> Cotización guardada correctamente</p>
        <p>Tu código de cotización es: <strong class="font-mono text-white">{{ generatedCode }}</strong></p>
        <p class="mt-1 opacity-80">Hemos enviado un correo con el PDF detallado de tu cotización.</p>
      </div>
      <div v-if="saveError" class="mb-6 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
        {{ saveError }}
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Components List -->
        <div class="lg:col-span-2 space-y-3">
          <div class="card-dark rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Componentes seleccionados</h2>
              <span class="badge bg-accent/10 text-accent border border-accent/20">{{ Object.keys(selectedComponents).length }} componentes</span>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-dark-border">
              <div
                v-for="(item, stepId) in selectedComponents"
                :key="stepId"
                class="px-5 py-4 flex items-center gap-4 hover:bg-black/5 dark:hover:bg-white/5 transition-colors group"
              >
                <!-- Icon -->
                <div class="w-9 h-9 rounded-lg bg-accent/10 flex items-center justify-center text-accent text-sm flex-shrink-0">
                  <component :is="item.step.icon" class="w-5 h-5" />
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-xs theme-text-muted uppercase tracking-wide mb-0.5">{{ item.step.label }}</p>
                  <p class="text-sm font-medium theme-text">{{ item.nombre }}</p>
                  <p class="text-xs theme-text-muted mt-0.5">{{ item.especificacion }}</p>
                </div>

                <!-- Store + Price + Qty + Remove -->
                <div class="flex-shrink-0 flex flex-col items-end gap-2">
                  <button
                    @click="handleRemove(stepId)"
                    class="theme-text-muted hover:text-red-500 transition-colors opacity-100 lg:opacity-0 lg:group-hover:opacity-100 text-xs"
                    title="Quitar componente"
                  >✕ quitar</button>
                  <p class="text-xs theme-text-muted">{{ item.bodega }}</p>
                  <div class="flex items-center gap-1.5">
                    <button
                      @click="updateQuantity(stepId, (item.cantidad || 1) - 1)"
                      :disabled="(item.cantidad || 1) <= 1"
                      class="w-6 h-6 rounded border theme-border bg-white dark:bg-dark-bg theme-text-muted hover:text-red-500 hover:border-red-500/40 transition-colors flex items-center justify-center text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                    >−</button>
                    <input
                      type="number"
                      :value="item.cantidad || 1"
                      @change="updateQuantity(stepId, parseInt($event.target.value) || 1)"
                      min="1"
                      :max="item.stock || 999"
                      class="w-10 h-6 theme-bg border theme-border rounded text-center text-xs font-mono theme-text focus:outline-none focus:border-accent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                    />
                    <button
                      @click="updateQuantity(stepId, (item.cantidad || 1) + 1)"
                      :disabled="(item.cantidad || 1) >= (item.stock || 999)"
                      class="w-6 h-6 rounded border theme-border bg-white dark:bg-dark-bg theme-text-muted hover:text-green-500 hover:border-green-500/40 transition-colors flex items-center justify-center text-xs font-bold disabled:opacity-30 disabled:cursor-not-allowed"
                    >+</button>
                  </div>
                  <div class="flex flex-col items-end">
                    <div v-if="item.descuento_activo && item.descuento_porcentaje > 0" class="flex items-center gap-1.5 mb-0.5">
                      <span class="line-through text-[10px] theme-text-muted opacity-70">${{ (Number(item.precio) * (item.cantidad || 1)).toLocaleString() }}</span>
                      <span class="text-[10px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold">-{{ item.descuento_porcentaje }}%</span>
                    </div>
                    <p class="text-accent font-semibold font-mono text-sm">${{ (Number(item.precio_final || item.precio) * (item.cantidad || 1)).toLocaleString() }}</p>
                  </div>
                  <p v-if="(item.cantidad || 1) > 1" class="text-[10px] theme-text-muted">${{ Number(item.precio_final || item.precio).toLocaleString() }} × {{ item.cantidad }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Componentes faltantes -->
          <div v-if="missingSteps.length > 0" class="card-dark rounded-xl p-5">
            <h3 class="font-semibold theme-text-muted text-sm mb-3">Sin seleccionar</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="step in missingSteps"
                :key="step.id"
                @click="goToStep(step)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed theme-border theme-text-muted hover:border-accent hover:text-accent transition-all text-xs bg-white/50 dark:bg-transparent"
              >
                <component :is="step.icon" class="w-4 h-4" /> {{ step.label }} →
              </button>
            </div>
          </div>

          <!-- Store breakdown -->
          <div class="card-dark rounded-xl p-5">
            <h3 class="font-semibold theme-text mb-4">Por bodega</h3>
            <div class="space-y-3">
              <div v-for="store in storeBreakdown" :key="store.name" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-accent"></div>
                  <span class="theme-text text-sm">{{ store.name }}</span>
                  <span class="text-xs theme-text-muted">({{ store.count }} producto{{ store.count > 1 ? 's' : '' }})</span>
                </div>
                <span class="text-sm font-medium theme-text font-mono">${{ store.total.toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Price Summary -->
        <div class="space-y-4">
          <div class="card-dark rounded-xl p-6">
            <p class="text-sm theme-text-muted mb-1">Total estimado</p>
            <p class="text-4xl font-bold text-accent font-mono">${{ totalPrice.toLocaleString() }}</p>
            <p class="text-xs theme-text-muted mt-2">Precio referencial · No incluye instalación</p>

            <div class="mt-4 pt-4 border-t theme-border space-y-2">
              <div class="flex justify-between text-sm">
                <span class="theme-text-muted">Componentes</span>
                <span class="theme-text font-mono">{{ Object.keys(selectedComponents).length }} / {{ steps.length }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="theme-text-muted">Bodegas</span>
                <span class="theme-text font-mono">{{ storeBreakdown.length }}</span>
              </div>
            </div>
          </div>

          <div class="card-dark rounded-xl p-5 space-y-3">
            <h3 class="font-semibold theme-text text-sm mb-1">Acciones</h3>
            <button @click="saveCotizacion" :disabled="saving" class="btn-primary w-full text-sm py-3 flex items-center justify-center gap-2">
              <svg v-if="saving" class="animate-spin h-4 w-4 text-white" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              <template v-if="saving">Guardando...</template>
              <template v-else><Save class="w-4 h-4" /> Guardar cotización</template>
            </button>
            <router-link to="/armar" class="btn-secondary w-full text-sm text-center block">
              ← Volver al armador
            </router-link>
            <button @click="clearAll(); $router.push('/armar')" class="w-full text-sm py-2 rounded-lg theme-text-muted hover:text-red-500 hover:bg-red-500/10 border theme-border transition-colors flex items-center justify-center gap-2">
              <Trash2 class="w-4 h-4" /> Empezar de nuevo
            </button>
          </div>

          <div class="rounded-xl border border-amber-500/30 bg-amber-50 dark:bg-yellow-500/5 p-4">
            <p class="text-amber-700 dark:text-yellow-400 text-xs font-medium mb-1 flex items-center gap-1"><AlertTriangle class="w-3 h-3" /> Nota importante</p>
            <p class="text-amber-600/80 dark:text-text-muted text-xs leading-relaxed">
              Los precios son referenciales y pueden variar. Verifica disponibilidad antes de comprar.
            </p>
          </div>
        </div>

      </div>
    </template>
  </main>
</template>

<script setup>
import { Monitor, Save, Check, Trash2, AlertTriangle } from 'lucide-vue-next';

import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'

import { useBuilder } from '../composables/useBuilder'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

import { API } from '@/config/api'
const router = useRouter()
const toast = useToast()
const { steps, selectedComponents, totalPrice, perfil, removeItem, clearAll, updateQuantity } = useBuilder()
const { getToken } = useAuth()

const saving      = ref(false)
const saveSuccess = ref(false)
const saveError   = ref('')
const generatedCode = ref('')

const today = new Date().toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' })

const missingSteps = computed(() =>
  steps.filter(s => !selectedComponents.value[s.id])
)

const storeBreakdown = computed(() => {
  const map = {}
  Object.values(selectedComponents.value).forEach(item => {
    const qty = item.cantidad || 1
    if (!map[item.bodega]) map[item.bodega] = { name: item.bodega, count: 0, total: 0 }
    map[item.bodega].count += qty
    map[item.bodega].total += Number(item.precio_final || item.precio) * qty
  })
  return Object.values(map)
})

function handleRemove(stepId) {
  removeItem(stepId)
}

function goToStep(step) {
  const index = steps.findIndex(s => s.id === step.id)
  router.push({ path: '/armar', query: { step: index } })
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveCotizacion() {
  saveError.value = ''
  saveSuccess.value = false
  saving.value = true

  const items = Object.values(selectedComponents.value).map(item => ({
    componente_id: item.id,
    precio:        Number(item.precio_final || item.precio),
    cantidad:      item.cantidad || 1,
  }))

  try {
    const res = await fetch(`${API}/cotizaciones`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ items, total: totalPrice.value, perfil: perfil.value })
    })
    const data = await res.json()
    if (!res.ok) {
      const errMsg = data.message || data.error || 'Error al guardar cotización'
      saveError.value = errMsg
      toast.error(errMsg)
      return
    }
    
    generatedCode.value = data.codigo
    saveSuccess.value = true
    toast.success('Cotización guardada exitosamente')
  } catch(e) {
    saveError.value = 'Error de conexión con el servidor'
    toast.error('Error de conexión con el servidor')
  } finally {
    saving.value = false
  }
}
</script>
