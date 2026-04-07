import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/LoginView.vue'
import AdminView from '@/views/AdminView.vue'
import ProveedorView from '@/views/ProveedorView.vue'

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
    },
    {
        path: '/admin',
        name: 'admin',
        component: AdminView,
        meta: { requiresAuth: true, roles: ['admin'] }
    },
    {
        path: '/proveedor',
        name: 'proveedor',
        component: ProveedorView,
        meta: { requiresAuth: true, roles: ['proveedor', 'bodega'] }
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes,
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const usuario = JSON.parse(localStorage.getItem('usuario') || 'null')

    if (to.meta.requiresAuth) {
        if (!token || !usuario) return next('/login')
        if (to.meta.roles && !to.meta.roles.includes(usuario.rol)) return next('/login')
    }

    if (to.path === '/login' && token && usuario) {
        if (usuario.rol === 'admin') return next('/admin')
        if (usuario.rol === 'bodega' || usuario.rol === 'proveedor') return next('/proveedor')
    }

    next()
})

export default router
