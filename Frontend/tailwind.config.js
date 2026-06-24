/** @type {import('tailwindcss').Config} */
export default {
  darkMode: 'class',
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Dark theme (default)
        'dark-bg': '#1A1A1A',
        'dark-card': '#242424',
        'dark-border': '#2E2E2E',
        'text-primary': '#E0E0E0',
        'text-muted': '#8A8A8A',

        // Light theme
        'light-bg': '#F8F9FC',
        'light-card': '#FFFFFF',
        'light-border': '#E2E5EB',
        'text-light': '#1A1D26',
        'text-light-muted': '#6B7280',

        // Shared
        'accent': '#3B82F6',
      },
      fontFamily: {
        sans: ['DM Sans', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
    },
  },
  plugins: [],
}