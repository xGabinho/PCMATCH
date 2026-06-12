const fs = require('fs');
const path = require('path');

const files = [
  'AdminView.vue',
  'SuperAdminView.vue',
  'BodegaView.vue',
  'ProveedorView.vue'
];

const basePath = 'c:\\xampp\\htdocs\\PCMATCH\\frontend\\src\\views';

const replacements = [
  { from: /bg-dark-bg\/90/g, to: 'bg-light-bg/90 dark:bg-dark-bg/90' },
  { from: /bg-dark-bg\/50/g, to: 'bg-gray-100 dark:bg-dark-bg/50' },
  { from: /bg-dark-bg/g, to: 'theme-bg' },
  { from: /border-dark-border/g, to: 'theme-border' },
  { from: /bg-dark-card/g, to: 'theme-card' },
  { from: /text-text-primary/g, to: 'theme-text' },
  { from: /text-text-muted/g, to: 'theme-text-muted' },
  // specific to ensure hover states also update properly if we used hover:bg-dark-card
  { from: /hover:theme-card/g, to: 'hover:theme-card' } // No-op, just to notice it if needed
];

files.forEach(file => {
  const filePath = path.join(basePath, file);
  let content = fs.readFileSync(filePath, 'utf8');

  // Apply general class replacements
  replacements.forEach(rep => {
    content = content.replace(rep.from, rep.to);
  });

  // Inject useTheme in <script setup>
  if (!content.includes('useTheme')) {
    content = content.replace(/<script setup>/, `<script setup>\nimport { useTheme } from '../composables/useTheme'\nconst { isDark, toggleTheme } = useTheme()`);
  }

  // Add ThemeToggle button before the Logout button
  // We can look for the handleLogout button
  const toggleBtnHtml = `
        <button @click="toggleTheme" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm theme-text-muted hover:theme-text hover:theme-card transition-all duration-150 mb-1">
          <span v-if="isDark">☀️ Modo claro</span>
          <span v-else>🌙 Modo oscuro</span>
        </button>
  `;

  // We find `<button @click="handleLogout"` and insert the toggle button right before it
  // But wait, what if it's already there?
  if (!content.includes('toggleTheme')) {
    content = content.replace(/(<button\s+[^>]*@click="handleLogout"[^>]*>)/, toggleBtnHtml + '$1');
  }

  fs.writeFileSync(filePath, content, 'utf8');
  console.log(`Processed ${file}`);
});
