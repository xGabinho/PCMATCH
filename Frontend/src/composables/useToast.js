import { ref } from 'vue'

const toasts = ref([])
let nextId = 1

export function useToast() {
  const addToast = (message, type = 'info', duration = 3500) => {
    const id = nextId++
    toasts.value.push({ id, message, type })
    if (duration > 0) {
      setTimeout(() => {
        removeToast(id)
      }, duration)
    }
  }

  const removeToast = (id) => {
    toasts.value = toasts.value.filter(t => t.id !== id)
  }

  const success = (message, duration) => addToast(message, 'success', duration)
  const error = (message, duration) => addToast(message, 'error', duration)
  const info = (message, duration) => addToast(message, 'info', duration)

  return { toasts, addToast, removeToast, success, error, info }
}
