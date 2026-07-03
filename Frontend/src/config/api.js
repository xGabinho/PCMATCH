/**
 * Configuración centralizada de la API.
 * En desarrollo usa '/api' (proxy de Vite).
 * En producción usa la URL definida en VITE_API_URL.
 */
export const API = import.meta.env.VITE_API_URL || '/api'
