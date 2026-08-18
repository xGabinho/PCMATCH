import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

import HomeView from '../views/HomeView.vue'

const routes = [
  // Públicas
  { path: '/',          component: HomeView },
  { path: '/login',     component: () => import('../views/LoginView.vue') },
  { path: '/recuperar-password',   component: () => import('../views/ForgotPasswordView.vue') },
  { path: '/restablecer-password', component: () => import('../views/ResetPasswordView.vue') },
  { path: '/ejemplo-cotizacion',   component: () => import('../views/DemoQuoteView.vue') },
  { path: '/sobre-nosotros',       component: () => import('../views/AboutView.vue') },
  { path: '/contacto',             component: () => import('../views/ContactView.vue') },

  // Cliente y armador (accesible por todos los roles autenticados)
  { path: '/inicio',    component: () => import('../views/ClientHomeView.vue'), meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/perfil',    component: () => import('../views/ProfileView.vue'),    meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/armar',     component: () => import('../views/BuilderView.vue'),    meta: { requiresAuth: true, roles: ['cliente', 'admin', 'superadmin', 'bodega', 'proveedor'] } },
  { path: '/cotizacion',component: () => import('../views/QuoteView.vue'),      meta: { requiresAuth: true, roles: ['cliente', 'admin', 'superadmin', 'bodega', 'proveedor'] } },

  // Perfil de usuario (todos los roles)
  { path: '/mi-perfil', component: () => import('../views/UserProfileView.vue'), meta: { requiresAuth: true, roles: ['cliente', 'admin', 'superadmin', 'bodega', 'proveedor'] } },

  // Admin
  { path: '/admin',  component: () => import('../views/AdminView.vue'),  meta: { requiresAuth: true, roles: ['admin']  } },

  // Super Admin
  { path: '/superadmin', component: () => import('../views/SuperAdminView.vue'), meta: { requiresAuth: true, roles: ['superadmin'] } },

  // Bodega / Proveedor
  { path: '/bodega', component: () => import('../views/BodegaView.vue'), meta: { requiresAuth: true, roles: ['bodega'] } },
  // Proveedor
  { path: '/proveedor', component: () => import('../views/ProveedorView.vue'), meta: { requiresAuth: true, roles: ['proveedor'] } },

  // Páginas de Error
  { path: '/error', name: 'Error', component: () => import('../views/ErrorView.vue') },
  { path: '/:pathMatch(.*)*', name: 'NotFound', component: () => import('../views/ErrorView.vue'), props: { code: 404, title: 'Página No Encontrada', description: 'La página que buscas no existe o ha sido movida.' } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const { isLoggedIn, user, checkAuth } = useAuth()

  // Ruta requiere auth
  if (to.meta.requiresAuth) {
    if (!isLoggedIn.value) {
      return { path: '/login' }
    }

    const isValid = await checkAuth()
    if (!isValid) {
      return { path: '/login' }
    }

    // Verificar rol
    if (to.meta.roles && !to.meta.roles.includes(user.value?.rol)) {
      if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
      if (user.value?.rol === 'admin')  return { path: '/admin'  }
      if (user.value?.rol === 'bodega') return { path: '/bodega' }
      if (user.value?.rol === 'proveedor') return { path: '/proveedor' }

      return { path: '/inicio' }
    }
  }

  // Si ya está logueado y va a la landing ('/') o al login ('/login'), redirigir a su home según rol
  if ((to.path === '/' || to.path === '/login') && isLoggedIn.value) {
    const isValid = await checkAuth()
    if (!isValid) return

    if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
    if (user.value?.rol === 'admin')  return { path: '/admin'  }
    if (user.value?.rol === 'bodega') return { path: '/bodega' }
    if (user.value?.rol === 'proveedor') return { path: '/proveedor' }

    return { path: '/inicio' }
  }
})

export default router