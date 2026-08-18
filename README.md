# 🖥️ PCMATCH

PCMATCH es una aplicación web diseñada para facilitar la cotización, gestión de historiales y administración de perfiles para la compatibilidad y ensamblaje de componentes de PC.

## 🚀 Tecnologías

El proyecto está dividido en un backend robusto y un frontend moderno y reactivo, todo contenedorizado para un desarrollo ágil.

**Frontend:**
- ⚡ [Vue 3](https://vuejs.org/) (Composition API)
- 🛠️ [Vite](https://vitejs.dev/) - Entorno de desarrollo rápido
- 🎨 [Tailwind CSS](https://tailwindcss.com/) - Estilos utilitarios
- 🛣️ Vue Router - Enrutamiento del cliente

**Backend:**
- 🐘 [Laravel](https://laravel.com/) (PHP) - Framework de API
- 🗄️ [Supabase](https://supabase.com/) - Base de datos PostgreSQL y Backend-as-a-Service

**Infraestructura:**
- 🐳 [Docker & Docker Compose](https://www.docker.com/) - Contenedorización de servicios

## ✨ Características Principales

- **Gestión de Perfiles:** Autenticación y administración de datos de usuarios (`PerfilController`).
- **Cotizaciones de PC:** Sistema para armar y guardar cotizaciones de ensamblajes de componentes (`CotizacionController`).
- **Historial:** Seguimiento y registro de las cotizaciones y actividades de los usuarios (`HistorialController`).

## 📁 Estructura del Proyecto

```text
PCMATCH/
├── backend-laravel/    # API en Laravel
├── Frontend/           # Aplicación SPA en Vue 3 + Vite
├── docker/             # Configuraciones específicas de Docker
├── docker-compose.yml  # Orquestación de contenedores
└── ...
 ```
⚙️ Requisitos Previos
Asegúrate de tener instalado lo siguiente en tu sistema:

Docker Desktop (o Docker Engine + Docker Compose)

Node.js (opcional para desarrollo local del frontend fuera de Docker, v18+ recomendado)

Composer (opcional para desarrollo local de Laravel sin Docker)

🛠️ Instalación y Configuración
1. Clonar el repositorio
2. Levantar el entorno con Docker
El proyecto utiliza Docker para facilitar el levantamiento de los servicios del backend, base de datos (si aplica local) y servidores web.
  ```sh
  docker-compose up -d
  ```
(Nota: Asegúrate de revisar las variables de entorno necesarias para los contenedores en caso de que existan archivos .env.example en la raíz o en las carpetas específicas).

3. Configuración del Backend (Laravel)
Entra al directorio del backend e instala las dependencias:
  ```sh
cd backend-laravel
# Copia el archivo de entorno
cp .env.example .env
# Genera la key de la aplicación
php artisan key:generate
# (Si usas Supabase, asegúrate de añadir las credenciales en el .env de Laravel)
  ```
4. Configuración del Frontend (Vue)
En otra terminal, entra a la carpeta del frontend para instalar los paquetes de Node:
  ```sh
cd Frontend
npm install
npm run dev
  ```
🔗 Enlaces y Puertos (Por Defecto)
Frontend (Vite): http://localhost:5173 (Dependiendo de la configuración)
Backend API (Laravel): http://localhost:8000 (Revisar puertos en docker-compose.yml)

---

## ☁️ Guía de Despliegue en Producción

### 1. Backend en Render (Web Service)
1. Inicia sesión en [Render](https://dashboard.render.com/) y crea un **New Web Service**.
2. Conecta tu repositorio de GitHub.
3. Configuración del servicio:
   - **Root Directory**: `backend-laravel`
   - **Runtime / Environment**: `Docker`
   - **Dockerfile Path**: `Dockerfile` (o relativo al root: `backend-laravel/Dockerfile`)
   - **Health Check Path**: `/api/health`
4. Añade las siguientes **Environment Variables**:
   - `APP_NAME`: `PCMATCH`
   - `APP_ENV`: `production`
   - `APP_DEBUG`: `false`
   - `APP_KEY`: *(Generar con `php artisan key:generate --show` o dejar que se autogenere)*
   - `APP_URL`: `https://tu-backend.onrender.com`
   - `DB_CONNECTION`: `pgsql`
   - `DB_HOST`: `aws-1-us-east-1.pooler.supabase.com`
   - `DB_PORT`: `6543`
   - `DB_DATABASE`: `postgres`
   - `DB_USERNAME`: `tu_usuario_supabase`
   - `DB_PASSWORD`: `tu_contraseña_supabase`
   - `FRONTEND_URL`: `https://tu-frontend.vercel.app`
   - `GEMINI_API_KEY`: `tu_api_key_gemini`
   *(Consulta `backend-laravel/.env.render` para la lista completa de variables recomendadas).*

*(Nota: También puedes usar el Blueprint `render.yaml` incluido en el repositorio para desplegar la infraestructura de forma automática).*

---

### 2. Frontend en Vercel
1. Inicia sesión en [Vercel](https://vercel.com/) y haz clic en **Add New Project**.
2. Importa tu repositorio.
3. En la configuración del proyecto:
   - **Framework Preset**: `Vite`
   - **Root Directory**: `Frontend`
   - **Build Command**: `npm run build`
   - **Output Directory**: `dist`
4. Añade la variable de entorno:
   - `VITE_API_URL`: `https://tu-backend.onrender.com/api`
5. Haz clic en **Deploy**. El archivo `Frontend/vercel.json` gestionará automáticamente el enrutamiento de la SPA.
