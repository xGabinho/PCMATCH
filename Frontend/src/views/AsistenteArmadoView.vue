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
          Asistente inteligente con IA
        </div>
        <h1 class="text-3xl sm:text-4xl font-bold theme-text tracking-tight mb-3">
          Arma tu PC ideal <span class="text-accent">conversando</span>
        </h1>
        <p class="theme-text-muted text-sm max-w-lg mx-auto">
          Dile al asistente para qué necesitas la PC, tu presupuesto y nivel de desempeño esperado.
        </p>
      </div>
    </section>

    <div class="max-w-4xl mx-auto px-6 py-10">

      <!-- ═══════════════════ CHAT INTERFACE ═══════════════════ -->
      <div v-if="!buildResult && !buildError" class="animate-fade-in max-w-3xl mx-auto flex flex-col h-[600px] border theme-border rounded-xl bg-card overflow-hidden shadow-lg shadow-accent/5">
        
        <!-- Header del chat -->
        <div class="p-4 border-b theme-border bg-card/80 flex items-center justify-between">
          <div class="flex items-center gap-3">
             <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent">
               <Bot class="w-6 h-6" />
             </div>
             <div>
               <h3 class="font-bold theme-text text-sm">PCMATCH Bot</h3>
               <p class="text-xs text-green-400 font-medium flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500"></span> En línea</p>
             </div>
          </div>
          <button @click="resetChat" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1">
             <RefreshCw class="w-3 h-3" /> Reiniciar
          </button>
        </div>

        <!-- Historial de mensajes -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="chatContainer">
          <div v-for="(msg, idx) in chatMessages" :key="idx" class="flex" :class="msg.role === 'user' ? 'justify-end' : 'justify-start'">
            
            <div v-if="msg.role === 'model'" class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 mr-2 mt-1">
               <Bot class="w-4 h-4" />
            </div>

            <div class="max-w-[80%] p-3.5 rounded-2xl shadow-sm text-sm" 
                 :class="msg.role === 'user' ? 'bg-accent text-white rounded-tr-sm' : 'bg-background border theme-border theme-text rounded-tl-sm'">
               {{ msg.content }}
            </div>
          </div>
          
          <div v-if="isTyping" class="flex justify-start">
             <div class="w-8 h-8 rounded-full bg-accent/10 flex items-center justify-center text-accent flex-shrink-0 mr-2 mt-1">
               <Bot class="w-4 h-4" />
             </div>
             <div class="max-w-[80%] p-3.5 rounded-2xl bg-background border theme-border theme-text rounded-tl-sm flex gap-1 items-center h-[46px]">
                 <span class="w-2 h-2 bg-accent/50 rounded-full animate-bounce"></span>
                 <span class="w-2 h-2 bg-accent/50 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                 <span class="w-2 h-2 bg-accent/50 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
             </div>
          </div>
        </div>

        <!-- Input de mensaje -->
        <div class="p-4 border-t theme-border bg-card/80">
          <form @submit.prevent="sendMessage" class="flex gap-2 items-end">
            <textarea 
              v-model="userInput" 
              placeholder="Escribe tu mensaje aquí... (Enter para enviar, Shift+Enter para nueva línea)" 
              class="flex-1 theme-input rounded-xl px-4 py-3 bg-background text-sm focus:ring-accent focus:border-accent outline-none border theme-border resize-none max-h-32 min-h-[48px]" 
              :disabled="isTyping"
              rows="1"
              @keydown.enter.exact.prevent="sendMessage"
              @input="autoResize"
              ref="chatInput"
            ></textarea>
            <button type="submit" class="btn-primary px-4 py-3 h-[48px] rounded-xl flex items-center justify-center transition-transform active:scale-95" :disabled="isTyping || !userInput.trim()">
                <Send class="w-5 h-5" />
            </button>
          </form>
        </div>
      </div>

      <!-- ═══════════════════ ERROR AL ARMAR (Presupuesto Insuficiente) ═══════════════════ -->
      <div v-else-if="buildError" class="max-w-lg mx-auto animate-fade-in">
        <div class="card-dark rounded-2xl p-8 text-center">
          <Frown class="w-14 h-14 mx-auto mb-4 text-orange-500" />
          <h3 class="text-xl font-bold theme-text mb-3">Presupuesto insuficiente</h3>
          <p class="theme-text-muted text-sm mb-4">{{ buildError.message }}</p>
          <p v-if="buildError.detalle" class="text-xs theme-text-muted mb-4">{{ buildError.detalle }}</p>

          <div v-if="buildError.presupuesto_minimo_estimado" class="card-dark rounded-xl p-4 mb-6 border border-accent/20">
            <p class="text-xs theme-text-muted mb-1">Presupuesto mínimo estimado</p>
            <p class="text-accent font-bold text-2xl font-mono">${{ Number(buildError.presupuesto_minimo_estimado).toLocaleString() }}</p>
          </div>

          <div class="flex justify-center">
            <button @click="resetChat" class="btn-primary text-sm">
              <MessageSquare class="w-4 h-4 mr-2 inline-block" /> Volver al chat
            </button>
          </div>
        </div>
      </div>

      <!-- ═══════════════════ RESULTADO ═══════════════════ -->
      <div v-else-if="buildResult" class="animate-fade-in">
        <div class="text-center mb-8">
          <PartyPopper class="w-12 h-12 mx-auto mb-3 text-accent" />
          <h2 class="text-2xl font-bold theme-text mb-2">¡Tu PC ideal está lista!</h2>
          <p class="theme-text-muted text-sm">
            Configuración optimizada para <span class="text-accent font-medium">{{ buildResult.uso }}</span> con
            desempeño <span class="text-accent font-medium">{{ buildResult.desempeno }}</span>
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
            <div class="w-16 h-16 rounded-lg theme-bg flex items-center justify-center overflow-hidden flex-shrink-0 border theme-border">
              <template v-if="comp.imagen_url">
                <img :src="comp.imagen_url" :alt="comp.nombre" class="w-full h-full object-contain" />
              </template>
              <template v-else>
                <component :is="categoryIcons[comp.categoria] ?? Wrench" class="w-8 h-8 opacity-30" />
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
            <Zap class="w-4 h-4 mr-2 inline-block" /> Usar esta configuración
          </button>
          <button @click="resetChat" class="btn-secondary text-sm flex-1">
            <MessageSquare class="w-4 h-4 mr-2 inline-block" /> Volver al chat
          </button>
        </div>
      </div>

    </div>
  </main>
</template>

<script setup>
import { 
  PartyPopper, Wrench, Zap, RefreshCw, Bot, Send, MessageSquare, Frown, 
  Settings, Gamepad2, Save, Disc, Plug, Snowflake, Monitor 
} from 'lucide-vue-next';

import { ref, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useBuilder } from '../composables/useBuilder'

const API = '/api'
const router = useRouter()
const { selectItem, clearAll } = useBuilder()

// ── Chat State ──
const defaultInitialMessage = "¡Hola! Soy tu asistente inteligente de PCMATCH con IA. ¿En qué te puedo ayudar hoy? Si quieres que te recomiende una PC ideal, cuéntame para qué la vas a usar, qué nivel de desempeño buscas y cuál es tu presupuesto máximo."
const chatMessages = ref([
  { role: 'model', content: defaultInitialMessage }
])
const userInput = ref('')
const isTyping = ref(false)
const chatContainer = ref(null)
const chatInput = ref(null)

// ── Build State ──
const buildResult = ref(null)
const buildError = ref(null)

// ── Helpers ──
const categoryIcons = {
  CPU: Settings, GPU: Gamepad2, RAM: Save, Storage: Disc,
  Motherboard: Plug, PSU: Zap, Cooler: Snowflake, Case: Monitor
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
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
    chatInput.value.style.height = (chatInput.value.scrollHeight) + 'px'
  }
}

// ── Chat Actions ──
async function sendMessage() {
  const text = userInput.value.trim()
  if (!text) return

  chatMessages.value.push({ role: 'user', content: text })
  userInput.value = ''
  
  // Reset textarea height
  if (chatInput.value) {
    chatInput.value.style.height = 'auto'
  }
  isTyping.value = true
  scrollToBottom()

  try {
    const res = await fetch(`${API}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        messages: chatMessages.value
      })
    })
    
    const data = await res.json()
    
    if (res.ok) {
      if (data.type === 'build') {
         buildResult.value = data.buildResult
         // No pusheamos el msg al chat porque la vista cambia, 
         // pero podríamos pushearlo si el usuario vuelve
         chatMessages.value.push({ role: 'model', content: data.message })
      } else {
         chatMessages.value.push({ role: 'model', content: data.message })
      }
    } else {
       if (data.error) {
          const detailMsg = data.details ? ` | Detalles: ${JSON.stringify(data.details)}` : ''
          chatMessages.value.push({ role: 'model', content: `Error: ${data.error}${detailMsg}` })
       } else {
         chatMessages.value.push({ role: 'model', content: data.message || "Hubo un problema procesando tu solicitud." })
       }
    }
  } catch (error) {
    chatMessages.value.push({ role: 'model', content: 'Ups, no pude conectarme al servidor. Revisa tu conexión.' })
    console.error(error)
  } finally {
    isTyping.value = false
    scrollToBottom()
  }
}

function resetChat() {
  buildResult.value = null
  buildError.value = null
  chatMessages.value = [
    { role: 'model', content: defaultInitialMessage }
  ]
}

// ── Build Action ──
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
</script>
