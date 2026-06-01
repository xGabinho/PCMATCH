import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router/index.js'

// Global input sanitizer for text fields
document.addEventListener('input', (e) => {
  if (e.target && e.target.tagName === 'INPUT' && e.target.type === 'text') {
    // Skip if the field is allowed to have special characters (e.g. product descriptions)
    if (e.target.classList.contains('allow-special')) return;
    
    // Remove spaces, commas, periods and other non-alphanumeric characters
    // as per the requirement: "espacios ni comas ni puntos. Nada de eso"
    // We allow only letters and numbers
    const original = e.target.value;
    const sanitized = original.replace(/[^a-zA-Z0-9]/g, '');
    if (original !== sanitized) {
      e.target.value = sanitized;
      e.target.dispatchEvent(new Event('input')); // Update Vue v-model
    }
  }
});

createApp(App).use(router).mount('#app')