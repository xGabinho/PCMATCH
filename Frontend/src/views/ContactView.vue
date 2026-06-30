<template>
  <main class="max-w-3xl mx-auto px-6 py-16">
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold theme-text mb-4">Contacto</h1>
      <p class="text-lg theme-text-muted">¿Tienes alguna duda o sugerencia? Escríbenos y te responderemos a la brevedad.</p>
    </div>

    <div class="card-dark rounded-2xl p-8 shadow-sm">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        
        <!-- Nombre -->
        <div>
          <label for="name" class="block text-sm font-medium theme-text mb-2">Nombre completo</label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            required
            placeholder="Ej. Juan Pérez"
            class="w-full theme-bg border theme-border rounded-lg px-4 py-3 theme-text focus:outline-none focus:border-accent transition-colors"
          />
        </div>

        <!-- Email -->
        <div>
          <label for="email" class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            required
            placeholder="ejemplo@correo.com"
            class="w-full theme-bg border theme-border rounded-lg px-4 py-3 theme-text focus:outline-none focus:border-accent transition-colors"
          />
        </div>

        <!-- Mensaje -->
        <div>
          <label for="message" class="block text-sm font-medium theme-text mb-2">Mensaje</label>
          <textarea
            id="message"
            v-model="form.message"
            required
            rows="5"
            placeholder="Escribe tu mensaje aquí..."
            class="w-full theme-bg border theme-border rounded-lg px-4 py-3 theme-text focus:outline-none focus:border-accent transition-colors resize-y"
          ></textarea>
        </div>

        <!-- Botón Enviar -->
        <button
          type="submit"
          :disabled="isSubmitting"
          class="btn-primary w-full py-4 text-base font-medium flex items-center justify-center gap-2 disabled:opacity-70"
        >
          <span v-if="isSubmitting">Enviando...</span>
          <span v-else>Enviar mensaje</span>
        </button>

        <!-- Feedback UI (mocked) -->
        <div v-if="showSuccess" class="mt-4 p-4 bg-green-500/10 border border-green-500/20 text-green-500 text-sm rounded-lg text-center">
          ¡Gracias por contactarnos! Tu mensaje ha sido enviado exitosamente.
        </div>

      </form>
    </div>
  </main>
</template>

<script setup>
import { ref, reactive } from 'vue'

const form = reactive({
  name: '',
  email: '',
  message: ''
})

const isSubmitting = ref(false)
const showSuccess = ref(false)

const handleSubmit = () => {
  // Lógica mockeada, solo UI por ahora
  isSubmitting.value = true
  showSuccess.value = false

  setTimeout(() => {
    isSubmitting.value = false
    showSuccess.value = true
    // Limpiar formulario
    form.name = ''
    form.email = ''
    form.message = ''
    
    // Ocultar mensaje después de 3 segundos
    setTimeout(() => {
      showSuccess.value = false
    }, 3000)
  }, 1000)
}
</script>
