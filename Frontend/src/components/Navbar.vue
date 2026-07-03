<template>
  <nav class="sticky top-0 z-50 border-b theme-border backdrop-blur-md transition-colors duration-200"
    :class="isDark ? 'bg-dark-bg/90' : 'bg-white/90'">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">

      <!-- Logo -->
      <router-link to="/" class="flex items-center gap-2.5 group flex-shrink-0">
        <div class="w-8 h-8 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-sm group-hover:shadow-lg group-hover:shadow-accent/30 transition-all duration-200">
          PC
        </div>
        <span class="font-semibold text-lg tracking-tight theme-text hidden sm:block">
          PCMATCH
        </span>
      </router-link>

      <!-- Center Nav (desktop) -->
      <div class="hidden md:flex items-center gap-1">
        <router-link
          :to="isLoggedIn ? '/inicio' : '/'"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150"
          :class="isDark
            ? 'text-text-muted hover:text-text-primary hover:bg-dark-card'
            : 'text-text-light-muted hover:text-text-light hover:bg-gray-100'"
          active-class="!text-accent"
        >
          Inicio
        </router-link>
        <router-link
          v-if="isLoggedIn"
          to="/asistente"
          class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150 flex items-center gap-1.5"
          :class="isDark
            ? 'text-text-muted hover:text-text-primary hover:bg-dark-card'
            : 'text-text-light-muted hover:text-text-light hover:bg-gray-100'"
          active-class="!text-accent"
        >
          🤖 Asistente
        </router-link>
      </div>

      <!-- Right side -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Theme toggle -->
        <button
          @click="toggleTheme"
          class="w-9 h-9 rounded-lg flex items-center justify-center transition-all duration-200"
          :class="isDark
            ? 'text-text-muted hover:text-yellow-400 hover:bg-dark-card'
            : 'text-text-light-muted hover:text-amber-500 hover:bg-gray-100'"
          :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        >
          <svg v-if="isDark" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
          </svg>
        </button>

        <!-- Desktop CTA buttons -->
        <div class="hidden sm:flex items-center gap-3">
          <!-- Not logged in -->
          <router-link
            v-if="!isLoggedIn && route.path !== '/login'"
            to="/login"
            class="btn-primary text-sm"
          >
            Iniciar sesión
          </router-link>

          <!-- Client: build PC -->
          <router-link
            v-if="isLoggedIn && user?.rol === 'cliente'"
            to="/armar"
            class="btn-secondary text-sm"
          >
            Armar mi PC
          </router-link>

          <!-- Profile -->
          <router-link
            v-if="isLoggedIn"
            to="/mi-perfil"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150"
            :class="isDark
              ? 'text-text-muted hover:text-accent hover:bg-dark-card'
              : 'text-text-light-muted hover:text-accent hover:bg-gray-100'"
            active-class="!text-accent"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Mi perfil
          </router-link>

          <!-- Logout -->
          <button
            v-if="isLoggedIn"
            @click="handleLogout"
            class="btn-secondary text-sm"
          >
            Cerrar sesión
          </button>
        </div>

        <!-- Mobile hamburger -->
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="sm:hidden w-9 h-9 rounded-lg flex items-center justify-center transition-all duration-200"
          :class="isDark
            ? 'text-text-muted hover:text-text-primary hover:bg-dark-card'
            : 'text-text-light-muted hover:text-text-light hover:bg-gray-100'"
        >
          <svg v-if="!mobileMenuOpen" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile menu dropdown -->
    <Transition name="mobile-menu">
      <div
        v-if="mobileMenuOpen"
        class="sm:hidden border-t theme-border px-4 pb-4 pt-2 space-y-1"
        :class="isDark ? 'bg-dark-bg' : 'bg-white'"
      >
        <router-link
          :to="isLoggedIn ? '/inicio' : '/'"
          @click="mobileMenuOpen = false"
          class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors min-h-[44px] flex items-center"
          :class="isDark
            ? 'text-text-muted hover:text-text-primary hover:bg-dark-card'
            : 'text-text-light-muted hover:text-text-light hover:bg-gray-100'"
        >
          Inicio
        </router-link>

        <router-link
          v-if="isLoggedIn"
          to="/asistente"
          @click="mobileMenuOpen = false"
          class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors min-h-[44px] flex items-center gap-2"
          :class="isDark
            ? 'text-accent hover:bg-dark-card'
            : 'text-accent hover:bg-gray-100'"
        >
          🤖 Asistente inteligente
        </router-link>

        <router-link
          v-if="!isLoggedIn && route.path !== '/login'"
          to="/login"
          @click="mobileMenuOpen = false"
          class="block px-4 py-3 rounded-lg text-sm font-medium bg-accent text-white text-center min-h-[44px] flex items-center justify-center"
        >
          Iniciar sesión
        </router-link>

        <router-link
          v-if="isLoggedIn && user?.rol === 'cliente'"
          to="/armar"
          @click="mobileMenuOpen = false"
          class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors min-h-[44px] flex items-center"
          :class="isDark
            ? 'text-accent hover:bg-dark-card'
            : 'text-accent hover:bg-gray-100'"
        >
          🖥️ Armar mi PC
        </router-link>

        <router-link
          v-if="isLoggedIn"
          to="/mi-perfil"
          @click="mobileMenuOpen = false"
          class="block px-4 py-3 rounded-lg text-sm font-medium transition-colors min-h-[44px] flex items-center gap-2"
          :class="isDark
            ? 'text-text-muted hover:text-text-primary hover:bg-dark-card'
            : 'text-text-light-muted hover:text-text-light hover:bg-gray-100'"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Mi perfil
        </router-link>

        <button
          v-if="isLoggedIn"
          @click="handleLogout; mobileMenuOpen = false"
          class="w-full px-4 py-3 rounded-lg text-sm font-medium text-left transition-colors min-h-[44px] flex items-center gap-2"
          :class="isDark
            ? 'text-red-400 hover:bg-red-500/10'
            : 'text-red-500 hover:bg-red-50'"
        >
          Cerrar sesión
        </button>
      </div>
    </Transition>
  </nav>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useTheme } from '../composables/useTheme'

const router = useRouter()
const route = useRoute()
const { isLoggedIn, user, logout } = useAuth()
const { isDark, toggleTheme } = useTheme()

const mobileMenuOpen = ref(false)

function handleLogout() {
  logout()
  mobileMenuOpen.value = false
  router.push('/')
}
</script>

<style scoped>
.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  max-height: 0;
}
.mobile-menu-enter-to,
.mobile-menu-leave-from {
  opacity: 1;
  max-height: 400px;
}
</style>