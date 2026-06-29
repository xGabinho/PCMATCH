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
        <h1 class="text-2xl font-bold theme-text">Recuperar contraseña</h1>
        <p class="theme-text-muted mt-2 text-sm">Ingresa tu correo y te enviaremos un enlace para restablecerla</p>
      </div>

      <!-- Card -->
      <div class="card-dark rounded-2xl p-6 sm:p-8">

        <!-- Success state -->
        <div v-if="sent" class="text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-green-500/10 border border-green-500/20 flex items-center justify-center text-3xl mx-auto">
            ✉️
          </div>
          <h2 class="text-lg font-bold theme-text">Enlace enviado</h2>
          <p class="theme-text-muted text-sm leading-relaxed">
            Si el correo <strong class="theme-text">{{ correo }}</strong> está registrado, recibirás un enlace para restablecer tu contraseña.
          </p>
          <p class="theme-text-muted text-xs">Revisa también tu carpeta de spam.</p>
          <router-link to="/login" class="btn-primary w-full text-sm text-center block mt-6">
            Volver al inicio de sesión
          </router-link>
        </div>

        <!-- Form state -->
        <form v-else @submit.prevent="handleSubmit" class="space-y-5">

          <!-- Error -->
          <div v-if="errorMsg" class="mb-5 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
            {{ errorMsg }}
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input
              type="email"
              placeholder="tucorreo@email.com"
              v-model="correo"
              class="theme-input"
              required
            />
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full text-sm text-center block mt-6"
            :class="{ 'opacity-60 cursor-not-allowed': loading }"
          >
            {{ loading ? 'Enviando enlace...' : 'Enviar enlace de recuperación →' }}
          </button>
        </form>

        <!-- Back to login -->
        <div v-if="!sent" class="mt-6 text-center">
          <router-link to="/login" class="text-sm text-accent hover:underline inline-flex items-center gap-1">
            ← Volver a iniciar sesión
          </router-link>
        </div>

      </div>
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { API } from '@/config/api'

const correo = ref('')
const loading = ref(false)
const errorMsg = ref('')
const sent = ref(false)

async function handleSubmit() {
  errorMsg.value = ''

  if (!correo.value) {
    return errorMsg.value = 'Ingresa tu correo electrónico'
  }

  loading.value = true
  try {
    const res = await fetch(`${API}/auth/forgot-password`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({ correo: correo.value })
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.message ?? 'Error al enviar el enlace')
    }

    sent.value = true
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}
</script>
