<template>
  <main>
    <!-- Hero -->
    <section class="min-h-[calc(100vh-4rem)] flex items-center relative overflow-hidden">
      <!-- Background grid effect -->
      <div class="absolute inset-0 opacity-[0.03]"
        style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
      </div>
      <!-- Glow -->
      <div class="absolute top-1/3 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-7xl mx-auto px-6 py-24 relative z-10">
        <div class="max-w-3xl">
          <!-- Badge -->
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-accent/20 bg-accent/5 text-accent text-xs font-medium mb-8">
            <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
            Cotización inteligente de PCs
          </div>

          <!-- Title -->
          <h1 class="text-5xl lg:text-6xl font-bold text-text-primary leading-[1.1] tracking-tight mb-6">
            Arma tu PC ideal<br/>
            <span class="text-accent">sin complicaciones</span>
          </h1>

          <!-- Description -->
          <p class="text-xl text-text-muted leading-relaxed mb-10 max-w-xl">
            Selecciona tu perfil de uso, elige componentes compatibles y obtén una cotización lista en minutos. Sin conocimientos técnicos requeridos.
          </p>

          <!-- CTAs -->
          <div class="flex items-center gap-4">
            <router-link to="/login" class="btn-primary text-base px-8 py-4">
              Armar mi PC →
            </router-link>
            <router-link to="/cotizacion" class="btn-secondary text-base px-8 py-4">
              Ver ejemplo
            </router-link>
          </div>

          <!-- Stats -->
          <div class="flex items-center gap-10 mt-16 pt-8 border-t border-dark-border">
            <div v-for="stat in stats" :key="stat.label">
              <p class="text-2xl font-bold text-text-primary font-mono">{{ stat.value }}</p>
              <p class="text-sm text-text-muted mt-0.5">{{ stat.label }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features strip -->
    <section class="border-t border-dark-border py-16">
      <div class="max-w-7xl mx-auto px-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div v-for="feature in features" :key="feature.title" class="flex items-start gap-4">
            <div class="w-10 h-10 rounded-lg bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 text-lg">
              {{ feature.icon }}
            </div>
            <div>
              <h3 class="font-semibold text-text-primary mb-1">{{ feature.title }}</h3>
              <p class="text-sm text-text-muted leading-relaxed">{{ feature.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
</template>

<script setup>
const stats = [
  { value: '+500', label: 'Componentes' },
  { value: '+30', label: 'Bodegas' },
  { value: '5min', label: 'Para cotizar' },
]

const features = [
  {
    icon: '⚡',
    title: 'Rápido y simple',
    desc: 'Selecciona tu perfil y obtén recomendaciones automáticas ajustadas a tu presupuesto.',
  },
  {
    icon: '🔧',
    title: 'Componentes compatibles',
    desc: 'Solo mostramos partes que funcionan juntas. Sin errores de compatibilidad.',
  },
  {
    icon: '📄',
    title: 'Cotización descargable',
    desc: 'Genera un PDF listo para llevar a cualquier bodega o punto de venta.',
  },
]
</script>