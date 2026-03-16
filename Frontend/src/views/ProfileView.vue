<template>
  <main class="max-w-5xl mx-auto px-6 py-16">
    <!-- Header -->
    <div class="text-center mb-12">
      <p class="text-accent text-sm font-medium uppercase tracking-widest mb-3">Paso 1 de 4</p>
      <h1 class="text-4xl font-bold text-text-primary mb-4">¿Para qué usarás tu PC?</h1>
      <p class="text-text-muted text-lg max-w-lg mx-auto">
        Elige el perfil que mejor describe tu uso principal. Esto nos ayuda a recomendarte los componentes ideales.
      </p>
    </div>

    <!-- Profile Cards Grid -->
    <div class="grid grid-cols-2 gap-5 max-w-3xl mx-auto mb-12">
      <div
        v-for="profile in profiles"
        :key="profile.id"
        @click="selected = profile.id"
        class="card-dark rounded-2xl p-8 cursor-pointer transition-all duration-200 hover:border-accent/40 group"
        :class="selected === profile.id
          ? 'border-accent shadow-xl shadow-accent/10 bg-accent/5'
          : 'hover:shadow-lg hover:shadow-accent/5'"
      >
        <!-- Icon -->
        <div class="text-4xl mb-5">{{ profile.icon }}</div>

        <!-- Name -->
        <h2 class="text-xl font-bold text-text-primary mb-2 group-hover:text-accent transition-colors"
          :class="{ 'text-accent': selected === profile.id }">
          {{ profile.name }}
        </h2>

        <!-- Description -->
        <p class="text-text-muted text-sm leading-relaxed mb-4">{{ profile.desc }}</p>

        <!-- Tags -->
        <div class="flex flex-wrap gap-2">
          <span
            v-for="tag in profile.tags"
            :key="tag"
            class="text-xs px-2.5 py-1 rounded-full bg-dark-bg border border-dark-border text-text-muted"
            :class="{ 'border-accent/30 text-accent bg-accent/5': selected === profile.id }"
          >
            {{ tag }}
          </span>
        </div>

        <!-- Selected indicator -->
        <div v-if="selected === profile.id" class="mt-4 flex items-center gap-2 text-accent text-sm font-medium">
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
          </svg>
          Seleccionado
        </div>
      </div>
    </div>

    <!-- Action -->
    <div class="text-center">
      <router-link
        to="/armar"
        class="btn-primary text-base px-10 py-4 inline-block"
        :class="{ 'opacity-50 pointer-events-none': !selected }"
      >
        Continuar →
      </router-link>
      <p class="text-text-muted text-sm mt-4">
        {{ selected ? `Perfil seleccionado: ${profiles.find(p => p.id === selected)?.name}` : 'Selecciona un perfil para continuar' }}
      </p>
    </div>
  </main>
</template>

<script setup>
import { ref } from 'vue'
import { useBuilder } from '../composables/useBuilder'

const { perfil } = useBuilder()
const selected = ref(perfil.value || null)

const profiles = [
  { id: 'office',  icon: '💼', name: 'Oficina',          desc: 'Para trabajo diario, documentos, correos y videollamadas.',              tags: ['Office', 'Zoom', 'Chrome', 'PDF']             },
  { id: 'gaming',  icon: '🎮', name: 'Gaming',           desc: 'Para juegos exigentes con gráficos de alta calidad y alto rendimiento.',  tags: ['AAA Games', 'Streaming', '1080p+', 'FPS alto'] },
  { id: 'design',  icon: '🎨', name: 'Diseño / Edición', desc: 'Para edición de video, diseño gráfico y renderizado 3D.',                tags: ['Premiere', 'Photoshop', 'Blender', '4K']      },
  { id: 'study',   icon: '📚', name: 'Estudio',          desc: 'Para tareas, clases virtuales, investigación y programación.',           tags: ['Clases online', 'Tareas', 'Programar', 'Navegador'] },
]

function selectProfile(id) {
  selected.value = id
  perfil.value   = id
}
</script>