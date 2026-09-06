<template>
  <div class="w-full">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <div class="w-2 h-2 rounded-full bg-violet-400"></div>
        <h3 class="text-sm font-semibold theme-text">{{ title }}</h3>
      </div>
      <span v-if="totalLabel" class="text-xs theme-text-muted font-mono">
        Total: {{ totalLabel }}
      </span>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <div class="w-6 h-6 border-2 border-accent/30 border-t-accent rounded-full animate-spin"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="labels.length === 0" class="py-12 text-center">
      <p class="text-sm theme-text-muted">{{ emptyText }}</p>
    </div>

    <!-- Chart -->
    <div v-else class="relative" style="height: 320px">
      <canvas ref="canvasRef"></canvas>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onBeforeUnmount, nextTick } from 'vue'
import { Chart, CHART_COLORS, tooltipOptions } from '@/config/chartjs'

const props = defineProps({
  /** Título del gráfico */
  title: { type: String, default: 'Distribución' },
  /** Etiquetas (nombre de cada segmento) */
  labels: { type: Array, default: () => [] },
  /** Valores numéricos para cada segmento */
  values: { type: Array, default: () => [] },
  /** Porcentajes opcionales (para leyenda personalizada) */
  percentages: { type: Array, default: () => [] },
  /** Modo oscuro activo */
  isDark: { type: Boolean, default: false },
  /** Estado de carga */
  loading: { type: Boolean, default: false },
  /** Texto total a mostrar en header */
  totalLabel: { type: String, default: '' },
  /** Texto cuando no hay datos */
  emptyText: { type: String, default: 'Sin datos disponibles' },
})

const canvasRef = ref(null)
let chartInstance = null

function render() {
  if (!canvasRef.value || props.labels.length === 0) return
  if (chartInstance) chartInstance.destroy()

  const colors = props.labels.map((_, i) => CHART_COLORS[i % CHART_COLORS.length])
  const textColor = props.isDark ? '#94a3b8' : '#64748b'

  chartInstance = new Chart(canvasRef.value, {
    type: 'doughnut',
    data: {
      labels: props.labels,
      datasets: [{
        data: props.values,
        backgroundColor: colors,
        borderWidth: 0,
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '55%',
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: textColor,
            font: { size: 12 },
            padding: 14,
            usePointStyle: true,
            pointStyleWidth: 10,
            generateLabels(chart) {
              const ds = chart.data.datasets[0]
              return chart.data.labels.map((label, i) => {
                const pct = props.percentages[i] !== undefined ? ` (${props.percentages[i]}%)` : ''
                return {
                  text: `${label}${pct}`,
                  fillStyle: ds.backgroundColor[i],
                  hidden: false,
                  index: i,
                }
              })
            }
          },
        },
        tooltip: {
          ...tooltipOptions(props.isDark),
          callbacks: {
            label: ctx => {
              const val = ctx.parsed
              const pct = props.percentages[ctx.dataIndex]
              return pct !== undefined ? `${val} unidades (${pct}%)` : `${val} unidades`
            }
          }
        },
      },
      animation: { duration: 700, easing: 'easeOutQuart' },
    },
  })
}

watch(
  () => [props.labels, props.values, props.isDark, props.percentages],
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
