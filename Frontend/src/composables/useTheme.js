import { ref, watchEffect } from 'vue'

const STORAGE_KEY = 'pcmatch-theme'

// Shared singleton state
const isDark = ref(true)

// Initialize once
let initialized = false
function init() {
  if (initialized) return
  initialized = true

  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored) {
    isDark.value = stored === 'dark'
  } else {
    isDark.value = !window.matchMedia('(prefers-color-scheme: light)').matches
  }

  // React to changes
  watchEffect(() => {
    const html = document.documentElement
    if (isDark.value) {
      html.classList.add('dark')
      html.classList.remove('light')
    } else {
      html.classList.remove('dark')
      html.classList.add('light')
    }
    localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light')
  })
}

export function useTheme() {
  init()

  function toggleTheme() {
    isDark.value = !isDark.value
  }

  function setTheme(mode) {
    isDark.value = mode === 'dark'
  }

  return {
    isDark,
    toggleTheme,
    setTheme,
  }
}
