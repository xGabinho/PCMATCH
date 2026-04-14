import { ref } from 'vue'

const API = 'http://127.0.0.1:8000/api'

// Estado global compartido entre componentes
const isLoggedIn = ref(!!localStorage.getItem('token'))
const user = ref(JSON.parse(localStorage.getItem('usuario') ?? 'null'))

export function useAuth() {

  async function login(correo, password) {
    const res = await fetch(`${API}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ correo, password })
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.error ?? 'Error al iniciar sesión')
    }

    // Guardar en localStorage y estado global
    localStorage.setItem('token', data.token)
    localStorage.setItem('usuario', JSON.stringify(data.usuario))
    isLoggedIn.value = true
    user.value = data.usuario

    return data.usuario
  }

  async function register(nombre, apellido, correo, telefono, password) {
    const res = await fetch(`${API}/auth/register`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre, apellido, correo, telefono, password })
    })

    const data = await res.json()

    if (!res.ok) {
      throw new Error(data.error ?? 'Error al registrarse')
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
    isLoggedIn.value = false
    user.value = null
  }

  function getToken() {
    return localStorage.getItem('token') ?? ''
  }

  return { isLoggedIn, user, login, register, logout, getToken }
}