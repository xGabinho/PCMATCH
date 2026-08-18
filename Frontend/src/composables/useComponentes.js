import { ref } from 'vue'
import { API } from '@/config/api'

// Estado global en memoria compartido entre vistas (Instant/SWR)
const allComponents = ref([])
const componentesPorCategoria = ref({})
const isLoaded = ref(false)
const isLoading = ref(false)

export function useComponentes() {
  async function fetchComponentes(force = false) {
    // Retorno instantáneo (0ms) si los datos ya están en RAM
    if (isLoaded.value && !force) {
      // Re-verificar en segundo plano de forma transparente (Stale-While-Revalidate)
      fetch(`${API}/componentes/publico`)
        .then(res => res.json())
        .then(data => {
          if (data?.componentes) {
            allComponents.value = data.componentes
            const agrupado = {}
            for (const comp of data.componentes) {
              if (!agrupado[comp.categoria]) agrupado[comp.categoria] = []
              agrupado[comp.categoria].push(comp)
            }
            componentesPorCategoria.value = agrupado
          }
        })
        .catch(console.error)
      return
    }

    if (!isLoaded.value) {
      isLoading.value = true
    }

    try {
      const res = await fetch(`${API}/componentes/publico`)
      const data = await res.json()
      if (res.ok && data?.componentes) {
        allComponents.value = data.componentes
        const agrupado = {}
        for (const comp of data.componentes) {
          if (!agrupado[comp.categoria]) agrupado[comp.categoria] = []
          agrupado[comp.categoria].push(comp)
        }
        componentesPorCategoria.value = agrupado
        isLoaded.value = true
      }
    } catch (e) {
      console.error(e)
    } finally {
      isLoading.value = false
    }
  }

  return {
    allComponents,
    componentesPorCategoria,
    isLoaded,
    isLoading,
    fetchComponentes
  }
}
