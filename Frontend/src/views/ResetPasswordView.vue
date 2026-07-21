<template>
  <main class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 sm:px-6 py-12 relative overflow-auto">

    <!-- Background glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.02]"
      style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
    </div>

    <div class="w-full max-w-md relative z-10 py-4">

      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2.5 mb-4">
          <div class="w-10 h-10 rounded-xl bg-accent flex items-center justify-center text-white font-bold">PC</div>
          <span class="font-bold theme-text text-2xl tracking-tight">PCMATCH</span>
        </div>
        <h1 class="text-2xl font-bold theme-text">Nueva contraseña</h1>
        <p class="theme-text-muted mt-2 text-sm">Ingresa tu nueva contraseña para restablecer el acceso</p>
      </div>

      <!-- Card -->
      <div class="card-dark rounded-2xl p-6 sm:p-8">

        <!-- Invalid token state -->
        <div v-if="invalidLink" class="text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-400 mx-auto">
            <AlertTriangle class="w-8 h-8" />
          </div>
          <h2 class="text-lg font-bold theme-text">Enlace no válido</h2>
          <p class="theme-text-muted text-sm leading-relaxed">
            Este enlace de recuperación no es válido o le faltan parámetros. Solicita uno nuevo.
          </p>
          <router-link to="/recuperar-password" class="btn-primary w-full text-sm text-center block mt-6">
            Solicitar nuevo enlace →
          </router-link>
        </div>

        <!-- Success state -->
        <div v-else-if="success" class="text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center text-green-400 mx-auto">
            <Check class="w-8 h-8" />
          </div>
          <h2 class="text-lg font-bold theme-text">Contraseña actualizada</h2>
          <p class="theme-text-muted text-sm leading-relaxed">
            Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña.
          </p>
          <router-link to="/login" class="btn-primary w-full text-sm text-center block mt-6">
            Iniciar sesión →
          </router-link>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="handleSubmit" class="space-y-5">

          <!-- Error -->
          <div v-if="errorMsg" class="mb-5 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ errorMsg }}
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Nueva contraseña</label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mínimo 8 caracteres"
                v-model="password"
                class="theme-input pr-12"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-xs"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Confirmar contraseña</label>
            <div class="relative">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                v-model="confirmPassword"
                class="theme-input pr-12"
                :class="confirmPassword && password !== confirmPassword
                  ? 'border-red-500/50'
                  : confirmPassword && password === confirmPassword
                    ? 'border-green-500/50'
                    : ''"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-xs"
              >
                <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="confirmPassword && password !== confirmPassword"
              class="text-xs text-red-400 mt-1">
              Las contraseñas no coinciden
            </p>
            <p v-if="confirmPassword && password === confirmPassword"
              class="text-xs text-green-400 mt-1">
              <Check class="w-3 h-3 inline-block mr-1" /> Las contraseñas coinciden
            </p>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full text-sm text-center block mt-6"
            :class="{ 'opacity-60 cursor-not-allowed': loading }"
          >
            {{ loading ? 'Actualizando contraseña...' : 'Restablecer contraseña →' }}
          </button>
        </form>

        <!-- Back to login -->
        <div v-if="!success && !invalidLink" class="mt-6 text-center">
          <router-link to="/login" class="text-sm text-accent hover:underline inline-flex items-center gap-1">
            ← Volver a iniciar sesión
          </router-link>
        </div>

      </div>
    </div>
  </main>
</template>

<script setup>
import { AlertTriangle, Check, Eye, EyeOff } from 'lucide-vue-next';

import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { API } from '@/config/api'

const route = useRoute()
const router = useRouter()

const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const loading = ref(false)
const errorMsg = ref('')
const success = ref(false)
const invalidLink = ref(false)

const token = ref('')
const email = ref('')

onMounted(() => {
  token.value = route.query.token || ''
  email.value = route.query.email || ''

  if (!token.value || !email.value) {
    invalidLink.value = true
  }
})

async function handleSubmit() {
  errorMsg.value = ''

  if (!password.value || !confirmPassword.value) {
    return errorMsg.value = 'Completa ambos campos'
  }
  if (password.value.length < 8) {
    return errorMsg.value = 'La contraseña debe tener al menos 8 caracteres'
  }
  if (password.value !== confirmPassword.value) {
    return errorMsg.value = 'Las contraseñas no coinciden'
  }

  loading.value = true
  try {
    const res = await fetch(`${API}/auth/reset-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        token: token.value,
        email: email.value,
        password: password.value,
        password_confirmation: confirmPassword.value
      })
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.message ?? 'Error al restablecer la contraseña')
    }

    success.value = true
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}
</script>
