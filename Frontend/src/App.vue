<template>
  <div class="min-h-screen theme-bg">
    <Navbar v-if="!isAdminRoute" />
    <router-view />
    <ToastContainer />
    <ChatbotWidget v-if="showChatbot" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from './components/Navbar.vue'
import ToastContainer from './components/ToastContainer.vue'
import ChatbotWidget from './components/ChatbotWidget.vue'
import { useTheme } from './composables/useTheme'
import { useAuth } from './composables/useAuth'

// Initialize theme on app mount
useTheme()

const route = useRoute()
const { isLoggedIn, user } = useAuth()

const isAdminRoute = computed(() =>
  route.path.startsWith('/admin') ||
  route.path.startsWith('/bodega') ||
  route.path.startsWith('/superadmin') ||
  route.path.startsWith('/proveedor')
)

const showChatbot = computed(() =>
  !isAdminRoute.value &&
  route.path !== '/' &&
  isLoggedIn.value &&
  user.value?.rol === 'cliente'
)
</script>