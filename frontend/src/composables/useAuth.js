import { ref } from 'vue'

const isLoggedIn = ref(!!localStorage.getItem('token'))
const user       = ref(JSON.parse(localStorage.getItem('usuario') || 'null'))

export function useAuth() {
    function login(token, usuario) {
        localStorage.setItem('token', token)
        localStorage.setItem('usuario', JSON.stringify(usuario))
        isLoggedIn.value = true
        user.value       = usuario
    }

    function logout() {
        localStorage.removeItem('token')
        localStorage.removeItem('usuario')
        isLoggedIn.value = false
        user.value       = null
    }

    function getToken() {
        return localStorage.getItem('token')
    }

    return { isLoggedIn, user, login, logout, getToken }
}
