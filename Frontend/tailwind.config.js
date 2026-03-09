/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        'dark-bg': '#1A1A1A',
        'light-bg': '#F5F5F5',
        'text-primary': '#E0E0E0',
        'text-light': '#2B2B2B',
        'text-muted': '#8A8A8A',
        'accent': '#3B82F6',
        'dark-card': '#242424',
        'dark-border': '#2E2E2E',
      },
      fontFamily: {
        sans: ['DM Sans', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
    },
  },
  plugins: [],
}