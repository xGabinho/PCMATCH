import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

import HomeView       from '../views/HomeView.vue'
import LoginView      from '../views/LoginView.vue'
import ClientHomeView from '../views/ClientHomeView.vue'
import BuilderView    from '../views/BuilderView.vue'
import QuoteView      from '../views/QuoteView.vue'
import AdminView      from '../views/AdminView.vue'
import BodegaView     from '../views/BodegaView.vue'
import ProfileView    from '../views/ProfileView.vue'
import ProveedorView  from '../views/ProveedorView.vue'
import SuperAdminView from '../views/SuperAdminView.vue'

const routes = [
  // Públicas
  { path: '/',       component: HomeView  },
  { path: '/login',  component: LoginView },

  // Cliente
  { path: '/inicio',    component: ClientHomeView, meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/perfil',    component: ProfileView,    meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/armar',     component: BuilderView,    meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/cotizacion',component: QuoteView,      meta: { requiresAuth: true, roles: ['cliente'] } },

  // Admin
  { path: '/admin',  component: AdminView,  meta: { requiresAuth: true, roles: ['admin']  } },

  // Bodega
  { path: '/bodega', component: BodegaView, meta: { requiresAuth: true, roles: ['bodega'] } },

  // Proveedor
  { path: '/proveedor', component: ProveedorView, meta: { requiresAuth: true, roles: ['proveedor'] } },

  // SuperAdmin
  { path: '/superadmin', component: SuperAdminView, meta: { requiresAuth: true, roles: ['superadmin'] } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const { isLoggedIn, user } = useAuth()

  // Ruta requiere auth
  if (to.meta.requiresAuth) {
    if (!isLoggedIn.value) {
      return { path: '/login' }
    }
    // Verificar rol
    if (to.meta.roles && !to.meta.roles.includes(user.value?.rol)) {
      // Redirigir a su home según rol
      if (user.value?.rol === 'admin')  return { path: '/admin'  }
      if (user.value?.rol === 'bodega') return { path: '/bodega' }
      if (user.value?.rol === 'proveedor') return { path: '/proveedor' }
      if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
      return { path: '/inicio' }
    }
  }

  // Si ya está logueado y va al login, redirigir a su home
  if (to.path === '/login' && isLoggedIn.value) {
    if (user.value?.rol === 'admin')  return { path: '/admin'  }
    if (user.value?.rol === 'bodega') return { path: '/bodega' }
    if (user.value?.rol === 'proveedor') return { path: '/proveedor' }
    if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
    return { path: '/inicio' }
  }
})

export default router