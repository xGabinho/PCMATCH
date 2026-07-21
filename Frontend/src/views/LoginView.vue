<template>
  <main class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 sm:px-6 py-12 relative overflow-auto">

    <!-- Background glow -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-accent/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute inset-0 opacity-[0.02]"
      style="background-image: linear-gradient(#3B82F6 1px, transparent 1px), linear-gradient(90deg, #3B82F6 1px, transparent 1px); background-size: 60px 60px;">
    </div>

    <div class="w-full max-w-md relative z-10 py-4">

      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center gap-2.5 mb-4">
          <div class="w-10 h-10 rounded-xl bg-accent flex items-center justify-center text-white font-bold">PC</div>
          <span class="font-bold theme-text text-2xl tracking-tight">PCMATCH</span>
        </div>
        <h1 class="text-2xl font-bold theme-text">Bienvenido de vuelta</h1>
        <p class="theme-text-muted mt-2 text-sm">Inicia sesión para comenzar a armar tu PC</p>
      </div>

      <!-- Card -->
      <div class="card-dark rounded-2xl p-6 sm:p-8">

        <!-- Tabs -->
        <div class="flex rounded-lg theme-bg p-1 mb-8 border theme-border">
          <button
            @click="tab = 'login'"
            class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-150"
            :class="tab === 'login' ? 'theme-card theme-text shadow' : 'theme-text-muted hover:theme-text'"
          >
            Iniciar sesión
          </button>
          <button
            @click="tab = 'register'"
            class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-150"
            :class="tab === 'register' ? 'theme-card theme-text shadow' : 'theme-text-muted hover:theme-text'"
          >
            Registrarse
          </button>
        </div>

        <!-- Error global -->
        <div v-if="errorMsg" class="mb-5 px-4 py-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <!-- Login Form -->
        <form v-if="tab === 'login'" @submit.prevent="handleLogin" class="space-y-5">
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input
              type="email"
              placeholder="tucorreo@email.com"
              v-model="loginData.correo"
              class="theme-input"
            />
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium theme-text">Contraseña</label>
              <router-link to="/recuperar-password" class="text-xs text-accent hover:underline">¿Olvidaste tu contraseña?</router-link>
            </div>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                v-model="loginData.password"
                class="theme-input pr-12"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-xs"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full text-sm text-center block mt-6"
            :class="{ 'opacity-60 cursor-not-allowed': loading }"
          >
            {{ loading ? 'Iniciando sesión...' : 'Iniciar sesión →' }}
          </button>
        </form>

        <!-- Register Form -->
        <form v-if="tab === 'register'" @submit.prevent="handleRegister" class="space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Nombre</label>
              <input
                type="text"
                placeholder="Juan"
                v-model="registerData.nombre"
                @input="registerData.nombre = registerData.nombre.replace(/[^a-záéíóúñüA-ZÁÉÍÓÚÑÜ]/g, '')"
                maxlength="40"
                class="theme-input"
              />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Apellido</label>
              <input
                type="text"
                placeholder="Pérez"
                v-model="registerData.apellido"
                @input="registerData.apellido = registerData.apellido.replace(/[^a-záéíóúñüA-ZÁÉÍÓÚÑÜ]/g, '')"
                maxlength="40"
                class="theme-input"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input
              type="email"
              placeholder="tucorreo@gmail.com"
              v-model="registerData.correo"
              class="theme-input"
            />
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
              </div>
              <input
                type="tel"
                placeholder="300 123 4567"
                v-model="registerData.telefonoLocal"
                @input="handleTelefonoInput"
                maxlength="13"
                class="flex-1 theme-input"
              />
            </div>
            <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Contraseña</label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mínimo 8 caracteres"
                v-model="registerData.password"
                class="theme-input pr-12"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-xs"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Confirmar contraseña</label>
            <div class="relative">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                v-model="registerData.confirmPassword"
                class="theme-input pr-12"
                :class="registerData.confirmPassword && registerData.password !== registerData.confirmPassword
                  ? 'border-red-500/50'
                  : registerData.confirmPassword && registerData.password === registerData.confirmPassword
                    ? 'border-green-500/50'
                    : ''"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 theme-text-muted hover:theme-text transition-colors text-xs"
              >
                <EyeOff v-if="showConfirmPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="registerData.confirmPassword && registerData.password !== registerData.confirmPassword"
              class="text-xs text-red-400 mt-1">
              Las contraseñas no coinciden
            </p>
            <p v-if="registerData.confirmPassword && registerData.password === registerData.confirmPassword"
              class="text-xs text-green-400 mt-1">
              <Check class="w-3 h-3 inline-block mr-1" /> Las contraseñas coinciden
            </p>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="btn-primary w-full text-sm text-center block mt-6"
            :class="{ 'opacity-60 cursor-not-allowed': loading }"
          >
            {{ loading ? 'Creando cuenta...' : 'Crear cuenta →' }}
          </button>
        </form>

      </div>

      <p class="text-center text-xs theme-text-muted mt-6 mb-4">
        Al continuar aceptas nuestros
        <button @click="showTerms = true" class="text-accent hover:underline">Términos de uso</button> y
        <button @click="showPrivacy = true" class="text-accent hover:underline">Política de privacidad</button>
      </p>
    </div>
  </main>

  <!-- ===== MODAL TÉRMINOS DE USO ===== -->
  <div v-if="showTerms" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showTerms = false"></div>
    <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="relative card-dark w-full sm:max-w-xl shadow-2xl flex flex-col max-h-[90vh] sm:rounded-2xl rounded-t-2xl sm:rounded-b-2xl animate-slide-up">
        <div class="flex items-center justify-between p-5 sm:p-6 border-b theme-border flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent">
              <FileText class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-base font-bold theme-text">Términos de uso</h2>
              <p class="text-xs theme-text-muted mt-0.5">PCMATCH — Última actualización: Feb 2026</p>
            </div>
          </div>
          <button @click="showTerms = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-black/5 dark:hover:bg-white/5 flex-shrink-0">×</button>
        </div>
        <div class="overflow-y-auto flex-1 p-5 sm:p-6 space-y-6 text-sm">
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">01</span> Objeto del servicio</h3>
            <p class="theme-text-muted leading-relaxed">La plataforma <span class="theme-text">PCMATCH</span> es un sistema informativo y comparativo que permite a los usuarios cotizar y configurar computadores de escritorio utilizando precios y disponibilidad de bodegas internas del centro comercial.</p>
            <p class="theme-text-muted leading-relaxed mt-2">El sistema no realiza ventas en línea, no procesa pagos ni garantiza la reserva de productos.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">02</span> Uso del sistema</h3>
            <p class="theme-text-muted leading-relaxed mb-2">El usuario se compromete a:</p>
            <ul class="space-y-1.5">
              <li v-for="item in ['Proporcionar información veraz y actualizada', 'Utilizar la plataforma únicamente con fines informativos', 'No hacer uso indebido del sistema o intentar alterar su funcionamiento']" :key="item" class="flex items-start gap-2 theme-text-muted">
                <span class="text-accent mt-0.5 flex-shrink-0">›</span>{{ item }}
              </li>
            </ul>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">03</span> Carácter informativo de las cotizaciones</h3>
            <p class="theme-text-muted leading-relaxed">Las cotizaciones generadas son referenciales. La cotización no constituye una oferta de venta ni un compromiso comercial.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">04</span> Registro de usuarios</h3>
            <p class="theme-text-muted leading-relaxed">El usuario es responsable de la confidencialidad de sus credenciales.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">05</span> Responsabilidad</h3>
            <p class="theme-text-muted leading-relaxed">El centro comercial no se hace responsable por cambios en precios o stock, ni por decisiones de compra tomadas por el usuario.</p>
          </section>
        </div>
        <div class="p-5 sm:p-6 border-t theme-border flex-shrink-0 bg-gray-100 dark:bg-dark-card rounded-b-2xl">
          <button @click="showTerms = false" class="btn-primary w-full text-sm">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MODAL POLÍTICA DE PRIVACIDAD ===== -->
  <div v-if="showPrivacy" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPrivacy = false"></div>
    <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="relative card-dark w-full sm:max-w-xl shadow-2xl flex flex-col max-h-[90vh] sm:rounded-2xl rounded-t-2xl sm:rounded-b-2xl animate-slide-up">
        <div class="flex items-center justify-between p-5 sm:p-6 border-b theme-border flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent">
              <Lock class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-base font-bold theme-text">Política de privacidad</h2>
              <p class="text-xs theme-text-muted mt-0.5">PCMATCH — Última actualización: Feb 2026</p>
            </div>
          </div>
          <button @click="showPrivacy = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-black/5 dark:hover:bg-white/5 flex-shrink-0">×</button>
        </div>
        <div class="overflow-y-auto flex-1 p-5 sm:p-6 space-y-6 text-sm">
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">01</span> Información recopilada</h3>
            <p class="theme-text-muted leading-relaxed mb-2">La plataforma podrá recopilar: nombre, correo electrónico e historial de cotizaciones. No se recopilan datos bancarios ni información sensible.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">02</span> Uso de la información</h3>
            <p class="theme-text-muted leading-relaxed">Se usa para gestionar el acceso, generar cotizaciones y mejorar la experiencia del usuario.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">03</span> Compartición de información</h3>
            <p class="theme-text-muted leading-relaxed">Los datos no serán vendidos ni compartidos con terceros externos al centro comercial.</p>
          </section>
          <div class="border-t theme-border"></div>
          <section>
            <h3 class="font-semibold theme-text mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">04</span> Derechos del usuario</h3>
            <p class="theme-text-muted leading-relaxed">El usuario puede consultar, modificar o eliminar sus datos en cualquier momento.</p>
          </section>
        </div>
        <div class="p-5 sm:p-6 border-t theme-border flex-shrink-0 bg-gray-100 dark:bg-dark-card rounded-b-2xl">
          <button @click="showPrivacy = false" class="btn-primary w-full text-sm">Entendido</button>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
import { MapPin, Eye, EyeOff, Check, FileText, Lock } from 'lucide-vue-next';


import { ref } from 'vue'
import { useRouter } from 'vue-router'

import { useAuth } from '../composables/useAuth'

const tab = ref('login')
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const showTerms = ref(false)
const showPrivacy = ref(false)
const loading = ref(false)
const errorMsg = ref('')

const router = useRouter()
const { login, register } = useAuth()

const loginData = ref({ correo: '', password: '' })
const registerData = ref({
  nombre: '',
  apellido: '',
  correo: '',
  telefonoLocal: '',
  password: '',
  confirmPassword: ''
})

function handleTelefonoInput() {
  let val = registerData.value.telefonoLocal.replace(/[^\d]/g, '')
  if (val.length > 10) val = val.slice(0, 10)
  if (val.length > 6) {
    val = val.slice(0, 3) + ' ' + val.slice(3, 6) + ' ' + val.slice(6)
  } else if (val.length > 3) {
    val = val.slice(0, 3) + ' ' + val.slice(3)
  }
  registerData.value.telefonoLocal = val
}

/**
 * Gestiona el inicio de sesión del usuario.
 * Llama al composable 'useAuth' para autenticar y luego redirige según el rol.
 */
/**
 * Gestiona el inicio de sesión validando credenciales.
 */
async function handleLogin() {
  errorMsg.value = ''
  if (!loginData.value.correo || !loginData.value.password) {
    return errorMsg.value = 'Completa todos los campos'
  }
  loading.value = true
  try {
    const usuario = await login(loginData.value.correo, loginData.value.password)
    redirectByRole(usuario.rol)
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

/**
 * Procesa el formulario de registro de nuevos usuarios.
 * Valida contraseñas, formato del número telefónico y llama a la API.
 */
/**
 * Procesa el registro de un nuevo usuario en la plataforma.
 */
async function handleRegister() {
  errorMsg.value = ''
  const { nombre, apellido, correo, telefonoLocal, password, confirmPassword } = registerData.value

  if (!nombre || !apellido || !correo || !telefonoLocal || !password || !confirmPassword) {
    return errorMsg.value = 'Completa todos los campos'
  }
  if (nombre.trim().length < 2) {
    return errorMsg.value = 'El nombre debe tener al menos 2 caracteres'
  }
  if (apellido.trim().length < 2) {
    return errorMsg.value = 'El apellido debe tener al menos 2 caracteres'
  }
  if (password.length < 8) {
    return errorMsg.value = 'La contraseña debe tener al menos 8 caracteres'
  }
  if (password !== confirmPassword) {
    return errorMsg.value = 'Las contraseñas no coinciden'
  }

  const dígitos = telefonoLocal.replace(/\s/g, '')
  if (dígitos.length !== 10 || !dígitos.startsWith('3')) {
    return errorMsg.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
  }

  const telefonoCompleto = '+57' + dígitos

  loading.value = true
  try {
    const usuario = await register(nombre, apellido, correo, telefonoCompleto, password)
    redirectByRole(usuario.rol)
  } catch (e) {
    errorMsg.value = e.message
  } finally {
    loading.value = false
  }
}

function redirectByRole(rol) {
  if (rol === 'superadmin') return router.push('/superadmin')
  if (rol === 'admin')  return router.push('/admin')
  if (rol === 'bodega') return router.push('/bodega')
  if (rol === 'proveedor') return router.push('/proveedor')

  router.push('/inicio')
}
</script>
