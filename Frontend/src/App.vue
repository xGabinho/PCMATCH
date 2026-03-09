<template>
  <div class="min-h-screen bg-dark-bg">
    <Navbar v-if="!isAdminRoute" />
    <router-view />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from './components/Navbar.vue'

const route = useRoute()
const isAdminRoute = computed(() => route.path.startsWith('/admin') || route.path.startsWith('/bodega'))
</script>