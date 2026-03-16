import { ref, computed } from 'vue'

const selectedItems = ref({})
const perfil = ref('')


const steps = [
  { id: 'cpu',         icon: '⚙️', label: 'Procesador',      short: 'CPU',  categoria: 'CPU'         },
  { id: 'gpu',         icon: '🎮', label: 'Tarjeta Gráfica', short: 'GPU',  categoria: 'GPU'         },
  { id: 'ram',         icon: '💾', label: 'Memoria RAM',     short: 'RAM',  categoria: 'RAM'         },
  { id: 'storage',     icon: '💿', label: 'Almacenamiento',  short: 'SSD',  categoria: 'Storage'     },
  { id: 'motherboard', icon: '🔌', label: 'Motherboard',     short: 'MOBO', categoria: 'Motherboard' },
  { id: 'psu',         icon: '⚡', label: 'Fuente de Poder', short: 'PSU',  categoria: 'PSU'         },
  { id: 'cooler',      icon: '❄️', label: 'Cooler',          short: 'FAN',  categoria: 'Cooler'      },
  { id: 'case',        icon: '🖥️', label: 'Gabinete',        short: 'CASE', categoria: 'Case'        },
]

export function useBuilder() {
  const selectedComponents = computed(() => {
    const result = {}
    steps.forEach(step => {
      if (selectedItems.value[step.id]) {
        result[step.id] = { ...selectedItems.value[step.id], step }
      }
    })
    return result
  })

  const totalPrice = computed(() =>
    Object.values(selectedComponents.value).reduce((sum, item) => sum + Number(item.precio), 0)
  )

  function selectItem(stepId, item) {
    selectedItems.value[stepId] = item
  }

  function removeItem(stepId) {
    delete selectedItems.value[stepId]
  }

  function clearAll() {
    selectedItems.value = {}
  }

  return { steps, selectedItems, selectedComponents, totalPrice, perfil, selectItem, removeItem, clearAll }
}