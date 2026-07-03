<template>
  <main class="min-h-screen theme-bg">

    <!-- Hero Header -->
    <section class="relative overflow-hidden border-b theme-border">
      <div class="absolute inset-0 opacity-[0.03]"
        style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
      </div>
      <div class="absolute right-0 top-0 w-[500px] h-[300px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>

      <div class="max-w-4xl mx-auto px-6 py-10 relative z-10 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-accent/20 bg-accent/5 text-accent text-xs font-medium mb-4">
          <span class="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></span>
          Asistente inteligente
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold theme-text tracking-tight mb-3">
          Arma tu PC ideal <span class="text-accent">sin complicaciones</span>
        </h1>
        <p class="theme-text-muted text-sm max-w-lg mx-auto">
          Responde 3 preguntas simples y te recomendamos la mejor combinación de componentes según tu presupuesto.
        </p>
      </div>
    </section>

    <div class="max-w-4xl mx-auto px-6 py-10">

      <!-- Progress Bar -->
      <div class="flex items-center justify-center gap-3 mb-10">
        <template v-for="(stepInfo, idx) in wizardSteps" :key="idx">
          <div class="flex items-center gap-2">
            <div
              class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold transition-all duration-300"
              :class="idx < currentStep
                ? 'bg-green-500/20 text-green-400 border border-green-500/30'
                : idx === currentStep
                  ? 'bg-accent text-white shadow-lg shadow-accent/30'
                  : 'theme-card border theme-border theme-text-muted'"
            >
              <span v-if="idx < currentStep">✓</span>
              <span v-else>{{ idx + 1 }}</span>
            </div>
            <span class="text-sm font-medium hidden sm:block transition-colors"
              :class="idx === currentStep ? 'text-accent' : 'theme-text-muted'"
            >
              {{ stepInfo.title }}
            </span>
          </div>
          <div v-if="idx < wizardSteps.length - 1"
            class="w-12 h-0.5 rounded-full transition-all duration-300"
            :class="idx < currentStep ? 'bg-green-500/40' : 'theme-border bg-current'"
          ></div>
        </template>
      </div>

      <!-- ═══════════════════ STEP 1: ¿Para qué? ═══════════════════ -->
      <div v-if="currentStep === 0" class="animate-fade-in">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold theme-text mb-2">¿Para qué necesitas tu PC?</h2>
          <p class="theme-text-muted text-sm">Selecciona el uso principal que le darás a tu computadora.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-w-2xl mx-auto">
          <button
            v-for="option in usoCases"
            :key="option.value"
            @click="selectUso(option.value)"
            class="relative group p-6 rounded-2xl border-2 transition-all duration-200 text-left overflow-hidden"
            :class="formData.uso === option.value
              ? 'border-accent bg-accent/5 shadow-lg shadow-accent/10'
              : 'theme-border theme-card hover:border-accent/40 card-hover'"
          >
            <div class="absolute top-0 right-0 w-24 h-24 rounded-full blur-2xl pointer-events-none transition-opacity"
              :class="formData.uso === option.value ? 'bg-accent/10 opacity-100' : 'opacity-0'"
            ></div>

            <div class="relative z-10">
              <div class="text-3xl mb-3">{{ option.icon }}</div>
              <h3 class="font-bold theme-text text-lg mb-1 group-hover:text-accent transition-colors">
                {{ option.label }}
              </h3>
              <p class="text-xs theme-text-muted leading-relaxed">{{ option.desc }}</p>
            </div>

            <div v-if="formData.uso === option.value"
              class="absolute top-3 right-3 w-6 h-6 rounded-full bg-accent flex items-center justify-center text-white text-xs font-bold">
              ✓
            </div>
          </button>
        </div>
      </div>

      <!-- ═══════════════════ STEP 2: ¿Qué desempeño? ═══════════════════ -->
      <div v-if="currentStep === 1" class="animate-fade-in">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold theme-text mb-2">¿Qué nivel de desempeño buscas?</h2>
          <p class="theme-text-muted text-sm">Esto nos ayuda a elegir la gama de componentes ideal para ti.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-3xl mx-auto">
          <button
            v-for="option in desempenoOptions"
            :key="option.value"
            @click="selectDesempeno(option.value)"
            class="relative group p-6 rounded-2xl border-2 transition-all duration-200 text-center overflow-hidden"
            :class="formData.desempeno === option.value
              ? 'border-accent bg-accent/5 shadow-lg shadow-accent/10'
              : 'theme-border theme-card hover:border-accent/40 card-hover'"
          >
            <div class="text-3xl mb-3">{{ option.icon }}</div>
            <h3 class="font-bold theme-text text-base mb-1">{{ option.label }}</h3>
            <p class="text-xs theme-text-muted leading-relaxed">{{ option.desc }}</p>
            <div class="mt-3 flex flex-wrap justify-center gap-1">
              <span v-for="tag in option.tags" :key="tag"
                class="text-[10px] px-2 py-0.5 rounded-full border theme-border theme-text-muted">
                {{ tag }}
              </span>
            </div>

            <div v-if="formData.desempeno === option.value"
              class="absolute top-3 right-3 w-6 h-6 rounded-full bg-accent flex items-center justify-center text-white text-xs font-bold">
              ✓
            </div>
          </button>
        </div>
      </div>

      <!-- ═══════════════════ STEP 3: Presupuesto ═══════════════════ -->
      <div v-if="currentStep === 2" class="animate-fade-in">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-bold theme-text mb-2">¿Cuál es tu presupuesto máximo?</h2>
          <p class="theme-text-muted text-sm">Ingresa la cantidad máxima que estás dispuesto a invertir.</p>
        </div>

        <div class="max-w-md mx-auto">
          <div class="card-dark rounded-2xl p-8 text-center">
            <div class="text-5xl mb-4">💰</div>
            <div class="relative mb-6">
              <span class="absolute left-4 top-1/2 -translate-y-1/2 text-accent font-bold text-xl">$</span>
              <input
                v-model.number="formData.presupuesto"
                type="number"
                min="100000"
                step="100000"
                placeholder="5000000"
                class="w-full text-center text-3xl font-bold font-mono theme-input py-4 pl-10 pr-4 rounded-xl"
                @keyup.enter="submitWizard"
              />
            </div>

            <!-- Quick budget buttons -->
            <div class="flex flex-wrap justify-center gap-2 mb-4">
              <button
                v-for="amount in quickBudgets"
                :key="amount"
                @click="formData.presupuesto = amount"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-150"
                :class="formData.presupuesto === amount
                  ? 'bg-accent text-white shadow-md'
                  : 'theme-card border theme-border theme-text-muted hover:border-accent/40'"
              >
                ${{ amount.toLocaleString() }}
              </button>
            </div>

            <p class="text-xs theme-text-muted">
              Mínimo recomendado: ${{ minRecommended.toLocaleString() }}
            </p>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ RESULTADO ═══════════════════ -->
      <div v-if="currentStep === 3" class="animate-fade-in">

        <!-- Loading -->
        <div v-if="loadingBuild" class="text-center py-20">
          <div class="inline-flex flex-col items-center gap-4">
            <svg class="animate-spin h-10 w-10 text-accent" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <div>
              <p class="theme-text font-semibold text-lg">Armando tu PC ideal...</p>
              <p class="theme-text-muted text-sm mt-1">Analizando componentes para {{ usoLabel }}</p>
            </div>
          </div>
        </div>

        <!-- Error -->
        <div v-else-if="buildError" class="max-w-lg mx-auto">
          <div class="card-dark rounded-2xl p-8 text-center">
            <div class="text-5xl mb-4">😕</div>
            <h3 class="text-xl font-bold theme-text mb-3">Presupuesto insuficiente</h3>
            <p class="theme-text-muted text-sm mb-4">{{ buildError.message }}</p>
            <p v-if="buildError.detalle" class="text-xs theme-text-muted mb-4">{{ buildError.detalle }}</p>

            <div v-if="buildError.presupuesto_minimo_estimado" class="card-dark rounded-xl p-4 mb-6 border border-accent/20">
              <p class="text-xs theme-text-muted mb-1">Presupuesto mínimo estimado</p>
              <p class="text-accent font-bold text-2xl font-mono">${{ Number(buildError.presupuesto_minimo_estimado).toLocaleString() }}</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
              <button @click="currentStep = 2" class="btn-primary text-sm">
                Ajustar presupuesto
              </button>
              <button @click="currentStep = 1" class="btn-secondary text-sm">
                Cambiar desempeño
              </button>
            </div>
          </div>
        </div>

        <!-- Success: Build result -->
        <div v-else-if="buildResult">
          <div class="text-center mb-8">
            <div class="text-4xl mb-3">🎉</div>
            <h2 class="text-2xl font-bold theme-text mb-2">¡Tu PC ideal está lista!</h2>
            <p class="theme-text-muted text-sm">
              Configuración optimizada para <span class="text-accent font-medium">{{ usoLabel }}</span> con
              desempeño <span class="text-accent font-medium">{{ desempenoLabel }}</span>
            </p>
          </div>

          <!-- Summary cards -->
          <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto mb-8">
            <div class="card-dark rounded-xl p-4 text-center">
              <p class="text-xs theme-text-muted mb-1">Total</p>
              <p class="text-accent font-bold text-xl font-mono">${{ Number(buildResult.total).toLocaleString() }}</p>
            </div>
            <div class="card-dark rounded-xl p-4 text-center">
              <p class="text-xs theme-text-muted mb-1">Presupuesto</p>
              <p class="theme-text font-bold text-xl font-mono">${{ Number(buildResult.presupuesto_max).toLocaleString() }}</p>
            </div>
            <div class="card-dark rounded-xl p-4 text-center">
              <p class="text-xs theme-text-muted mb-1">Ahorro</p>
              <p class="text-green-400 font-bold text-xl font-mono">${{ Number(buildResult.ahorro).toLocaleString() }}</p>
            </div>
          </div>

          <!-- Components list -->
          <div class="space-y-3 max-w-2xl mx-auto mb-8">
            <div
              v-for="comp in buildResult.build"
              :key="comp.id"
              class="card-dark rounded-xl flex items-center gap-4 p-4 card-hover group"
            >
              <!-- Image -->
              <div class="w-16 h-16 rounded-lg theme-bg flex items-center justify-center overflow-hidden flex-shrink-0">
                <template v-if="comp.imagen_url">
                  <img :src="comp.imagen_url" :alt="comp.nombre" class="w-full h-full object-contain" />
                </template>
                <template v-else>
                  <span class="text-2xl opacity-30">{{ categoryIcons[comp.categoria] ?? '🔧' }}</span>
                </template>
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="badge text-[10px] bg-accent/10 text-accent border border-accent/20">
                    {{ comp.categoria }}
                  </span>
                  <span class="text-[10px] px-2 py-0.5 rounded-full font-medium border"
                    :class="tierStyles[comp.gama]"
                  >
                    {{ comp.gama }}
                  </span>
                </div>
                <h4 class="text-sm font-semibold theme-text truncate group-hover:text-accent transition-colors">
                  {{ comp.nombre }}
                </h4>
                <p class="text-xs theme-text-muted truncate">{{ comp.especificacion }}</p>
              </div>

              <!-- Price -->
              <div class="text-right flex-shrink-0">
                <div v-if="comp.descuento_activo && comp.descuento_porcentaje > 0" class="flex flex-col items-end">
                  <span class="line-through text-[10px] theme-text-muted opacity-70">${{ Number(comp.precio).toLocaleString() }}</span>
                </div>
                <p class="text-accent font-bold font-mono">${{ Number(comp.precio_final || comp.precio).toLocaleString() }}</p>
                <p class="text-[10px] theme-text-muted">{{ comp.bodega }}</p>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex flex-col sm:flex-row gap-3 justify-center max-w-md mx-auto">
            <button @click="applyBuildToBuilder" class="btn-primary text-sm flex-1">
              ⚡ Usar esta configuración
            </button>
            <button @click="resetWizard" class="btn-secondary text-sm flex-1">
              🔄 Armar otra vez
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ NAVIGATION ═══════════════════ -->
      <div v-if="currentStep < 3" class="flex items-center justify-between mt-10 pt-6 border-t theme-border">
        <button
          @click="currentStep = Math.max(0, currentStep - 1)"
          class="btn-secondary text-sm"
          :class="{ 'opacity-40 pointer-events-none': currentStep === 0 }"
          :disabled="currentStep === 0"
        >
          ← Anterior
        </button>

        <div class="flex items-center gap-1.5">
          <div
            v-for="i in 3"
            :key="i"
            class="rounded-full transition-all duration-200"
            :class="i - 1 === currentStep
              ? 'w-5 h-1.5 bg-accent'
              : i - 1 < currentStep
                ? 'w-1.5 h-1.5 bg-green-500'
                : 'w-1.5 h-1.5 bg-gray-300 dark:bg-dark-border'"
          ></div>
        </div>

        <button
          @click="nextStep"
          class="btn-primary text-sm"
          :disabled="!canAdvance"
          :class="{ 'opacity-40 cursor-not-allowed': !canAdvance }"
        >
          {{ currentStep === 2 ? '🚀 Armar mi PC' : 'Siguiente →' }}
        </button>
      </div>

    </div>
  </main>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useBuilder } from '../composables/useBuilder'

const API = '/api'
const router = useRouter()
const { steps, selectItem, clearAll } = useBuilder()

// ── Wizard state ──
const currentStep = ref(0)
const loadingBuild = ref(false)
const buildResult = ref(null)
const buildError = ref(null)

const formData = ref({
  uso: '',
  desempeno: '',
  presupuesto: null,
})

// ── Step definitions ──
const wizardSteps = [
  { title: 'Uso' },
  { title: 'Desempeño' },
  { title: 'Presupuesto' },
]

// ── Use cases ──
const usoCases = [
  {
    value: 'gaming',
    icon: '🎮',
    label: 'Gaming',
    desc: 'Juegos AAA, streaming, VR. Prioridad en GPU y CPU de alto rendimiento.',
  },
  {
    value: 'estudio',
    icon: '📚',
    label: 'Estudio',
    desc: 'Programación, investigación, multitarea. Balance entre RAM y procesador.',
  },
  {
    value: 'oficina',
    icon: '💼',
    label: 'Oficina',
    desc: 'Office, navegación, correo. Prioridad en fiabilidad y almacenamiento.',
  },
  {
    value: 'diseño',
    icon: '🎨',
    label: 'Diseño',
    desc: 'Photoshop, Premiere, renderizado 3D. Prioridad en CPU y GPU profesional.',
  },
]

// ── Performance options ──
const desempenoOptions = [
  {
    value: 'alta',
    icon: '🚀',
    label: 'Alto',
    desc: 'La mejor experiencia posible sin compromisos.',
    tags: ['Gama alta', 'Máximo rendimiento'],
  },
  {
    value: 'media',
    icon: '⚡',
    label: 'Medio',
    desc: 'Buen rendimiento con excelente relación calidad-precio.',
    tags: ['Gama media', 'Mejor valor'],
  },
  {
    value: 'baja',
    icon: '💡',
    label: 'Básico',
    desc: 'Cubre lo esencial de forma eficiente y económica.',
    tags: ['Gama entrada', 'Económico'],
  },
]

// ── Quick budget presets ──
const quickBudgets = [2000000, 3000000, 5000000, 8000000, 12000000]

const minRecommended = computed(() => {
  if (formData.value.desempeno === 'alta') return 6000000
  if (formData.value.desempeno === 'media') return 3500000
  return 1800000
})

// ── Labels ──
const usoLabel = computed(() => usoCases.find(u => u.value === formData.value.uso)?.label ?? '')
const desempenoLabel = computed(() => desempenoOptions.find(d => d.value === formData.value.desempeno)?.label ?? '')

// ── Validation ──
const canAdvance = computed(() => {
  if (currentStep.value === 0) return !!formData.value.uso
  if (currentStep.value === 1) return !!formData.value.desempeno
  if (currentStep.value === 2) return formData.value.presupuesto && formData.value.presupuesto >= 100000
  return false
})

// ── Helpers ──
const categoryIcons = {
  CPU: '⚙️', GPU: '🎮', RAM: '💾', Storage: '💿',
  Motherboard: '🔌', PSU: '⚡', Cooler: '❄️', Case: '🖥️'
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

// ── Actions ──
function selectUso(val) {
  formData.value.uso = val
}

function selectDesempeno(val) {
  formData.value.desempeno = val
}

function nextStep() {
  if (!canAdvance.value) return

  if (currentStep.value === 2) {
    submitWizard()
  } else {
    currentStep.value++
  }
}

async function submitWizard() {
  if (!canAdvance.value) return

  currentStep.value = 3
  loadingBuild.value = true
  buildResult.value = null
  buildError.value = null

  try {
    const res = await fetch(`${API}/recomendaciones/pc-ideal`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        uso: formData.value.uso,
        desempeno: formData.value.desempeno,
        presupuesto_max: formData.value.presupuesto,
      }),
    })
    const data = await res.json()

    if (res.ok && data.success) {
      buildResult.value = data
    } else {
      buildError.value = data
    }
  } catch (e) {
    buildError.value = { message: 'Error de conexión. Intenta de nuevo.' }
    console.error('Error building PC:', e)
  } finally {
    loadingBuild.value = false
  }
}

function applyBuildToBuilder() {
  if (!buildResult.value) return

  clearAll()

  for (const comp of buildResult.value.build) {
    selectItem(comp.step_id, {
      id: comp.id,
      nombre: comp.nombre,
      categoria: comp.categoria,
      especificacion: comp.especificacion,
      gama: comp.gama,
      enfoque_uso: comp.enfoque_uso,
      precio: comp.precio,
      stock: comp.stock,
      imagen_url: comp.imagen_url,
      bodega: comp.bodega,
    })
  }

  router.push('/armar')
}

function resetWizard() {
  currentStep.value = 0
  formData.value = { uso: '', desempeno: '', presupuesto: null }
  buildResult.value = null
  buildError.value = null
}
</script>
