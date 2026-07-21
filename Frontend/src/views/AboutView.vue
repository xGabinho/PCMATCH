<template>
  <main class="max-w-4xl mx-auto px-6 py-16">
    <div class="text-center mb-16">
      <h1 class="text-4xl font-bold theme-text mb-4">Sobre Nosotros</h1>
      <p class="text-lg theme-text-muted">Conoce más sobre la plataforma líder en cotización de PCs en Chile.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center mb-16">
      <div>
        <h2 class="text-2xl font-bold theme-text mb-4">Nuestra Misión</h2>
        <p class="theme-text-muted leading-relaxed mb-4">
          En PCMATCH, nuestro objetivo es democratizar el acceso al hardware de computadoras. Creemos que armar un PC no debería requerir conocimientos técnicos avanzados ni semanas de investigación.
        </p>
        <p class="theme-text-muted leading-relaxed">
          Nuestra plataforma inteligente te ayuda a encontrar los componentes compatibles perfectos para tus necesidades, buscando siempre el mejor precio entre las bodegas y proveedores de hardware más confiables del país.
        </p>
      </div>
      <div class="rounded-2xl bg-accent/5 p-8 border theme-border flex items-center justify-center">
        <Rocket class="w-24 h-24 text-accent" />
      </div>
    </div>

    <div class="card-dark rounded-2xl p-8 mb-16">
      <h2 class="text-2xl font-bold theme-text mb-6 text-center">¿Por qué elegir PCMATCH?</h2>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
        <div class="text-center">
          <div class="w-12 h-12 rounded-full bg-accent/10 text-accent flex items-center justify-center mx-auto mb-4">
            <Shield class="w-6 h-6" />
          </div>
          <h3 class="font-bold theme-text mb-2">Compatibilidad</h3>
          <p class="text-sm theme-text-muted">Nuestro motor verifica que cada pieza encaje perfectamente con el resto.</p>
        </div>
        <div class="text-center">
          <div class="w-12 h-12 rounded-full bg-accent/10 text-accent flex items-center justify-center mx-auto mb-4">
            <CircleDollarSign class="w-6 h-6" />
          </div>
          <h3 class="font-bold theme-text mb-2">Mejores Precios</h3>
          <p class="text-sm theme-text-muted">Comparamos precios de diferentes bodegas para optimizar tu presupuesto.</p>
        </div>
        <div class="text-center">
          <div class="w-12 h-12 rounded-full bg-accent/10 text-accent flex items-center justify-center mx-auto mb-4">
            <Zap class="w-6 h-6" />
          </div>
          <h3 class="font-bold theme-text mb-2">Rapidez</h3>
          <p class="text-sm theme-text-muted">Obtén tu cotización en formato PDF en menos de 5 minutos.</p>
        </div>
      </div>
    </div>
  </main>
</template>

<script setup>
import { Rocket, Shield, CircleDollarSign, Zap } from 'lucide-vue-next';

</script>
