<template>
  <div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full" :class="variantDot"></div>
        <h3 class="text-sm font-semibold theme-text">{{ title }}</h3>
      </div>
      <span v-if="items.length > 0" class="text-xs theme-text-muted font-mono">
        {{ items.length }} items
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="w-6 h-6 border-2 border-accent/30 border-t-accent rounded-full animate-spin"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="items.length === 0" class="py-12 text-center">
      <p class="text-sm theme-text-muted">{{ emptyText }}</p>
    </div>

    <!-- Chart -->
    <div v-else class="relative" :style="{ height: chartHeight }">
      <canvas ref="canvasRef"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, nextTick, computed } from 'vue'
import { Chart, CHART_COLORS, CHART_COLORS_WARNING, tooltipOptions, themeColors } from '@/config/chartjs'

const props = defineProps({
  /** Título del gráfico */
  title: { type: String, default: 'Flujo de componentes' },
  /** Lista de items: [{ label, value, sublabel? }] */
  items: { type: Array, default: () => [] },
  /** Orientación del eje de categorías: 'x' (barras verticales) o 'y' (barras horizontales) */
  orientation: { type: String, default: 'y', validator: v => ['x', 'y'].includes(v) },
  /** Variante de colores: 'accent' (positivo), 'warning' (atención), 'success' */
  variant: { type: String, default: 'accent', validator: v => ['accent', 'warning', 'success'].includes(v) },
  /** Etiqueta de unidad para el tooltip */
  unitLabel: { type: String, default: 'unidades' },
  /** Modo oscuro activo */
  isDark: { type: Boolean, default: false },
  /** Estado de carga */
  loading: { type: Boolean, default: false },
  /** Texto cuando no hay datos */
  emptyText: { type: String, default: 'Sin datos disponibles' },
})

const canvasRef = ref(null)
let chartInstance = null

const variantDot = computed(() => {
  if (props.variant === 'warning') return 'bg-amber-400'
  if (props.variant === 'success') return 'bg-emerald-400'
  return 'bg-indigo-400'
})

const chartHeight = computed(() => {
  if (props.orientation === 'y') {
    // Horizontal bars: height based on item count
    const minH = 200
    const perItem = 36
    return `${Math.max(minH, props.items.length * perItem)}px`
  }
  return '300px'
})

function getColors() {
  return props.variant === 'warning' ? CHART_COLORS_WARNING : CHART_COLORS
}

function render() {
  if (!canvasRef.value || props.items.length === 0) return
  if (chartInstance) chartInstance.destroy()

  const labels = props.items.map(d => {
    const l = d.label || ''
    return l.length > 28 ? l.slice(0, 28) + '…' : l
  })
  const values = props.items.map(d => Number(d.value) || 0)
  const palette = getColors()
  const colors = props.items.map((_, i) => palette[i % palette.length])
  const theme = themeColors(props.isDark)

  const isHorizontal = props.orientation === 'y'

  chartInstance = new Chart(canvasRef.value, {
    type: 'bar',
    data: {
      labels,
      datasets: [{
        label: props.unitLabel,
        data: values,
        backgroundColor: colors,
        borderRadius: 6,
        borderSkipped: false,
        maxBarThickness: 40,
      }]
    },
    options: {
      indexAxis: isHorizontal ? 'y' : 'x',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          ...tooltipOptions(props.isDark),
          callbacks: {
            label: ctx => {
              const item = props.items[ctx.dataIndex]
              let text = `${ctx.parsed[isHorizontal ? 'x' : 'y']} ${props.unitLabel}`
              if (item?.sublabel) text += ` — ${item.sublabel}`
              return text
            }
          }
        },
      },
      scales: {
        x: {
          beginAtZero: true,
          ticks: { color: theme.text, font: { size: 11 } },
          grid: { color: isHorizontal ? theme.grid : 'transparent' },
        },
        y: {
          ticks: { color: theme.text, font: { size: 11 } },
          grid: { color: isHorizontal ? 'transparent' : theme.grid },
        },
      },
      animation: { duration: 700, easing: 'easeOutQuart' },
    },
  })
}

// Re-render when data or theme changes
watch(
  () => [props.items, props.isDark, props.variant, props.orientation],
  async () => {
    await nextTick()
    render()
  },
  { deep: true }
)

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
})
</script>
