<template>
  <main class="min-h-screen theme-bg">

    <!-- Header Banner -->
    <section class="relative overflow-hidden border-b theme-border">
      <div class="absolute inset-0 opacity-[0.03]"
        style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
      </div>
      <div class="absolute right-0 top-0 w-[500px] h-[300px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-3xl mx-auto px-6 pt-28 pb-10 relative z-10">
        <div class="flex items-center gap-5">
          <!-- Avatar -->
          <div class="w-16 h-16 rounded-2xl bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-2xl font-bold flex-shrink-0 select-none">
            {{ initials }}
          </div>
          <div>
            <h1 class="text-2xl font-bold theme-text tracking-tight">Mi Perfil</h1>
            <p class="theme-text-muted text-sm mt-1">
              Gestiona tu información personal · <span class="text-accent capitalize">{{ rolLabel }}</span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Loading State -->
    <div v-if="loadingProfile" class="max-w-3xl mx-auto px-6 py-20 text-center">
      <div class="inline-flex items-center gap-3 theme-text-muted text-sm">
        <svg class="animate-spin h-5 w-5 text-accent" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        Cargando perfil...
      </div>
    </div>

    <!-- Profile Form -->
    <div v-else class="max-w-3xl mx-auto px-6 py-10">
      <form @submit.prevent="handleSave" class="space-y-8">

        <!-- Personal Info Card -->
        <div class="card-dark rounded-2xl p-5 md:p-8">
          <h2 class="text-lg font-semibold theme-text mb-6 flex items-center gap-2">
            <span class="text-accent">●</span> Información Personal
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Nombre -->
            <div>
              <label for="profile-nombre" class="block text-sm font-medium theme-text-muted mb-2">Nombre</label>
              <input
                id="profile-nombre"
                v-model="form.nombre"
                type="text"
                class="theme-input"
                :class="fieldErrors.nombre ? 'border-red-500 focus:border-red-500' : ''"
                placeholder="Tu nombre"
              />
              <p v-if="fieldErrors.nombre" class="text-red-400 text-xs mt-1.5">{{ fieldErrors.nombre[0] }}</p>
            </div>

            <!-- Apellido (solo Usuario) -->
            <div v-if="tipo === 'usuario'">
              <label for="profile-apellido" class="block text-sm font-medium theme-text-muted mb-2">Apellido</label>
              <input
                id="profile-apellido"
                v-model="form.apellido"
                type="text"
                class="theme-input"
                placeholder="Tu apellido"
              />
            </div>

            <!-- Correo -->
            <div>
              <label for="profile-correo" class="block text-sm font-medium theme-text-muted mb-2">Correo electrónico</label>
              <input
                id="profile-correo"
                v-model="form.correo"
                type="email"
                class="theme-input"
                :class="fieldErrors.correo ? 'border-red-500 focus:border-red-500' : ''"
                placeholder="correo@ejemplo.com"
              />
              <p v-if="fieldErrors.correo" class="text-red-400 text-xs mt-1.5">{{ fieldErrors.correo[0] }}</p>
            </div>

            <!-- Teléfono (Usuario o Bodega) -->
            <div v-if="tipo !== 'proveedor'">
              <label for="profile-telefono" class="block text-sm font-medium theme-text-muted mb-2">Teléfono</label>
              <input
                id="profile-telefono"
                v-model="form.telefono"
                type="text"
                class="theme-input"
                placeholder="+57 300 123 4567"
              />
            </div>
          </div>
        </div>

        <!-- Password Change Card -->
        <div class="card-dark rounded-2xl p-5 md:p-8">
          <button
            type="button"
            @click="showPasswordSection = !showPasswordSection"
            class="w-full flex items-center justify-between text-left group"
          >
            <h2 class="text-lg font-semibold theme-text flex items-center gap-2">
              <span class="text-accent">●</span> Cambiar Contraseña
            </h2>
            <svg
              class="w-5 h-5 theme-text-muted transition-transform duration-200"
              :class="{ 'rotate-180': showPasswordSection }"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>

          <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-96"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 max-h-96"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-if="showPasswordSection" class="mt-6 space-y-5 overflow-hidden">
              <p class="theme-text-muted text-sm">Deja estos campos vacíos si no deseas cambiar tu contraseña.</p>

              <!-- Contraseña actual -->
              <div>
                <label for="profile-password-actual" class="block text-sm font-medium theme-text-muted mb-2">Contraseña actual</label>
                <input
                  id="profile-password-actual"
                  v-model="form.password_actual"
                  type="password"
                  class="theme-input"
                  :class="fieldErrors.password_actual ? 'border-red-500 focus:border-red-500' : ''"
                  placeholder="••••••••"
                  autocomplete="current-password"
                />
                <p v-if="fieldErrors.password_actual" class="text-red-400 text-xs mt-1.5">{{ fieldErrors.password_actual[0] }}</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nueva contraseña -->
                <div>
                  <label for="profile-password-new" class="block text-sm font-medium theme-text-muted mb-2">Nueva contraseña</label>
                  <input
                    id="profile-password-new"
                    v-model="form.password"
                    type="password"
                    class="theme-input"
                    :class="fieldErrors.password ? 'border-red-500 focus:border-red-500' : ''"
                    placeholder="Mínimo 8 caracteres"
                    autocomplete="new-password"
                  />
                  <p v-if="fieldErrors.password" class="text-red-400 text-xs mt-1.5">{{ fieldErrors.password[0] }}</p>
                </div>

                <!-- Confirmar contraseña -->
                <div>
                  <label for="profile-password-confirm" class="block text-sm font-medium theme-text-muted mb-2">Confirmar contraseña</label>
                  <input
                    id="profile-password-confirm"
                    v-model="form.password_confirm"
                    type="password"
                    class="theme-input"
                    :class="passwordMismatch ? 'border-red-500 focus:border-red-500' : ''"
                    placeholder="Repite la contraseña"
                    autocomplete="new-password"
                  />
                  <p v-if="passwordMismatch" class="text-red-400 text-xs mt-1.5">Las contraseñas no coinciden</p>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <!-- Actions -->
        <div class="flex flex-col-reverse sm:flex-row items-center justify-between gap-4 pt-2 pb-10">
          <router-link
            :to="backRoute"
            class="btn-ghost text-sm px-5 py-2.5 w-full sm:w-auto text-center"
          >
            ← Volver
          </router-link>

          <button
            type="submit"
            class="btn-primary text-sm px-8 py-3 w-full sm:w-auto flex justify-center items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="saving || passwordMismatch"
          >
            <svg v-if="saving" class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ saving ? 'Guardando...' : 'Guardar Cambios' }}
          </button>
        </div>

      </form>
    </div>

  </main>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

import { API } from '@/config/api'
const { user, getToken, updateUser } = useAuth()
const toast = useToast()

const loadingProfile = ref(true)
const saving = ref(false)
const showPasswordSection = ref(false)
const tipo = ref('usuario')
const fieldErrors = ref({})

const form = ref({
  nombre: '',
  apellido: '',
  correo: '',
  telefono: '',
  password_actual: '',
  password: '',
  password_confirm: '',
})

const initials = computed(() => {
  const n = form.value.nombre?.charAt(0) || ''
  const a = form.value.apellido?.charAt(0) || ''
  return (n + a).toUpperCase() || '?'
})

const rolLabel = computed(() => {
  const labels = {
    cliente: 'Cliente',
    admin: 'Administrador',
    superadmin: 'Super Admin',
    bodega: 'Bodega',
    proveedor: 'Proveedor',
  }
  return labels[user.value?.rol] || user.value?.rol || ''
})

const backRoute = computed(() => {
  const rol = user.value?.rol
  if (rol === 'superadmin') return '/superadmin'
  if (rol === 'admin') return '/admin'
  if (rol === 'bodega') return '/bodega'
  if (rol === 'proveedor') return '/proveedor'
  return '/inicio'
})

const passwordMismatch = computed(() => {
  return !!(form.value.password && form.value.password_confirm && form.value.password !== form.value.password_confirm)
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchProfile() {
  loadingProfile.value = true
  try {
    const res = await fetch(`${API}/auth/profile`, {
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`
      }
    })
    const data = await res.json()
    if (res.ok && data.perfil) {
      tipo.value = data.tipo || 'usuario'
      const p = data.perfil
      form.value.nombre = p.nombre || ''
      form.value.apellido = p.apellido || ''
      form.value.correo = p.correo || ''
      form.value.telefono = p.telefono || ''
    } else {
      toast.error(data?.message || 'Error al cargar el perfil')
    }
  } catch (e) {
    toast.error('No se pudo conectar con el servidor')
  } finally {
    loadingProfile.value = false
  }
}

async function handleSave() {
  fieldErrors.value = {}

  // Client-side password match check
  if (form.value.password && form.value.password !== form.value.password_confirm) {
    return
  }

  saving.value = true

  const body = {
    nombre: form.value.nombre,
    correo: form.value.correo,
  }

  if (tipo.value === 'usuario') {
    body.apellido = form.value.apellido
    body.telefono = form.value.telefono
  }

  if (tipo.value === 'bodega') {
    body.telefono = form.value.telefono
  }

  if (form.value.password) {
    body.password_actual = form.value.password_actual
    body.password = form.value.password
  }

  try {
    const res = await fetch(`${API}/auth/profile`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${getToken()}`
      },
      body: JSON.stringify(body)
    })

    const data = await res.json()

    if (res.ok && data.success) {
      toast.success(data.message || 'Perfil actualizado')

      // Sync auth state
      updateUser(data.perfil)

      // Clear password fields
      form.value.password_actual = ''
      form.value.password = ''
      form.value.password_confirm = ''
      showPasswordSection.value = false
    } else {
      toast.error(data.message || 'Error al actualizar el perfil')
      if (data.errors) {
        fieldErrors.value = data.errors
      }
    }
  } catch (e) {
    toast.error('No se pudo conectar con el servidor')
  } finally {
    saving.value = false
  }
}

onMounted(fetchProfile)
</script>
