import { ref } from 'vue'
import { API } from '@/config/api'

// Estado global compartido entre componentes
const isLoggedIn = ref(!!localStorage.getItem('token'))
const user = ref(JSON.parse(localStorage.getItem('usuario') ?? 'null'))

export function useAuth() {

  /**
   * Parsea la respuesta JSON de forma segura.
   * Si el body está vacío o no es JSON válido, lanza un error descriptivo.
   */
  async function safeJson(res) {
    const text = await res.text()
    if (!text) {
      throw new Error('El servidor no respondió. Verifica que el backend esté en ejecución.')
    }
    try {
      return JSON.parse(text)
    } catch {
      throw new Error('Respuesta inesperada del servidor. Intenta de nuevo más tarde.')
    }
  }

  async function login(correo, password) {
    let res
    try {
      res = await fetch(`${API}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ correo, password })
      })
    } catch {
      throw new Error('No se pudo conectar al servidor. Verifica tu conexión o que el backend esté activo.')
    }

    const data = await safeJson(res)

    if (!res.ok) {
      throw new Error(data.message ?? data.error ?? 'Error al iniciar sesión')
    }

    // Guardar en localStorage y estado global
    localStorage.setItem('token', data.token)
    localStorage.setItem('usuario', JSON.stringify(data.usuario))
    isLoggedIn.value = true
    user.value = data.usuario

    return data.usuario
  }

  async function register(nombre, apellido, correo, telefono, password) {
    let res
    try {
      res = await fetch(`${API}/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ nombre, apellido, correo, telefono, password })
      })
    } catch {
      throw new Error('No se pudo conectar al servidor. Verifica tu conexión o que el backend esté activo.')
    }

    const data = await safeJson(res)

    if (!res.ok) {
      throw new Error(data.message ?? data.error ?? 'Error al registrarse')
    }

    localStorage.setItem('token', data.token)
    localStorage.setItem('usuario', JSON.stringify(data.usuario))
    isLoggedIn.value = true
    user.value = data.usuario

    return data.usuario
  }

  function logout() {
    localStorage.removeItem('token')
    localStorage.removeItem('usuario')
    localStorage.clear()
    isLoggedIn.value = false
    user.value = null
    window.location.href = '/login'
  }

  function getToken() {
    return localStorage.getItem('token') ?? ''
  }

  function updateUser(data) {
    const updated = { ...user.value, ...data }
    localStorage.setItem('usuario', JSON.stringify(updated))
    user.value = updated
  }

  function hasPermission(code) {
    if (!user.value) return false
    if (user.value.rol === 'superadmin') return true
    if (user.value.rol === 'admin') {
      if (!user.value.perfil_id) return true
      return Array.isArray(user.value.permisos) && user.value.permisos.includes(code)
    }
    return false
  }

  async function checkAuth() {
    const token = getToken()
    if (!token) return false
    try {
      const res = await fetch(`${API}/auth/profile`, {
        headers: { Authorization: `Bearer ${token}` }
      })
      if (res.status === 401) {
        logout()
        return false
      }
      if (res.ok) {
        const data = await res.json()
        if (data.perfil) {
          updateUser(data.perfil)
        }
        return true
      }
    } catch (e) {
      console.error('Error al verificar sesión:', e)
    }
    return true
  }

  return { isLoggedIn, user, login, register, logout, getToken, updateUser, hasPermission, checkAuth }
}