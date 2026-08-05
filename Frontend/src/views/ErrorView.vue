<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-slate-900/80 backdrop-blur-md border border-slate-800 rounded-2xl p-8 text-center shadow-2xl">
      <!-- Indicador o Código de Error -->
      <div class="text-6xl font-extrabold bg-gradient-to-r from-sky-400 to-indigo-500 bg-clip-text text-transparent mb-4">
        {{ errorCode }}
      </div>

      <!-- Título de Error -->
      <h1 class="text-2xl font-bold text-slate-100 mb-3">
        {{ errorTitle }}
      </h1>

      <!-- Descripción Amigable -->
      <p class="text-slate-400 text-sm mb-8 leading-relaxed">
        {{ errorDescription }}
      </p>

      <!-- Acciones de Navegación -->
      <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
        <button
          @click="goHome"
          class="w-full sm:w-auto px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-semibold rounded-lg shadow-lg hover:shadow-indigo-500/25 transition-all text-sm"
        >
          Volver al Inicio
        </button>

        <button
          @click="reloadPage"
          class="w-full sm:w-auto px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold rounded-lg border border-slate-700 transition-all text-sm"
        >
          Reintentar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const props = defineProps({
  code: {
    type: [Number, String],
    default: null
  },
  title: {
    type: String,
    default: null
  },
  description: {
    type: String,
    default: null
  }
})

const errorCode = computed(() => {
  return props.code || route.query.code || '404'
})

const errorTitle = computed(() => {
  if (props.title) return props.title
  if (route.query.title) return route.query.title

  switch (String(errorCode.value)) {
    case '404':
      return 'Página No Encontrada'
    case '500':
      return 'Error Interno del Servidor'
    case '502':
    case '503':
      return 'Servicio No Disponible'
    default:
      return 'Ha Ocurrido un Error Inesperado'
  }
})

const errorDescription = computed(() => {
  if (props.description) return props.description
  if (route.query.description) return route.query.description

  switch (String(errorCode.value)) {
    case '404':
      return 'La página o recurso que buscas no existe o ha sido movido.'
    case '500':
      return 'Tuvimos un problema procesando tu solicitud. El incidente ha sido registrado automáticamente para ser resuelto.'
    case '502':
    case '503':
      return 'El servicio se encuentra en mantenimiento o temporalmente fuera de línea. Por favor reintenta en unos minutos.'
    default:
      return 'Lo sentimos, algo no salió como esperábamos. Puedes reintentar o volver al inicio.'
  }
})

const goHome = () => {
  router.push('/')
}

const reloadPage = () => {
  window.location.reload()
}
</script>
