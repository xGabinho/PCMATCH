<template>
  <div class="min-h-screen theme-bg">
    <Navbar v-if="!isAdminRoute" />
    <router-view />
    <ToastContainer />
    <ChatbotWidget v-if="!isAdminRoute" />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from './components/Navbar.vue'
import ToastContainer from './components/ToastContainer.vue'
import ChatbotWidget from './components/ChatbotWidget.vue'
import { useTheme } from './composables/useTheme'

// Initialize theme on app mount
useTheme()

const route = useRoute()
const isAdminRoute = computed(() =>
  route.path.startsWith('/admin') ||
  route.path.startsWith('/bodega') ||
  route.path.startsWith('/superadmin') ||
  route.path.startsWith('/proveedor')
)
</script>