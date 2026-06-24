const fs = require('fs');
const path = require('path');

const files = [
  'AdminView.vue',
  'BodegaView.vue',
  'BuilderView.vue',
  'ClientHomeView.vue',
  'ProveedorView.vue',
  'SuperAdminView.vue'
];

const basePath = path.join(__dirname, 'Frontend', 'src', 'views');

files.forEach(file => {
  const filePath = path.join(basePath, file);
  if (!fs.existsSync(filePath)) return;
  
  let content = fs.readFileSync(filePath, 'utf8');
  
  // Resolve conflicts by keeping HEAD
  const conflictRegex = /<<<<<<< HEAD\r?\n([\s\S]*?)=======\r?\n([\s\S]*?)>>>>>>> main\r?\n/g;
  
  content = content.replace(conflictRegex, '$1');
  
  fs.writeFileSync(filePath, content, 'utf8');
  console.log(`Resolved conflicts in ${file} by keeping HEAD`);
});
