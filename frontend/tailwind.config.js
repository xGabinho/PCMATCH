/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        'dark-bg':     '#0D1117',
        'dark-card':   '#161B22',
        'dark-border': '#30363D',
        'text-primary':'#E6EDF3',
        'text-muted':  '#8B949E',
        'accent':      '#1A6FE0',
      }
    }
  },
  plugins: [],
}
