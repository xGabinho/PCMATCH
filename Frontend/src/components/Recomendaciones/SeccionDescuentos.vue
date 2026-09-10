<template>
  <section v-if="descuentos.length > 0" class="py-16 border-t theme-border bg-gradient-to-b from-transparent via-red-500/[0.02] to-transparent">
    <div class="max-w-7xl mx-auto px-6">

      <!-- Header -->
      <div class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-red-500/30 bg-red-500/10 text-red-400 text-xs font-semibold mb-4">
          <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
          Ofertas y promociones activas
        </div>
        <h2 class="text-3xl font-bold theme-text tracking-tight mb-2">
          Componentes en Descuento
        </h2>
        <p class="theme-text-muted text-sm max-w-lg mx-auto">
          Aprovecha componentes con precios rebajados directamente por las bodegas asociadas.
        </p>
      </div>

      <!-- Filtros por categoría de descuento -->
      <div v-if="availableCats.length > 1" class="flex items-center justify-center gap-2 flex-wrap mb-8">
        <button
          v-for="cat in ['Todos', ...availableCats]"
          :key="cat"
          @click="activeCat = cat"
          class="px-4 py-2 rounded-xl text-xs font-semibold transition-all duration-200 cursor-pointer min-h-[38px] flex items-center gap-1.5"
          :class="activeCat === cat
            ? 'bg-red-500 text-white shadow-lg shadow-red-500/25'
            : 'theme-card border theme-border theme-text-muted hover:theme-text hover:border-red-500/30'"
        >
          <span v-if="cat === 'Todos'">🔥</span>
          {{ cat }}
          <span class="text-[10px] opacity-75 font-mono">
            ({{ cat === 'Todos' ? descuentos.length : (byCatCounts[cat] || 0) }})
          </span>
        </button>
      </div>

      <!-- Grid de Componentes con Descuento -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 animate-fade-in">
        <div
          v-for="comp in displayedItems"
          :key="'desc-sec-' + comp.id"
          class="card-dark rounded-2xl flex flex-col card-hover group overflow-hidden border border-red-500/20 hover:border-red-500/40 relative shadow-sm"
        >
          <!-- Badge Descuento -->
          <div class="absolute top-3 left-3 z-10 px-2.5 py-1 rounded-lg text-xs font-black bg-red-600 text-white shadow-md flex items-center gap-1">
            <span>🔥</span> -{{ comp.descuento_porcentaje }}%
          </div>

          <!-- Stock badge -->
          <div class="absolute top-3 right-3 z-10 px-2 py-0.5 rounded-md text-[10px] font-medium bg-black/50 backdrop-blur-sm text-gray-300 border border-white/10">
            {{ comp.stock > 0 ? `${comp.stock} disp.` : 'Agotado' }}
          </div>

          <!-- Imagen del componente -->
          <div class="relative w-full h-44 theme-bg flex items-center justify-center overflow-hidden flex-shrink-0 p-4">
            <img
              v-if="comp.imagen_url"
              :src="comp.imagen_url"
              :alt="comp.nombre"
              class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300 drop-shadow-sm"
            />
            <component v-else :is="categoryIcons[comp.categoria] ?? Wrench" class="w-12 h-12 opacity-20" />
          </div>

          <!-- Contenido / Ficha -->
          <div class="p-5 flex flex-col gap-2 flex-1">
            <div class="flex items-center justify-between gap-2">
              <span class="badge text-[10px] bg-accent/10 text-accent border border-accent/20">
                {{ comp.categoria }}
              </span>
              <span v-if="comp.gama" class="text-[10px] uppercase font-semibold text-text-muted">
                Gama {{ comp.gama }}
              </span>
            </div>

            <h3 class="text-sm font-semibold theme-text leading-snug line-clamp-2 group-hover:text-accent transition-colors mt-1">
              {{ comp.nombre }}
            </h3>

            <p class="text-xs theme-text-muted leading-relaxed line-clamp-2 mt-0.5">
              {{ comp.especificacion }}
            </p>

            <!-- Precios y Botón -->
            <div class="mt-auto pt-3 border-t theme-border">
              <div class="flex items-baseline gap-2 mb-1">
                <span class="line-through text-xs theme-text-muted opacity-70">
                  ${{ Number(comp.precio).toLocaleString() }}
                </span>
                <span class="text-[11px] font-bold text-red-400">
                  Ahorras ${{ Number(comp.precio - comp.precio_final).toLocaleString() }}
                </span>
              </div>

              <div class="flex items-end justify-between gap-2">
                <div>
                  <p class="text-accent font-bold font-mono text-xl leading-tight">
                    ${{ Number(comp.precio_final || comp.precio).toLocaleString() }}
                  </p>
                  <p class="text-[11px] theme-text-muted mt-0.5 flex items-center gap-1">
                    <span>📍</span> {{ comp.bodega }}
                  </p>
                </div>

                <button
                  @click="handleUse(comp)"
                  :disabled="comp.stock == 0"
                  class="btn-primary text-xs px-3.5 py-2 rounded-xl flex items-center gap-1.5 cursor-pointer disabled:opacity-40 disabled:pointer-events-none"
                >
                  Usar en mi PC →
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</template>

<script setup>
import { ref, computed, markRaw } from 'vue'
import { useRouter } from 'vue-router'
import { Settings, Gamepad2, Save, Disc, Plug, Zap, Snowflake, Monitor, Wrench } from 'lucide-vue-next'
import { useComponentes } from '@/composables/useComponentes'
import { useBuilder } from '@/composables/useBuilder'

const router = useRouter()
const { allComponents } = useComponentes()
const { selectItem } = useBuilder()

const activeCat = ref('Todos')

const categoryIcons = {
  CPU: markRaw(Settings), GPU: markRaw(Gamepad2), RAM: markRaw(Save), Storage: markRaw(Disc),
  Motherboard: markRaw(Plug), PSU: markRaw(Zap), Cooler: markRaw(Snowflake), Case: markRaw(Monitor)
}

const descuentos = computed(() => {
  return allComponents.value.filter(c => {
    const isActivo = c.descuento_activo === true || c.descuento_activo == 1 || c.descuento_activo === '1' || c.descuento_activo === 'true'
    const pct = Number(c.descuento_porcentaje) || 0
    return isActivo && pct > 0
  })
})

const availableCats = computed(() => {
  return [...new Set(descuentos.value.map(c => c.categoria))].filter(Boolean)
})

const byCatCounts = computed(() => {
  const counts = {}
  for (const c of descuentos.value) {
    counts[c.categoria] = (counts[c.categoria] || 0) + 1
  }
  return counts
})

const displayedItems = computed(() => {
  if (activeCat.value === 'Todos') {
    return descuentos.value.slice(0, 8)
  }
  return descuentos.value.filter(c => c.categoria === activeCat.value).slice(0, 8)
})

const getStepId = (comp) => {
  const cat = (comp.categoria || '').toLowerCase().trim()
  if (cat.includes('cpu') || cat.includes('procesador')) return 'cpu'
  if (cat.includes('gpu') || cat.includes('video') || cat.includes('grafica')) return 'gpu'
  if (cat.includes('ram') || cat.includes('memoria')) return 'ram'
  if (cat.includes('storage') || cat.includes('disco') || cat.includes('ssd')) return 'storage'
  if (cat.includes('motherboard') || cat.includes('placa')) return 'motherboard'
  if (cat.includes('psu') || cat.includes('fuente')) return 'psu'
  if (cat.includes('cooler') || cat.includes('disipador')) return 'cooler'
  if (cat.includes('case') || cat.includes('gabinete')) return 'case'
  return null
}

const handleUse = async (comp) => {
  try {
    const stepId = getStepId(comp)
    if (stepId) {
      selectItem(stepId, {
        id: comp.id,
        nombre: comp.nombre,
        categoria: comp.categoria,
        especificacion: comp.especificacion,
        gama: comp.gama,
        enfoque_uso: comp.enfoque_uso,
        precio: comp.precio,
        precio_final: comp.precio_final || comp.precio,
        stock: comp.stock,
        imagen_url: comp.imagen_url,
        bodega: comp.bodega,
      })
    }
    await router.push('/armar')
  } catch (e) {
    router.push('/armar')
  }
}
</script>
