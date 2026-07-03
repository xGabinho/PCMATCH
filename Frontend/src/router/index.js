import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from '../composables/useAuth'

import HomeView       from '../views/HomeView.vue'
import LoginView      from '../views/LoginView.vue'
import ForgotPasswordView  from '../views/ForgotPasswordView.vue'
import ResetPasswordView   from '../views/ResetPasswordView.vue'
import DemoQuoteView  from '../views/DemoQuoteView.vue'
import AboutView      from '../views/AboutView.vue'
import ContactView    from '../views/ContactView.vue'
import ClientHomeView from '../views/ClientHomeView.vue'
import BuilderView    from '../views/BuilderView.vue'
import QuoteView      from '../views/QuoteView.vue'
import AdminView      from '../views/AdminView.vue'
import SuperAdminView from '../views/SuperAdminView.vue'
import BodegaView     from '../views/BodegaView.vue'
import ProveedorView  from '../views/ProveedorView.vue'
import ProfileView    from '../views/ProfileView.vue'
import UserProfileView from '../views/UserProfileView.vue'
import AsistenteArmadoView from '../views/AsistenteArmadoView.vue'

const routes = [
  // Públicas
  { path: '/',          component: HomeView  },
  { path: '/login',     component: LoginView },
  { path: '/asistente', component: AsistenteArmadoView },
  { path: '/recuperar-password',   component: ForgotPasswordView },
  { path: '/restablecer-password', component: ResetPasswordView },
  { path: '/ejemplo-cotizacion',   component: DemoQuoteView },
  { path: '/sobre-nosotros',       component: AboutView },
  { path: '/contacto',             component: ContactView },

  // Cliente
  { path: '/inicio',    component: ClientHomeView, meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/perfil',    component: ProfileView,    meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/armar',     component: BuilderView,    meta: { requiresAuth: true, roles: ['cliente'] } },
  { path: '/cotizacion',component: QuoteView,      meta: { requiresAuth: true, roles: ['cliente'] } },

  // Perfil de usuario (todos los roles)
  { path: '/mi-perfil', component: UserProfileView, meta: { requiresAuth: true, roles: ['cliente', 'admin', 'superadmin', 'bodega', 'proveedor'] } },

  // Admin
  { path: '/admin',  component: AdminView,  meta: { requiresAuth: true, roles: ['admin']  } },

  // Super Admin
  { path: '/superadmin', component: SuperAdminView, meta: { requiresAuth: true, roles: ['superadmin'] } },

  // Bodega / Proveedor
  { path: '/bodega', component: BodegaView, meta: { requiresAuth: true, roles: ['bodega'] } },
  // Proveedor
  { path: '/proveedor', component: ProveedorView, meta: { requiresAuth: true, roles: ['proveedor'] } },
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
      if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
      if (user.value?.rol === 'admin')  return { path: '/admin'  }
      if (user.value?.rol === 'bodega') return { path: '/bodega' }
      if (user.value?.rol === 'proveedor') return { path: '/proveedor' }

      return { path: '/inicio' }
    }
  }

  // Si ya está logueado y va al login, redirigir a su home
  if (to.path === '/login' && isLoggedIn.value) {
    if (user.value?.rol === 'superadmin') return { path: '/superadmin' }
    if (user.value?.rol === 'admin')  return { path: '/admin'  }
    if (user.value?.rol === 'bodega') return { path: '/bodega' }
    if (user.value?.rol === 'proveedor') return { path: '/proveedor' }

    return { path: '/inicio' }
  }
})

export default router