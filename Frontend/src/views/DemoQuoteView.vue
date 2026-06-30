<template>
  <main class="max-w-4xl mx-auto px-6 py-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6 mb-10">
      <div>
        <p class="text-accent text-sm font-medium uppercase tracking-widest mb-2">Demo de Cotización</p>
        <h1 class="text-3xl font-bold theme-text">Resumen de tu PC (Ejemplo)</h1>
        <p class="theme-text-muted mt-2">Generado el {{ today }}</p>
      </div>
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <router-link to="/login" class="btn-primary text-sm flex-1 sm:flex-none">
          Crear mi propia cotización
        </router-link>
      </div>
    </div>

    <!-- Alert -->
    <div class="mb-6 px-4 py-3 rounded-lg bg-accent/10 border border-accent/20 text-accent text-sm">
      <p class="font-bold mb-1 flex items-center gap-1"><Info class="w-4 h-4" /> Modo Demostración</p>
      <p>Esta es una cotización de ejemplo con datos ficticios para que veas cómo funciona el sistema.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

      <!-- Components List -->
      <div class="lg:col-span-2 space-y-3">
        <div class="card-dark rounded-xl overflow-hidden">
          <div class="px-5 py-4 border-b theme-border flex items-center justify-between">
            <h2 class="font-semibold theme-text">Componentes de ejemplo</h2>
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

              <!-- Store + Price + Qty -->
              <div class="flex-shrink-0 flex flex-col items-end gap-2">
                <p class="text-xs theme-text-muted">{{ item.bodega }}</p>
                <div class="flex items-center gap-1.5">
                  <span class="text-xs theme-text-muted">Cant: {{ item.cantidad }}</span>
                </div>
                <p class="text-accent font-semibold font-mono text-sm">${{ (Number(item.precio) * (item.cantidad || 1)).toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Componentes faltantes -->
        <div v-if="missingSteps.length > 0" class="card-dark rounded-xl p-5">
          <h3 class="font-semibold theme-text-muted text-sm mb-3">Sugerencias (Sin seleccionar)</h3>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="step in missingSteps"
              :key="step.id"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-dashed theme-border theme-text-muted hover:border-accent hover:text-accent transition-all text-xs bg-white/50 dark:bg-transparent cursor-default"
            >
              <component :is="step.icon" class="w-4 h-4" /> {{ step.label }}
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
          <h3 class="font-semibold theme-text text-sm mb-3">¿Te gusta lo que ves?</h3>
          <router-link to="/login" class="btn-primary w-full text-sm py-3 flex items-center justify-center gap-2">
            Iniciar sesión para armar
          </router-link>
          <router-link to="/" class="btn-secondary w-full text-sm text-center block">
            ← Volver al inicio
          </router-link>
        </div>

        <div class="rounded-xl border border-amber-500/30 bg-amber-50 dark:bg-yellow-500/5 p-4">
          <p class="text-amber-700 dark:text-yellow-400 text-xs font-medium mb-1 flex items-center gap-1"><AlertTriangle class="w-3 h-3" /> Nota importante</p>
          <p class="text-amber-600/80 dark:text-text-muted text-xs leading-relaxed">
            Los precios son referenciales y pueden variar. Verifica disponibilidad antes de comprar.
          </p>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref, markRaw } from 'vue'
import { Info, AlertTriangle, Cpu, CircuitBoard, Zap, Gamepad2, Save, Battery, Package } from '@lucide/vue'

const today = new Date().toLocaleDateString('es-CL', { day: 'numeric', month: 'long', year: 'numeric' })

const steps = [
  { id: 'cpu', label: 'Procesador', icon: markRaw(Cpu) },
  { id: 'mb', label: 'Placa Madre', icon: markRaw(CircuitBoard) },
  { id: 'ram', label: 'Memoria RAM', icon: markRaw(Zap) },
  { id: 'gpu', label: 'Tarjeta de Video', icon: markRaw(Gamepad2) },
  { id: 'storage', label: 'Almacenamiento', icon: markRaw(Save) },
  { id: 'psu', label: 'Fuente de Poder', icon: markRaw(Battery) },
  { id: 'case', label: 'Gabinete', icon: markRaw(Package) }
]

const selectedComponents = ref({
  cpu: { id: 1, nombre: 'AMD Ryzen 5 5600X', especificacion: '6 Cores, 12 Threads, 4.6GHz', precio: 159990, cantidad: 1, bodega: 'PC Factory', step: steps[0] },
  mb: { id: 2, nombre: 'ASUS Prime B550M-A AC', especificacion: 'Micro-ATX, AM4, Wi-Fi', precio: 119990, cantidad: 1, bodega: 'SP Digital', step: steps[1] },
  ram: { id: 3, nombre: 'Corsair Vengeance LPX 16GB', especificacion: '2x8GB DDR4 3200MHz', precio: 49990, cantidad: 1, bodega: 'PC Factory', step: steps[2] },
  gpu: { id: 4, nombre: 'NVIDIA GeForce RTX 3060', especificacion: '12GB GDDR6', precio: 299990, cantidad: 1, bodega: 'Infor-Ingen', step: steps[3] },
  storage: { id: 5, nombre: 'Kingston NV2 1TB', especificacion: 'M.2 NVMe PCIe 4.0', precio: 55990, cantidad: 1, bodega: 'SP Digital', step: steps[4] },
})

const missingSteps = [steps[5], steps[6]]

const storeBreakdown = [
  { name: 'PC Factory', count: 2, total: 209980 },
  { name: 'SP Digital', count: 2, total: 175980 },
  { name: 'Infor-Ingen', count: 1, total: 299990 }
]

const totalPrice = 685950
</script>
