<template>
  <div class="min-h-screen bg-dark-bg flex items-center justify-center p-4">
    <div class="w-full max-w-md">

      <!-- Logo -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold text-accent">PCMATCH</h1>
        <p class="text-text-muted mt-2">Sistema de Gestión</p>
      </div>

      <!-- Card -->
      <div class="card-dark">
        <h2 class="text-xl font-bold text-text-primary mb-6">Iniciar sesión</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm text-text-muted mb-1">Correo</label>
            <input
              v-model="form.correo"
              type="email"
              placeholder="admin@pcmatch.com"
              class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-text-primary focus:outline-none focus:border-accent transition-colors"
              @keyup.enter="iniciarSesion"
            />
          </div>

          <div>
            <label class="block text-sm text-text-muted mb-1">Contraseña</label>
            <input
              v-model="form.password"
              type="password"
              placeholder="••••••••"
              class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-2.5 text-text-primary focus:outline-none focus:border-accent transition-colors"
              @keyup.enter="iniciarSesion"
            />
          </div>
        </div>

        <div v-if="error" class="mt-4 p-3 rounded-lg bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
          {{ error }}
        </div>

        <button
          class="btn-primary w-full mt-6"
          @click="iniciarSesion"
          :disabled="cargando"
        >
          {{ cargando ? 'Ingresando...' : 'Ingresar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router  = useRouter()
const { login } = useAuth()

const form     = ref({ correo: '', password: '' })
const error    = ref('')
const cargando = ref(false)

async function iniciarSesion() {
    if (!form.value.correo || !form.value.password) {
        error.value = 'Ingresa tu correo y contraseña.'
        return
    }

    cargando.value = true
    error.value    = ''

    try {
        const res  = await fetch('http://localhost/pcmatch/backend/api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form.value)
        })
        const data = await res.json()

        if (!res.ok) {
            error.value = data.error || 'Error al iniciar sesión.'
            return
        }

        login(data.token, data.usuario)

        if (data.usuario.rol === 'admin') {
            router.push('/admin')
        } else if (data.usuario.rol === 'bodega' || data.usuario.rol === 'proveedor') {
            router.push('/proveedor')
        } else {
            error.value = 'Acceso no permitido.'
        }

    } catch {
        error.value = 'No se pudo conectar con el servidor. ¿Está XAMPP corriendo?'
    } finally {
        cargando.value = false
    }
}
</script>