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

const app = createApp(App);

app.config.errorHandler = (err, instance, info) => {
  const errorLog = {
    timestamp: new Date().toISOString(),
    level: 'error',
    message: err?.message || String(err),
    component: instance?.$options?.name || instance?.$options?.__name || 'UnknownComponent',
    info: info,
    stack_trace: err?.stack || null,
  };
  console.error('[LOG_ERROR]: ' + JSON.stringify(errorLog));
};

window.addEventListener('unhandledrejection', (event) => {
  const errorLog = {
    timestamp: new Date().toISOString(),
    level: 'error',
    type: 'unhandledrejection',
    reason: event.reason?.message || String(event.reason),
    stack_trace: event.reason?.stack || null,
  };
  console.error('[LOG_UNHANDLED_REJECTION]: ' + JSON.stringify(errorLog));
});

app.use(router).mount('#app');