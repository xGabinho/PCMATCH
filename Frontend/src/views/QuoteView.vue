<template>
  <main class="max-w-4xl mx-auto px-6 py-12">

    <!-- Sin componentes -->
    <div v-if="Object.keys(selectedComponents).length === 0" class="text-center py-24">
      <div class="text-5xl mb-4">🖥️</div>
      <p class="text-text-primary font-semibold text-lg mb-2">No hay componentes seleccionados</p>
      <p class="text-text-muted text-sm mb-6">Vuelve al armador y selecciona los componentes de tu PC</p>
      <router-link to="/armar" class="btn-primary text-sm">← Ir al armador</router-link>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="flex items-start justify-between mb-10">
        <div>
          <p class="text-accent text-sm font-medium uppercase tracking-widest mb-2">Cotización</p>
          <h1 class="text-3xl font-bold text-text-primary">Resumen de tu PC</h1>
          <p class="text-text-muted mt-2">Generado el {{ today }}</p>
        </div>
        <div class="flex items-center gap-3">
          <router-link to="/armar" class="btn-secondary text-sm">← Editar build</router-link>
          <button @click="saveCotizacion" :disabled="saving" class="btn-primary text-sm">
            {{ saving ? 'Guardando...' : '💾 Guardar cotización' }}
          </button>
        </div>
      </div>

      <!-- Success -->
      <div v-if="saveSuccess" class="mb-6 px-4 py-3 rounded-lg bg-green-500/10 border border-green-500/20 text-green-400 text-sm">
        ✓ Cotización guardada correctamente
      </div>
      <div v-if="saveError" class="mb-6 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
        {{ saveError }}
      </div>

      <div class="grid grid-cols-3 gap-6">

        <!-- Components List -->
        <div class="col-span-2 space-y-3">
          <div class="card-dark rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Componentes seleccionados</h2>
              <span class="badge bg-accent/10 text-accent border border-accent/20">{{ Object.keys(selectedComponents).length }} componentes</span>
            </div>

            <div class="divide-y divide-dark-border">
              <div
                v-for="(item, stepId) in selectedComponents"
                :key="stepId"
                class="px-5 py-4 flex items-center gap-4 hover:bg-dark-bg/40 transition-colors group"
              >
                <!-- Icon -->
                <div class="w-9 h-9 rounded-lg bg-accent/10 flex items-center justify-center text-accent text-sm flex-shrink-0">
                  {{ item.step.icon }}
                </div>

                <!-- Info -->
                <div class="flex-1 min-w-0">
                  <p class="text-xs text-text-muted uppercase tracking-wide mb-0.5">{{ item.step.label }}</p>
                  <p class="text-sm font-medium text-text-primary">{{ item.nombre }}</p>
                  <p class="text-xs text-text-muted mt-0.5">{{ item.especificacion }}</p>
                </div>

                <!-- Store + Price + Remove -->
                <div class="text-right flex-shrink-0 flex flex-col items-end gap-1">
                  <button
                    @click="handleRemove(stepId)"
                    class="text-text-muted hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100 text-xs"
                    title="Quitar componente"
                  >✕ quitar</button>
                  <p class="text-xs text-text-muted">{{ item.bodega }}</p>
                  <p class="text-accent font-semibold font-mono text-sm">${{ Number(item.precio).toLocaleString() }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Componentes faltantes -->
          <div v-if="missingSteps.length > 0" class="card-dark rounded-xl p-5">
            <h3 class="font-semibold text-text-muted text-sm mb-3">Sin seleccionar</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="step in missingSteps"
                :key="step.id"
                @click="goToStep(step)"
                class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed border-dark-border text-text-muted hover:border-accent hover:text-accent transition-all text-xs"
              >
                {{ step.icon }} {{ step.label }} →
              </button>
            </div>
          </div>

          <!-- Store breakdown -->
          <div class="card-dark rounded-xl p-5">
            <h3 class="font-semibold text-text-primary mb-4">Por bodega</h3>
            <div class="space-y-3">
              <div v-for="store in storeBreakdown" :key="store.name" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-2 h-2 rounded-full bg-accent"></div>
                  <span class="text-text-primary text-sm">{{ store.name }}</span>
                  <span class="text-xs text-text-muted">({{ store.count }} producto{{ store.count > 1 ? 's' : '' }})</span>
                </div>
                <span class="text-sm font-medium text-text-primary font-mono">${{ store.total.toLocaleString() }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Price Summary -->
        <div class="space-y-4">
          <div class="card-dark rounded-xl p-6">
            <p class="text-sm text-text-muted mb-1">Total estimado</p>
            <p class="text-4xl font-bold text-accent font-mono">${{ totalPrice.toLocaleString() }}</p>
            <p class="text-xs text-text-muted mt-2">Precio referencial · No incluye instalación</p>

            <div class="mt-4 pt-4 border-t border-dark-border space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Componentes</span>
                <span class="text-text-primary font-mono">{{ Object.keys(selectedComponents).length }} / {{ steps.length }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-text-muted">Bodegas</span>
                <span class="text-text-primary font-mono">{{ storeBreakdown.length }}</span>
              </div>
            </div>
          </div>

          <div class="card-dark rounded-xl p-5 space-y-3">
            <h3 class="font-semibold text-text-primary text-sm mb-1">Acciones</h3>
            <button @click="saveCotizacion" :disabled="saving" class="btn-primary w-full text-sm">
              {{ saving ? 'Guardando...' : '💾 Guardar cotización' }}
            </button>
            <router-link to="/armar" class="btn-secondary w-full text-sm text-center block">
              ← Volver al armador
            </router-link>
            <button @click="clearAll(); $router.push('/armar')" class="w-full text-sm py-2 rounded-lg text-text-muted hover:text-red-400 hover:bg-red-400/5 border border-dark-border transition-colors">
              🗑️ Empezar de nuevo
            </button>
          </div>

          <div class="rounded-xl border border-yellow-500/20 bg-yellow-500/5 p-4">
            <p class="text-yellow-400 text-xs font-medium mb-1">⚠️ Nota importante</p>
            <p class="text-text-muted text-xs leading-relaxed">
              Los precios son referenciales y pueden variar. Verifica disponibilidad antes de comprar.
            </p>
          </div>
        </div>

      </div>
    </template>
  </main>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useBuilder } from '../composables/useBuilder'
import { useAuth } from '../composables/useAuth'

const API = 'http://localhost/pcmatch/backend/api'
const router = useRouter()
const { steps, selectedComponents, totalPrice, perfil, removeItem, clearAll } = useBuilder()
const { getToken } = useAuth()

const saving      = ref(false)
const saveSuccess = ref(false)
const saveError   = ref('')

const today = new Date().toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' })

const missingSteps = computed(() =>
  steps.filter(s => !selectedComponents.value[s.id])
)

const storeBreakdown = computed(() => {
  const map = {}
  Object.values(selectedComponents.value).forEach(item => {
    if (!map[item.bodega]) map[item.bodega] = { name: item.bodega, count: 0, total: 0 }
    map[item.bodega].count++
    map[item.bodega].total += Number(item.precio)
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

async function saveCotizacion() {
  saveError.value = ''
  saveSuccess.value = false
  saving.value = true

const items = Object.values(selectedComponents.value).map(item => ({
  componente_id: item.id,
  precio:        Number(item.precio),
  cantidad:      1,
}))

  try {
    const res = await fetch(`${API}/cotizaciones/`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ items, total: totalPrice.value, perfil: perfil.value })
    })
    const data = await res.json()
    if (!res.ok) return saveError.value = data.error ?? 'Error al guardar'
    saveSuccess.value = true
  } catch(e) {
    saveError.value = 'Error de conexión'
  } finally {
    saving.value = false
  }
}
</script>