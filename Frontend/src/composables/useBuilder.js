import { ref, computed, watch } from 'vue'
import { useAuth } from './useAuth'

const { user } = useAuth()

const selectedItems = ref({})
const perfil = ref('')

// Load state from localStorage based on user
function loadState() {
  if (!user.value || !user.value.id) {
    selectedItems.value = {}
    perfil.value = ''
    return
  }
  
  const saved = localStorage.getItem(`pcmatch_builder_${user.value.id}`)
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      selectedItems.value = parsed.items || {}
      perfil.value = parsed.perfil || ''
    } catch(e) {
      selectedItems.value = {}
      perfil.value = ''
    }
  } else {
    selectedItems.value = {}
    perfil.value = ''
  }
}

// Initial load
loadState()

// Reload if user changes (e.g. logout -> login)
watch(() => user.value?.id, () => {
  loadState()
})

// Auto-save changes to localStorage
watch([selectedItems, perfil], () => {
  if (!user.value || !user.value.id) return
  
  localStorage.setItem(`pcmatch_builder_${user.value.id}`, JSON.stringify({
    items: selectedItems.value,
    perfil: perfil.value
  }))
}, { deep: true })


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
    Object.values(selectedComponents.value).reduce((sum, item) => sum + Number(item.precio_final || item.precio) * (item.cantidad || 1), 0)
  )

  function selectItem(stepId, item) {
    selectedItems.value[stepId] = { ...item, cantidad: 1 }
  }

  function removeItem(stepId) {
    delete selectedItems.value[stepId]
  }

  function updateQuantity(stepId, newQty) {
    if (!selectedItems.value[stepId]) return
    const item = selectedItems.value[stepId]
    const maxStock = item.stock ?? 999
    const clamped = Math.max(1, Math.min(newQty, maxStock))
    selectedItems.value[stepId] = { ...item, cantidad: clamped }
  }

  function clearAll() {
    selectedItems.value = {}
  }

  return { steps, selectedItems, selectedComponents, totalPrice, perfil, selectItem, removeItem, updateQuantity, clearAll }
}