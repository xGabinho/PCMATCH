<template>
  <main class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-6 py-12 relative overflow-auto">

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
          <span class="font-bold text-text-primary text-2xl tracking-tight">PCMATCH</span>
        </div>
        <h1 class="text-2xl font-bold text-text-primary">Bienvenido de vuelta</h1>
        <p class="text-text-muted mt-2 text-sm">Inicia sesión para comenzar a armar tu PC</p>
      </div>

      <!-- Card -->
      <div class="card-dark rounded-2xl p-8">

        <!-- Tabs -->
        <div class="flex rounded-lg bg-dark-bg p-1 mb-8">
          <button
            @click="tab = 'login'"
            class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-150"
            :class="tab === 'login' ? 'bg-dark-card text-text-primary shadow' : 'text-text-muted hover:text-text-primary'"
          >
            Iniciar sesión
          </button>
          <button
            @click="tab = 'register'"
            class="flex-1 py-2 text-sm font-medium rounded-md transition-all duration-150"
            :class="tab === 'register' ? 'bg-dark-card text-text-primary shadow' : 'text-text-muted hover:text-text-primary'"
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
            <label class="block text-sm font-medium text-text-primary mb-2">Correo electrónico</label>
            <input
              type="email"
              placeholder="tucorreo@email.com"
              v-model="loginData.correo"
              class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
            />
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-sm font-medium text-text-primary">Contraseña</label>
              <button type="button" class="text-xs text-accent hover:underline">¿Olvidaste tu contraseña?</button>
            </div>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="••••••••"
                v-model="loginData.password"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors pr-12"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors text-xs"
              >
                {{ showPassword ? '🙈' : '👁️' }}
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
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
              <input
                type="text"
                placeholder="Juan"
                v-model="registerData.nombre"
                @input="registerData.nombre = registerData.nombre.replace(/[^a-záéíóúñüA-ZÁÉÍÓÚÑÜ]/g, '')"
                maxlength="40"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Apellido</label>
              <input
                type="text"
                placeholder="Pérez"
                v-model="registerData.apellido"
                @input="registerData.apellido = registerData.apellido.replace(/[^a-záéíóúñüA-ZÁÉÍÓÚÑÜ]/g, '')"
                maxlength="40"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Correo electrónico</label>
            <input
              type="email"
              placeholder="tucorreo@gmail.com"
              v-model="registerData.correo"
              class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg bg-dark-bg border border-dark-border text-text-muted text-sm select-none flex-shrink-0">
                🇨🇴 +57
              </div>
              <input
                type="tel"
                placeholder="300 123 4567"
                v-model="registerData.telefonoLocal"
                @input="handleTelefonoInput"
                maxlength="13"
                class="flex-1 bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
              />
            </div>
            <p class="text-xs text-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Contraseña</label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                placeholder="Mínimo 8 caracteres"
                v-model="registerData.password"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors pr-12"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors text-xs"
              >
                {{ showPassword ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Confirmar contraseña</label>
            <div class="relative">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Repite tu contraseña"
                v-model="registerData.confirmPassword"
                class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors pr-12"
                :class="registerData.confirmPassword && registerData.password !== registerData.confirmPassword
                  ? 'border-red-500/50'
                  : registerData.confirmPassword && registerData.password === registerData.confirmPassword
                    ? 'border-green-500/50'
                    : ''"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-text-primary transition-colors text-xs"
              >
                {{ showConfirmPassword ? '🙈' : '👁️' }}
              </button>
            </div>
            <p v-if="registerData.confirmPassword && registerData.password !== registerData.confirmPassword"
              class="text-xs text-red-400 mt-1">
              Las contraseñas no coinciden
            </p>
            <p v-if="registerData.confirmPassword && registerData.password === registerData.confirmPassword"
              class="text-xs text-green-400 mt-1">
              ✓ Las contraseñas coinciden
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

      <p class="text-center text-xs text-text-muted mt-6 mb-4">
        Al continuar aceptas nuestros
        <button @click="showTerms = true" class="text-accent hover:underline">Términos de uso</button> y
        <button @click="showPrivacy = true" class="text-accent hover:underline">Política de privacidad</button>
      </p>
    </div>
  </main>

  <!-- ===== MODAL TÉRMINOS DE USO ===== -->
  <div v-if="showTerms" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showTerms = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative card-dark rounded-2xl w-full max-w-xl shadow-2xl flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between p-6 border-b border-dark-border flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-base">📄</div>
            <div>
              <h2 class="text-base font-bold text-text-primary">Términos de uso</h2>
              <p class="text-xs text-text-muted mt-0.5">PCMATCH — Última actualización: Feb 2026</p>
            </div>
          </div>
          <button @click="showTerms = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg flex-shrink-0">×</button>
        </div>
        <div class="overflow-y-auto flex-1 p-6 space-y-6 text-sm">
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">01</span> Objeto del servicio</h3>
            <p class="text-text-muted leading-relaxed">La plataforma <span class="text-text-primary">PCMATCH</span> es un sistema informativo y comparativo que permite a los usuarios cotizar y configurar computadores de escritorio utilizando precios y disponibilidad de bodegas internas del centro comercial.</p>
            <p class="text-text-muted leading-relaxed mt-2">El sistema no realiza ventas en línea, no procesa pagos ni garantiza la reserva de productos.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">02</span> Uso del sistema</h3>
            <p class="text-text-muted leading-relaxed mb-2">El usuario se compromete a:</p>
            <ul class="space-y-1.5">
              <li v-for="item in ['Proporcionar información veraz y actualizada', 'Utilizar la plataforma únicamente con fines informativos', 'No hacer uso indebido del sistema o intentar alterar su funcionamiento']" :key="item" class="flex items-start gap-2 text-text-muted">
                <span class="text-accent mt-0.5 flex-shrink-0">›</span>{{ item }}
              </li>
            </ul>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">03</span> Carácter informativo de las cotizaciones</h3>
            <p class="text-text-muted leading-relaxed">Las cotizaciones generadas son referenciales. La cotización no constituye una oferta de venta ni un compromiso comercial.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">04</span> Registro de usuarios</h3>
            <p class="text-text-muted leading-relaxed">El usuario es responsable de la confidencialidad de sus credenciales.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">05</span> Responsabilidad</h3>
            <p class="text-text-muted leading-relaxed">El centro comercial no se hace responsable por cambios en precios o stock, ni por decisiones de compra tomadas por el usuario.</p>
          </section>
        </div>
        <div class="p-6 border-t border-dark-border flex-shrink-0">
          <button @click="showTerms = false" class="btn-primary w-full text-sm">Entendido</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== MODAL POLÍTICA DE PRIVACIDAD ===== -->
  <div v-if="showPrivacy" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showPrivacy = false"></div>
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative card-dark rounded-2xl w-full max-w-xl shadow-2xl flex flex-col max-h-[85vh]">
        <div class="flex items-center justify-between p-6 border-b border-dark-border flex-shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-base">🔐</div>
            <div>
              <h2 class="text-base font-bold text-text-primary">Política de privacidad</h2>
              <p class="text-xs text-text-muted mt-0.5">PCMATCH — Última actualización: Feb 2026</p>
            </div>
          </div>
          <button @click="showPrivacy = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg flex-shrink-0">×</button>
        </div>
        <div class="overflow-y-auto flex-1 p-6 space-y-6 text-sm">
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">01</span> Información recopilada</h3>
            <p class="text-text-muted leading-relaxed mb-2">La plataforma podrá recopilar: nombre, correo electrónico e historial de cotizaciones. No se recopilan datos bancarios ni información sensible.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">02</span> Uso de la información</h3>
            <p class="text-text-muted leading-relaxed">Se usa para gestionar el acceso, generar cotizaciones y mejorar la experiencia del usuario.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">03</span> Compartición de información</h3>
            <p class="text-text-muted leading-relaxed">Los datos no serán vendidos ni compartidos con terceros externos al centro comercial.</p>
          </section>
          <div class="border-t border-dark-border"></div>
          <section>
            <h3 class="font-semibold text-text-primary mb-2 flex items-center gap-2"><span class="text-accent font-mono text-xs">04</span> Derechos del usuario</h3>
            <p class="text-text-muted leading-relaxed">El usuario puede consultar, modificar o eliminar sus datos en cualquier momento.</p>
          </section>
        </div>
        <div class="p-6 border-t border-dark-border flex-shrink-0">
          <button @click="showPrivacy = false" class="btn-primary w-full text-sm">Entendido</button>
        </div>
      </div>
    </div>
  </div>

</template>

<script setup>
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
  if (rol === 'admin')  return router.push('/admin')
  if (rol === 'bodega') return router.push('/bodega')
  if (rol === 'proveedor') return router.push('/proveedor')
  if (rol === 'superadmin') return router.push('/superadmin')
  router.push('/inicio')
}
</script>