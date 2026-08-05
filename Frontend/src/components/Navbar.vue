<template>
  <header class="fixed top-0 left-0 right-0 z-50 px-4 sm:px-8 py-3 pointer-events-none">
    <nav
      class="pointer-events-auto max-w-7xl mx-auto rounded-full border theme-border backdrop-blur-md shadow-md transition-colors duration-200 px-6 py-3 flex items-center justify-between gap-4"
      :class="isDark ? 'bg-dark-card/90' : 'bg-white/90'"
    >
      <!-- 1. Logo -->
      <router-link :to="homeRoute" class="flex items-center gap-3 group flex-shrink-0">
        <div class="w-9 h-9 rounded-xl bg-accent flex items-center justify-center text-white font-bold text-sm shadow-sm group-hover:shadow-md transition-all duration-200">
          PC
        </div>
        <span class="font-bold text-lg tracking-tight theme-text">
          PCMATCH
        </span>
      </router-link>

      <!-- 2. Menú de navegación (desktop) -->
      <div class="hidden md:flex items-center gap-8">
        <router-link
          :to="homeRoute"
          class="text-sm font-medium transition-all py-1 relative theme-text-muted hover:text-accent"
          active-class="!text-accent font-semibold after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-accent after:rounded-full"
        >
          Inicio
        </router-link>

        <router-link
          to="/sobre-nosotros"
          class="text-sm font-medium transition-all py-1 relative theme-text-muted hover:text-accent"
          active-class="!text-accent font-semibold after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-accent after:rounded-full"
        >
          Sobre nosotros
        </router-link>

        <router-link
          to="/contacto"
          class="text-sm font-medium transition-all py-1 relative theme-text-muted hover:text-accent"
          active-class="!text-accent font-semibold after:absolute after:bottom-0 after:left-0 after:right-0 after:h-0.5 after:bg-accent after:rounded-full"
        >
          Contacto
        </router-link>
      </div>

      <!-- 3. Grupo derecho -->
      <div class="hidden md:flex items-center gap-4">
        <!-- 3a. Ícono de luna (toggle modo oscuro) -->
        <button
          @click="toggleTheme"
          class="p-2 rounded-full text-text-muted hover:text-accent transition-colors bg-transparent border-0 cursor-pointer flex items-center justify-center"
          :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
        >
          <Moon v-if="!isDark" class="w-5 h-5" />
          <Sun v-else class="w-5 h-5" />
        </button>

        <!-- 3b. Botón "Armar mi PC" -->
        <router-link
          to="/armar"
          class="border border-accent text-accent hover:bg-accent/10 px-4 py-2 rounded-full font-medium text-sm flex items-center gap-2 transition-all cursor-pointer"
        >
          <Monitor class="w-4 h-4" />
          <span>Armar mi PC</span>
        </router-link>

        <!-- 3c. Trigger usuario + Dropdown (si hay sesión) O Botón Iniciar sesión (si no hay sesión) -->
        <div v-if="isLoggedIn" class="relative" ref="userDropdownRef">
          <button
            @click="userMenuOpen = !userMenuOpen"
            class="flex items-center gap-2 px-3 py-1.5 rounded-full theme-text-muted hover:text-accent transition-colors cursor-pointer bg-transparent border-0"
          >
            <User class="w-4 h-4" />
            <span class="text-sm font-medium theme-text">{{ userDisplayName }}</span>
            <ChevronDown class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': userMenuOpen }" />
          </button>

          <!-- Dropdown menu -->
          <Transition name="fade">
            <div
              v-if="userMenuOpen"
              class="absolute right-0 mt-2 w-48 rounded-xl theme-card border theme-border shadow-lg py-2 z-50 animate-fade-in"
            >
              <router-link
                v-if="adminPanelRoute"
                :to="adminPanelRoute"
                @click="userMenuOpen = false"
                class="block px-4 py-2 text-sm text-accent font-semibold hover:bg-gray-100 dark:hover:bg-dark-border/50 transition-colors"
              >
                Panel de control
              </router-link>
              <router-link
                to="/mi-perfil"
                @click="userMenuOpen = false"
                class="block px-4 py-2 text-sm theme-text hover:bg-gray-100 dark:hover:bg-dark-border/50 transition-colors"
              >
                Mi perfil
              </router-link>
              <button
                @click="handleLogout"
                class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-gray-100 dark:hover:bg-dark-border/50 transition-colors bg-transparent border-0 cursor-pointer"
              >
                Cerrar sesión
              </button>
            </div>
          </Transition>
        </div>

        <router-link
          v-else
          to="/login"
          class="btn-primary text-sm rounded-full px-5 py-2"
        >
          Iniciar sesión
        </router-link>
      </div>

      <!-- Mobile Menu Toggle Button -->
      <div class="flex items-center gap-2 md:hidden">
        <button
          @click="toggleTheme"
          class="p-2 rounded-full text-text-muted hover:text-accent transition-colors bg-transparent border-0 cursor-pointer"
        >
          <Moon v-if="!isDark" class="w-5 h-5" />
          <Sun v-else class="w-5 h-5" />
        </button>
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          class="p-2 rounded-lg text-text-muted hover:text-accent transition-colors bg-transparent border-0 cursor-pointer"
        >
          <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>
    </nav>

    <!-- Mobile Dropdown Menu -->
    <Transition name="mobile-menu">
      <div
        v-if="mobileMenuOpen"
        class="md:hidden mt-2 max-w-7xl mx-auto rounded-2xl border theme-border shadow-lg p-4 space-y-3 transition-colors backdrop-blur-md"
        :class="isDark ? 'bg-dark-card' : 'bg-white'"
      >
        <router-link
          :to="homeRoute"
          @click="mobileMenuOpen = false"
          class="block px-4 py-2.5 rounded-lg text-sm font-medium theme-text-muted hover:text-accent transition-colors"
          active-class="!text-accent font-semibold"
        >
          Inicio
        </router-link>
        <router-link
          to="/sobre-nosotros"
          @click="mobileMenuOpen = false"
          class="block px-4 py-2.5 rounded-lg text-sm font-medium theme-text-muted hover:text-accent transition-colors"
          active-class="!text-accent font-semibold"
        >
          Sobre nosotros
        </router-link>
        <router-link
          to="/contacto"
          @click="mobileMenuOpen = false"
          class="block px-4 py-2.5 rounded-lg text-sm font-medium theme-text-muted hover:text-accent transition-colors"
          active-class="!text-accent font-semibold"
        >
          Contacto
        </router-link>
        <router-link
          to="/armar"
          @click="mobileMenuOpen = false"
          class="flex items-center gap-2 px-4 py-2.5 rounded-full border border-accent text-accent font-medium text-sm justify-center"
        >
          <Monitor class="w-4 h-4" />
          <span>Armar mi PC</span>
        </router-link>
        <div class="pt-2 border-t theme-border">
          <div class="flex items-center justify-between px-4 py-2">
            <span class="text-sm font-medium theme-text flex items-center gap-2">
              <User class="w-4 h-4" /> {{ userDisplayName }}
            </span>
            <div class="flex items-center gap-3">
              <router-link
                v-if="isLoggedIn && adminPanelRoute"
                :to="adminPanelRoute"
                @click="mobileMenuOpen = false"
                class="text-xs text-accent font-semibold"
              >
                Panel
              </router-link>
              <router-link
                v-if="isLoggedIn"
                to="/mi-perfil"
                @click="mobileMenuOpen = false"
                class="text-xs theme-text-muted font-medium hover:text-accent"
              >
                Perfil
              </router-link>
            </div>
          </div>
          <button
            v-if="isLoggedIn"
            @click="handleLogout()"
            class="w-full text-left px-4 py-2 text-sm text-red-500 hover:bg-gray-100 dark:hover:bg-dark-border/50 rounded-lg transition-colors bg-transparent border-0 cursor-pointer"
          >
            Cerrar sesión
          </button>
          <router-link
            v-else
            to="/login"
            @click="mobileMenuOpen = false"
            class="block text-center px-4 py-2 text-sm text-accent font-medium"
          >
            Iniciar sesión
          </router-link>
        </div>
      </div>
    </Transition>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Monitor, Moon, Sun, User, ChevronDown } from 'lucide-vue-next'

import { useAuth } from '../composables/useAuth'
import { useTheme } from '../composables/useTheme'

const router = useRouter()
const route = useRoute()
const { isLoggedIn, user, logout } = useAuth()
const { isDark, toggleTheme } = useTheme()

const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)
const userDropdownRef = ref(null)

const userDisplayName = computed(() => {
  return user.value?.nombre || user.value?.username || 'Usuario'
})

const homeRoute = computed(() => {
  if (!isLoggedIn.value) return '/'
  const rol = user.value?.rol
  if (rol === 'superadmin') return '/superadmin'
  if (rol === 'admin') return '/admin'
  if (rol === 'bodega') return '/bodega'
  if (rol === 'proveedor') return '/proveedor'
  return '/inicio'
})

const adminPanelRoute = computed(() => {
  if (!isLoggedIn.value) return null
  const rol = user.value?.rol
  if (rol === 'superadmin') return '/superadmin'
  if (rol === 'admin') return '/admin'
  if (rol === 'bodega') return '/bodega'
  if (rol === 'proveedor') return '/proveedor'
  return null
})

function handleLogout() {
  logout()
  userMenuOpen.value = false
  mobileMenuOpen.value = false
  router.push('/')
}

function handleClickOutside(event) {
  if (userDropdownRef.value && !userDropdownRef.value.contains(event.target)) {
    userMenuOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})
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