/**
 * Configuración centralizada de Chart.js
 * Registra solo los controladores y elementos necesarios (tree-shakeable).
 */
import {
  Chart,
  BarController,
  DoughnutController,
  PieController,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend,
} from 'chart.js'

Chart.register(
  BarController,
  DoughnutController,
  PieController,
  BarElement,
  ArcElement,
  CategoryScale,
  LinearScale,
  Tooltip,
  Legend
)

export { Chart }

/**
 * Paleta de colores reutilizable para gráficos.
 * Colores con opacidad 0.8 para fondos, 1.0 para bordes.
 */
export const CHART_COLORS = [
  'rgba(99, 102, 241, 0.8)',   // indigo
  'rgba(16, 185, 129, 0.8)',   // emerald
  'rgba(245, 158, 11, 0.8)',   // amber
  'rgba(239, 68, 68, 0.8)',    // red
  'rgba(139, 92, 246, 0.8)',   // violet
  'rgba(6, 182, 212, 0.8)',    // cyan
  'rgba(236, 72, 153, 0.8)',   // pink
  'rgba(34, 197, 94, 0.8)',    // green
  'rgba(251, 146, 60, 0.8)',   // orange
  'rgba(168, 85, 247, 0.8)',   // purple
]

export const CHART_COLORS_WARNING = [
  'rgba(245, 158, 11, 0.8)',   // amber
  'rgba(239, 68, 68, 0.8)',    // red
  'rgba(251, 146, 60, 0.8)',   // orange
  'rgba(234, 179, 8, 0.8)',    // yellow
  'rgba(236, 72, 153, 0.8)',   // pink
  'rgba(220, 38, 38, 0.8)',    // red-600
  'rgba(249, 115, 22, 0.8)',   // orange-500
  'rgba(217, 119, 6, 0.8)',    // amber-600
  'rgba(239, 68, 68, 0.6)',    // red-light
  'rgba(245, 158, 11, 0.6)',   // amber-light
]

/**
 * Genera opciones de tooltip estilizadas según el tema.
 */
export function tooltipOptions(isDark) {
  return {
    backgroundColor: isDark ? '#1e293b' : '#fff',
    titleColor: isDark ? '#e2e8f0' : '#1e293b',
    bodyColor: isDark ? '#94a3b8' : '#64748b',
    borderColor: isDark ? '#334155' : '#e2e8f0',
    borderWidth: 1,
    padding: 12,
    cornerRadius: 8,
    titleFont: { size: 13, weight: '600' },
    bodyFont: { size: 12 },
  }
}

/**
 * Colores de texto y cuadrícula según el tema.
 */
export function themeColors(isDark) {
  return {
    text: isDark ? '#94a3b8' : '#64748b',
    grid: isDark ? 'rgba(148, 163, 184, 0.08)' : 'rgba(100, 116, 139, 0.1)',
  }
}
