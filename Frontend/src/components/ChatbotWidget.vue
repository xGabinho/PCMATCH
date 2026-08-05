<template>
  <div class="fixed bottom-5 right-5 z-50 flex flex-col items-end font-sans">
    
    <!-- ═══════════════════ VENTANA DEL CHATBOT (POPUP) ═══════════════════ -->
    <transition
      enter-active-class="transition duration-300 ease-out transform"
      enter-from-class="opacity-0 translate-y-6 scale-95"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-200 ease-in transform"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 translate-y-6 scale-95"
    >
      <div 
        v-if="isOpen" 
        class="w-[360px] sm:w-[420px] h-[580px] max-h-[82vh] bg-white dark:bg-zinc-900 border theme-border rounded-2xl shadow-2xl flex flex-col overflow-hidden mb-3 text-slate-900 dark:text-slate-100"
      >
        <!-- Header del Popup -->
        <div class="p-3.5 border-b theme-border bg-slate-100 dark:bg-zinc-800 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 rounded-full bg-accent/20 border border-accent/40 flex items-center justify-center text-accent relative">
              <Bot class="w-5 h-5" />
              <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 rounded-full border-2 border-white dark:border-zinc-800"></span>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <h3 class="font-bold theme-text text-sm leading-none">Asistente PCMATCH</h3>
                <span class="px-1.5 py-0.5 rounded text-[10px] bg-accent/15 text-accent font-mono font-semibold">IA</span>
              </div>
              <p class="text-[11px] theme-text-muted mt-0.5">Catálogo y Recomendaciones</p>
            </div>
          </div>

          <div class="flex items-center gap-1">
            <button 
              @click="resetChat" 
              title="Reiniciar chat"
              class="p-1.5 text-xs theme-text-muted hover:theme-text hover:bg-accent/10 rounded-lg transition-colors"
            >
              <RefreshCw class="w-4 h-4" />
            </button>
            <button 
              @click="toggleChat" 
              title="Minimizar chat"
              class="p-1.5 text-xs theme-text-muted hover:theme-text hover:bg-accent/10 rounded-lg transition-colors"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Cuerpo / Mensajes del Chat -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3.5 bg-slate-50 dark:bg-zinc-950" ref="chatContainer">
          
          <div 
            v-for="(msg, idx) in chatMessages" 
            :key="idx" 
            class="flex flex-col"
            :class="msg.role === 'user' ? 'items-end' : 'items-start'"
          >
            <div class="flex items-start gap-2 max-w-[88%]" :class="msg.role === 'user' ? 'flex-row-reverse' : 'flex-row'">
              <!-- Icono de Bot -->
              <div 
                v-if="msg.role === 'model'" 
                class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center text-accent flex-shrink-0 mt-0.5 text-xs border border-accent/30"
              >
                <Bot class="w-3.5 h-3.5" />
              </div>

              <!-- Burbuja de mensaje -->
              <div 
                class="px-3.5 py-2.5 rounded-2xl text-xs sm:text-sm leading-relaxed shadow-sm whitespace-pre-wrap break-words font-medium" 
                :class="msg.role === 'user' 
                  ? 'bg-accent text-white rounded-tr-xs' 
                  : 'bg-white dark:bg-zinc-800 border theme-border text-slate-800 dark:text-slate-100 rounded-tl-xs'"
                v-html="formatMarkdown(msg.content)"
              ></div>
            </div>

            <!-- Mapeo de Varias Opciones de PC si el mensaje contiene resultado de armado -->
            <div v-if="msg.buildResult" class="w-full mt-2 space-y-3">
              
              <!-- Si hay error de presupuesto -->
              <div v-if="msg.buildResult.error" class="p-3 bg-red-500/10 border border-red-500/20 rounded-xl text-xs">
                <p class="text-red-400 font-semibold mb-1">Presupuesto insuficiente</p>
                <p class="theme-text-muted">{{ msg.buildResult.error.message }}</p>
                <div v-if="msg.buildResult.error.presupuesto_minimo_estimado" class="mt-2 font-mono text-accent font-bold">
                  Mínimo estimado: ${{ Number(msg.buildResult.error.presupuesto_minimo_estimado).toLocaleString() }}
                </div>
              </div>

              <!-- Varias Opciones de Recomendación -->
              <div v-else-if="msg.buildResult.opciones && msg.buildResult.opciones.length > 0" class="space-y-3">
                <div class="text-xs font-semibold theme-text flex items-center gap-1.5">
                  <Sparkles class="w-3.5 h-3.5 text-accent" />
                  <span>Opciones recomendadas para ti:</span>
                </div>

                <!-- Tabs de Opciones -->
                <div class="flex gap-1 bg-slate-200 dark:bg-zinc-800 p-1 rounded-xl border theme-border overflow-x-auto">
                  <button
                    v-for="(opc, opcIdx) in msg.buildResult.opciones"
                    :key="opc.id"
                    @click="msg.activeOptionIdx = opcIdx"
                    class="flex-1 min-w-[110px] px-2.5 py-1.5 rounded-lg text-[11px] font-semibold transition-all text-center truncate"
                    :class="(msg.activeOptionIdx ?? 0) === opcIdx
                      ? 'bg-accent text-white shadow-sm'
                      : 'theme-text-muted hover:theme-text hover:bg-accent/10'"
                  >
                    {{ opc.nombre }}
                  </button>
                </div>

                <!-- Tarjeta de Opción Seleccionada -->
                <div 
                  v-if="msg.buildResult.opciones[msg.activeOptionIdx ?? 0]" 
                  class="bg-white dark:bg-zinc-900 border theme-border rounded-xl p-3 space-y-3 shadow-md"
                >
                  <div class="flex justify-between items-start border-b theme-border pb-2">
                    <div>
                      <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-accent/20 text-accent border border-accent/30">
                        {{ msg.buildResult.opciones[msg.activeOptionIdx ?? 0].tag }}
                      </span>
                      <p class="text-xs theme-text-muted mt-1 font-medium">
                        {{ msg.buildResult.opciones[msg.activeOptionIdx ?? 0].descripcion }}
                      </p>
                    </div>
                    <div class="text-right flex-shrink-0 ml-2">
                      <p class="text-xs theme-text-muted font-medium">Total</p>
                      <p class="text-accent font-bold font-mono text-sm">
                        ${{ Number(msg.buildResult.opciones[msg.activeOptionIdx ?? 0].total).toLocaleString() }}
                      </p>
                    </div>
                  </div>

                  <!-- Lista resumida de piezas de la opción -->
                  <div class="space-y-1.5 max-h-44 overflow-y-auto pr-1">
                    <div 
                      v-for="comp in msg.buildResult.opciones[msg.activeOptionIdx ?? 0].build" 
                      :key="comp.id"
                      class="flex items-center justify-between text-xs p-1.5 rounded-lg bg-slate-100 dark:bg-zinc-800 border theme-border"
                    >
                      <div class="min-w-0 flex-1 pr-2">
                        <span class="font-bold text-[10px] text-accent block uppercase">{{ comp.categoria }}</span>
                        <p class="theme-text text-[11px] truncate font-medium">{{ comp.nombre }}</p>
                      </div>
                      <span class="font-mono font-semibold text-[11px] text-right flex-shrink-0 theme-text">
                        ${{ Number(comp.precio_final || comp.precio).toLocaleString() }}
                      </span>
                    </div>
                  </div>

                  <!-- Botón para usar esta build -->
                  <button 
                    @click="applyBuildToBuilder(msg)"
                    class="w-full btn-primary text-xs py-2 rounded-lg flex items-center justify-center gap-1.5 font-medium shadow-sm transition-transform active:scale-98"
                  >
                    <Zap class="w-3.5 h-3.5" />
                    <span>Cargar en Ensamblador</span>
                  </button>
                </div>
              </div>
            </div>

          </div>

          <!-- Indicador de "Escribiendo..." -->
          <div v-if="isTyping" class="flex items-start gap-2">
            <div class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center text-accent flex-shrink-0 text-xs border border-accent/30">
              <Bot class="w-3.5 h-3.5" />
            </div>
            <div class="px-3.5 py-2.5 rounded-2xl bg-white dark:bg-zinc-800 border theme-border rounded-tl-xs flex items-center gap-1.5 h-9">
              <span class="w-1.5 h-1.5 bg-accent/80 rounded-full animate-bounce"></span>
              <span class="w-1.5 h-1.5 bg-accent/80 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
              <span class="w-1.5 h-1.5 bg-accent/80 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
          </div>

        </div>

        <!-- Sugerencias / Chips Rápidos de Introducción -->
        <div v-if="showQuickChips" class="px-3 py-2 bg-slate-100 dark:bg-zinc-900 border-t theme-border flex gap-1.5 overflow-x-auto text-[11px]">
          <button 
            v-for="chip in quickChips" 
            :key="chip.label"
            @click="sendQuickChip(chip.text)"
            class="px-2.5 py-1 rounded-full bg-accent/15 border border-accent/30 text-accent hover:bg-accent hover:text-white transition-colors whitespace-nowrap flex items-center gap-1 font-medium"
          >
            <span>{{ chip.icon }}</span>
            <span>{{ chip.label }}</span>
          </button>
        </div>

        <!-- Area de Entrada de Texto -->
        <div class="p-3 border-t theme-border bg-slate-100 dark:bg-zinc-900">
          <form @submit.prevent="sendMessage" class="flex items-end gap-2">
            <textarea
              v-model="userInput"
              placeholder="Ej: Necesito PC para gaming por 4.000.000..."
              class="flex-1 theme-input rounded-xl px-3 py-2 bg-white dark:bg-zinc-800 text-slate-900 dark:text-slate-100 text-xs sm:text-sm focus:ring-1 focus:ring-accent focus:border-accent outline-none border theme-border resize-none max-h-24 min-h-[38px]"
              :disabled="isTyping"
              rows="1"
              @keydown.enter.exact.prevent="sendMessage"
              @input="autoResize"
              ref="chatInput"
            ></textarea>
            <button 
              type="submit" 
              class="btn-primary w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 transition-transform active:scale-95 disabled:opacity-50"
              :disabled="isTyping || !userInput.trim()"
            >
              <Send class="w-4 h-4" />
            </button>
          </form>
        </div>

      </div>
    </transition>

    <!-- ═══════════════════ BOTÓN BURBUJA FLOTANTE ═══════════════════ -->
    <button
      @click="toggleChat"
      class="w-14 h-14 rounded-full bg-accent text-white shadow-xl flex items-center justify-center transition-all duration-300 hover:scale-105 active:scale-95 group relative border-2 border-white/20"
      :class="isOpen ? 'rotate-90' : 'animate-bounce-subtle'"
      title="Asistente de PCMATCH"
    >
      <MessageSquare v-if="!isOpen" class="w-6 h-6 group-hover:rotate-6 transition-transform" />
      <X v-else class="w-6 h-6" />

      <!-- Indicador verde de activo -->
      <span class="absolute top-1 right-1 w-3.5 h-3.5 bg-green-500 rounded-full border-2 border-background"></span>
    </button>

  </div>
</template>

<script setup>
import { ref, onMounted, watch, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Bot, Send, RefreshCw, X, MessageSquare, Sparkles, Zap } from 'lucide-vue-next'
import { useBuilder } from '../composables/useBuilder'

const API = '/api'
const STORAGE_KEY = 'pcmatch_chat_session'

const router = useRouter()
const { selectItem, clearAll } = useBuilder()

const isOpen = ref(false)
const userInput = ref('')
const isTyping = ref(false)
const chatContainer = ref(null)
const chatInput = ref(null)
const showQuickChips = ref(true)

const defaultInitialMessage = "¡Hola! 👋 Soy tu asistente inteligente de PCMATCH. ¿Deseas armar una PC o resolver dudas de nuestro catálogo?\n\nSi quieres una recomendación completa, dime tu enfoque (Gaming, Edición, Oficina), nivel (Entrada, Media, Alta) y presupuesto."

const chatMessages = ref([
  { role: 'model', content: defaultInitialMessage }
])

const quickChips = [
  { icon: '🎮', label: 'Gaming', text: 'Quiero armar una PC para Gaming' },
  { icon: '💼', label: 'Oficina', text: 'Necesito una PC económica de Oficina' },
  { icon: '🎨', label: 'Edición', text: 'Quiero una PC potente para Edición y Diseño' },
  { icon: '🔍', label: 'Ver Stock', text: '¿Qué procesadores y tarjetas de video tienen disponibles?' },
]

// ── Persistencia en localStorage con TTL de 5 minutos ──
const SESSION_TTL_MS = 5 * 60 * 1000 // 5 minutos

function loadSession() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY)
    if (saved) {
      const data = JSON.parse(saved)
      const elapsed = Date.now() - (data.timestamp || 0)

      if (elapsed > SESSION_TTL_MS) {
        // Sesión expirada, limpiar
        localStorage.removeItem(STORAGE_KEY)
        return
      }

      if (Array.isArray(data.messages) && data.messages.length > 0) {
        chatMessages.value = data.messages
        // Ocultar chips si ya hubo interacción del usuario
        if (data.messages.some(m => m.role === 'user')) {
          showQuickChips.value = false
        }
      }
    }
  } catch (e) {
    console.error('Error al cargar la sesión del chatbot:', e)
  }
}

function saveSession() {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify({
      messages: chatMessages.value,
      timestamp: Date.now()
    }))
  } catch (e) {
    console.error('Error al guardar la sesión del chatbot:', e)
  }
}

watch(chatMessages, () => {
  saveSession()
}, { deep: true })

onMounted(() => {
  loadSession()
})

const toggleChat = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    scrollToBottom()
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

const autoResize = () => {
  if (chatInput.value) {
    chatInput.value.style.height = 'auto'
    chatInput.value.style.height = `${chatInput.value.scrollHeight}px`
  }
}

const sendQuickChip = (text) => {
  userInput.value = text
  sendMessage()
}

const sendMessage = async () => {
  const text = userInput.value.trim()
  if (!text || isTyping.value) return

  chatMessages.value.push({ role: 'user', content: text })
  userInput.value = ''
  showQuickChips.value = false

  if (chatInput.value) {
    chatInput.value.style.height = 'auto'
  }

  isTyping.value = true
  scrollToBottom()

  try {
    // Formatear payload enviando sólo las claves role y content al servidor
    const apiPayload = chatMessages.value.map(m => ({
      role: m.role,
      content: m.content
    }))

    const res = await fetch(`${API}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ messages: apiPayload })
    })

    const data = await res.json()

    if (res.ok) {
      if (data.type === 'build') {
        chatMessages.value.push({
          role: 'model',
          content: data.message || '¡Aquí tienes las mejores opciones configuradas para ti!',
          buildResult: data.buildResult,
          activeOptionIdx: 0
        })
      } else {
        chatMessages.value.push({ role: 'model', content: data.message })
      }
    } else {
      chatMessages.value.push({
        role: 'model',
        content: data.message || 'Hubo un inconveniente al procesar tu mensaje. Por favor intenta nuevamente.'
      })
    }
  } catch (error) {
    console.error('Chat error:', error)
    chatMessages.value.push({
      role: 'model',
      content: 'Ups, no pude conectarme al servidor. Verifica tu conexión de red.'
    })
  } finally {
    isTyping.value = false
    scrollToBottom()
  }
}

const formatMarkdown = (text) => {
  if (!text) return ''
  let safe = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')

  // Reemplazar **texto** por <strong>texto</strong>
  safe = safe.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
  // Reemplazar *texto* por <em>texto</em>
  safe = safe.replace(/\*(.*?)\*/g, '<em>$1</em>')

  return safe
}

const resetChat = () => {
  chatMessages.value = [{ role: 'model', content: defaultInitialMessage }]
  showQuickChips.value = true
  localStorage.removeItem(STORAGE_KEY)
}

const getStepIdFromCategory = (comp) => {
  if (comp.step_id && ['cpu', 'gpu', 'ram', 'storage', 'motherboard', 'psu', 'cooler', 'case'].includes(comp.step_id)) {
    return comp.step_id
  }
  const cat = (comp.categoria || '').toLowerCase().trim()
  if (cat.includes('cpu') || cat.includes('procesador')) return 'cpu'
  if (cat.includes('gpu') || cat.includes('gráfica') || cat.includes('grafica') || cat.includes('video')) return 'gpu'
  if (cat.includes('ram') || cat.includes('memoria')) return 'ram'
  if (cat.includes('storage') || cat.includes('almacenamiento') || cat.includes('ssd') || cat.includes('disco')) return 'storage'
  if (cat.includes('motherboard') || cat.includes('placa') || cat.includes('mobo')) return 'motherboard'
  if (cat.includes('psu') || cat.includes('fuente')) return 'psu'
  if (cat.includes('cooler') || cat.includes('refrigeracion') || cat.includes('disipador')) return 'cooler'
  if (cat.includes('case') || cat.includes('gabinete') || cat.includes('chasis')) return 'case'
  return null
}

const applyBuildToBuilder = async (msg) => {
  try {
    if (!msg || !msg.buildResult) return

    let buildItems = []
    if (msg.buildResult.opciones && msg.buildResult.opciones.length > 0) {
      const activeIdx = msg.activeOptionIdx ?? 0
      const selectedOption = msg.buildResult.opciones[activeIdx] || msg.buildResult.opciones[0]
      buildItems = selectedOption ? (selectedOption.build || []) : []
    } else if (Array.isArray(msg.buildResult.build)) {
      buildItems = msg.buildResult.build
    }

    if (!buildItems || buildItems.length === 0) {
      console.warn('No hay componentes válidos para añadir al ensamblador.')
      return
    }

    clearAll()

    for (const comp of buildItems) {
      const stepId = getStepIdFromCategory(comp)
      if (stepId) {
        selectItem(stepId, {
          id: comp.id,
          nombre: comp.nombre,
          categoria: comp.categoria,
          especificacion: comp.especificacion,
          gama: comp.gama,
          enfoque_uso: comp.enfoque_uso,
          precio: comp.precio,
          precio_final: comp.precio_final || comp.precio,
          stock: comp.stock,
          imagen_url: comp.imagen_url,
          bodega: comp.bodega,
        })
      } else {
        console.warn(`Categoría no mapeada para el componente: ${comp.nombre} (${comp.categoria})`)
      }
    }

    isOpen.value = false
    await router.push('/armar').catch(() => {
      window.location.href = '/armar'
    })
  } catch (error) {
    console.error('Error al aplicar build al ensamblador:', error)
  }
}
</script>

<style scoped>
@keyframes bounceSubtle {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-4px); }
}
.animate-bounce-subtle {
  animation: bounceSubtle 3s infinite ease-in-out;
}
</style>
