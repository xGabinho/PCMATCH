<template>
  <nav class="sticky top-0 z-50 border-b border-dark-border bg-dark-bg/90 backdrop-blur-md">
    <div class="max-w-7xl mx-auto px-6 h-16 grid grid-cols-3 items-center">

      <!-- Logo (izquierda) -->
      <router-link to="/" class="flex items-center gap-2.5 group justify-self-start">
        <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-sm group-hover:shadow-lg group-hover:shadow-accent/30 transition-all duration-200">
          PC
        </div>
        <span class="font-semibold text-text-primary text-lg tracking-tight">
          PCMATCH
        </span>
      </router-link>

      <!-- Nav Links (centro) -->
      <div class="flex items-center gap-1 justify-self-center">
        <router-link
          :to="isLoggedIn ? '/inicio' : '/'"
          class="px-4 py-2 rounded-lg text-sm font-medium text-text-muted hover:text-text-primary hover:bg-dark-card transition-all duration-150"
          active-class="text-text-primary bg-dark-card"
        >
          Inicio
        </router-link>
      </div>
<!-- CTA (derecha) -->
<div class="flex items-center gap-3 justify-self-end">
  <!-- No logueado -->
  <router-link
    v-if="!isLoggedIn && route.path !== '/login'"
    to="/login"
    class="btn-primary text-sm"
  >
    Iniciar sesión
  </router-link>

  <!-- Logueado como cliente -->
  <router-link
    v-if="isLoggedIn && user?.rol === 'cliente'"
    to="/armar"
    class="btn-secondary text-sm"
  >
    Armar mi PC
  </router-link>

  <!-- Cerrar sesión — todos los roles -->
  <button
    v-if="isLoggedIn"
    @click="handleLogout"
    class="btn-secondary text-sm"
  >
    Cerrar sesión
  </button>
</div>

    </div>
  </nav>
</template>

<script setup>
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const route = useRoute()
const { isLoggedIn, user, logout } = useAuth()

function handleLogout() {
  logout()
  router.push('/')
}
</script>