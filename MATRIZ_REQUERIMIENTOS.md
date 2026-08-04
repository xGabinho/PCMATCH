# 📋 Matriz de Requerimientos Completa del Proyecto PCMATCH

## 1. Información General
- **Proyecto:** PCMATCH
- **Descripción:** Plataforma web integral para la cotización, verificación de compatibilidad, gestión de inventario/bodegas, catálogo de proveedores y administración de ensambles de componentes de PC.
- **Tecnologías:** 
  - **Frontend:** Vue 3 (Composition API), Vite, Tailwind CSS, Vue Router
  - **Backend:** Laravel API (PHP), Supabase (PostgreSQL / BaaS)
  - **Infraestructura:** Docker & Docker Compose
- **Versión:** 2.0.0

---

## 2. Archivos y Módulos Analizados

- **Core & Enrutamiento Backend:** `backend-laravel/routes/api.php`, `backend-laravel/routes/web.php`
- **Controladores API (`backend-laravel/app/Http/Controllers/Api/`):**
  - `AuthController.php`, `PasswordResetController.php`, `UsuarioController.php`, `PerfilController.php`, `ComponenteController.php`, `CatalogoController.php`, `CotizacionController.php`, `HistorialController.php`, `BodegaController.php`, `ProveedorController.php`, `ChatbotController.php`, `RecomendacionController.php`, `AnaliticaController.php`
- **Modelos de Datos (`backend-laravel/app/Models/`):**
  - `User.php`, `Usuario.php`, `Perfil.php`, `PerfilPermiso.php`, `Componente.php`, `ProductoCatalogo.php`, `Bodega.php`, `Proveedor.php`, `HistorialAccion.php`
- **Frontend SPA Vue 3 (`Frontend/src/`):**
  - Enrutamiento: `router/index.js`
  - Vistas principales: `LoginView.vue`, `ProfileView.vue`, `UserProfileView.vue`, `AdminView.vue`, `SuperAdminView.vue`, `BodegaView.vue`, `ProveedorView.vue`, `BuilderView.vue`, `QuoteView.vue`, `ClientHomeView.vue`, `DemoQuoteView.vue`, `ForgotPasswordView.vue`, `ResetPasswordView.vue`
- **Infraestructura & Configuración:** `docker-compose.yml`, `docker/`, `README.md`
- **Casos de Prueba QA (`qa/casos-prueba/`):** Suite completa de 10 archivos de prueba Markdown.

---

## 3. Matriz de Requerimientos del Proyecto

| ID | Tipo | Categoría | Requisito | Descripción | Prioridad | Módulo / Archivo fuente | Criterio de aceptación | Estado actual |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **RF-001** | Funcional | - | Registro de Usuario | Permitir a usuarios nuevos registrarse ingresando nombre, correo único, contraseña y datos personales. | Alta | `backend-laravel/app/Http/Controllers/Api/AuthController.php` (`register`) | El correo debe ser único, la clave debe ser hasheada y se asigna el rol 'cliente' por defecto. | Implementado |
| **RF-002** | Funcional | - | Inicio de Sesión | Autenticar usuarios registrados generando un token de acceso seguro (Laravel Sanctum). | Alta | `backend-laravel/app/Http/Controllers/Api/AuthController.php` (`login`) | Retorna credenciales válidas con token Bearer y datos del usuario con sus permisos asignados. | Implementado |
| **RF-003** | Funcional | - | Cierre de Sesión | Revocar e invalidar el token Sanctum activo del usuario autenticado. | Media | `backend-laravel/app/Http/Controllers/Api/AuthController.php` (`logout`) | Elimina el token actual en BD e impida peticiones subsecuentes autenticadas con dicho token. | Implementado |
| **RF-004** | Funcional | - | Solicitud de Recuperación de Contraseña | Enviar token/enlace por correo electrónico para restablecer la contraseña olvidada. | Alta | `backend-laravel/app/Http/Controllers/Api/PasswordResetController.php` (`sendResetLink`) | Genera token temporal en la tabla `password_reset_tokens` y notifica al usuario. | Implementado |
| **RF-005** | Funcional | - | Restablecimiento de Contraseña | Actualizar la contraseña del usuario previa validación de token de restablecimiento. | Alta | `backend-laravel/app/Http/Controllers/Api/PasswordResetController.php` (`resetPassword`) | Valida coincidencias de token, expira tokens previos y actualiza la clave encriptada. | Implementado |
| **RF-006** | Funcional | - | Edición de Perfil Propio | Permitir al usuario autenticado modificar sus datos personales, nombre y contraseña. | Media | `backend-laravel/app/Http/Controllers/Api/AuthController.php` (`updateProfile`) | Actualiza los datos del usuario logueado en la BD y sincroniza el almacenamiento local. | Implementado |
| **RF-007** | Funcional | - | Gestión de Usuarios (CRUD) | Permitir al rol Admin/Superadmin listar, crear, modificar y eliminar usuarios del sistema. | Alta | `backend-laravel/app/Http/Controllers/Api/UsuarioController.php` | Las operaciones CRUD responden con validación de roles y actualizan la tabla `usuarios`/`users`. | Implementado |
| **RF-008** | Funcional | - | Control de Acceso Basado en Roles (RBAC) | Restringir rutas y operaciones según los roles del usuario (cliente, admin, superadmin, bodega, proveedor). | Alta | `Frontend/src/router/index.js` (`router.beforeEach`) | Usuarios sin rol autorizado son redirigidos automáticamente a sus paneles correspondientes. | Implementado |
| **RF-009** | Funcional | - | Gestión de Perfiles y Permisos | Administración dinámica de perfiles de usuario y matriz de permisos del sistema. | Alta | `backend-laravel/app/Http/Controllers/Api/PerfilController.php` | El superadministrador asigna/revoca módulos permitidos por perfil. | Implementado |
| **RF-010** | Funcional | - | Catálogo Público de Componentes | Exponer consulta pública de componentes de hardware para el ensamblador y catálogo. | Alta | `backend-laravel/app/Http/Controllers/Api/CatalogoController.php` (`index`) | Devuelve lista de componentes activos filtrados por categoría, disponibilidad y precio. | Implementado |
| **RF-011** | Funcional | - | CRUD de Componentes de Hardware | Administración completa de componentes (CPU, GPU, RAM, Motherboard, Fuente, Almacenamiento, etc.). | Alta | `backend-laravel/app/Http/Controllers/Api/ComponenteController.php` | Permite registrar especificaciones técnicas, precio, imagen y disponibilidad. | Implementado |
| **RF-012** | Funcional | - | Especificaciones Técnicas Detalladas | Almacenamiento estructurado de compatibilidad por tipo de componente (Socket, TDP, Factor de forma). | Alta | `backend-laravel/app/Models/Componente.php` | Mantiene campos clave JSON/columnas para validación de arquitectura de hardware. | Implementado |
| **RF-013** | Funcional | - | Configurador Interactivo (Builder) | Interfaz visual paso a paso para la selección y ensamble de piezas de PC por parte del usuario. | Alta | `Frontend/src/views/BuilderView.vue` | Guía la selección ordenada de CPU, Placa Madre, RAM, GPU, Fuente, Gabinete y Almacenamiento. | Implementado |
| **RF-014** | Funcional | - | Verificación de Compatibilidad de Hardware | Algoritmo de validación de compatibilidad física y eléctrica entre piezas seleccionadas en el ensamble. | Alta | `backend-laravel/app/Http/Controllers/Api/ComponenteController.php` (`validarCompatibilidad`) | Verifica socket CPU-Motherboard, TDP total vs PSU y formato de gabinete. | Implementado |
| **RF-015** | Funcional | - | Cálculo de Consumo Energético (TDP) | Sumatoria automática de potencia consumida por los componentes y recomendación de PSU mínima. | Alta | `Frontend/src/views/BuilderView.vue` | Muestra el consumo estimado en Watts y alerta si la fuente seleccionada es insuficiente. | Implementado |
| **RF-016** | Funcional | - | Creación y Guardado de Cotizaciones | Permitir a los clientes guardar la configuración ensamblada como cotización personal. | Alta | `backend-laravel/app/Http/Controllers/Api/CotizacionController.php` (`store`) | Guarda el desglose de componentes, precio total calculado y estado de la cotización. | Implementado |
| **RF-017** | Funcional | - | Historial de Cotizaciones del Usuario | Consulta y visualización del registro de cotizaciones previas creadas por el cliente. | Media | `backend-laravel/app/Http/Controllers/Api/CotizacionController.php` (`index`) | Muestra lista paginada/ordenada con fecha, piezas y monto total de cotizaciones del usuario. | Implementado |
| **RF-018** | Funcional | - | Eliminación y Gestión de Cotizaciones | Permitir la eliminación o actualización de cotizaciones almacenadas por el usuario. | Baja | `backend-laravel/app/Http/Controllers/Api/CotizacionController.php` (`destroy`) | Remueve la cotización seleccionada de la base de datos de manera segura. | Implementado |
| **RF-019** | Funcional | - | Exportación/Vista Impresa de Cotización | Vista formateada de cotizaciones con opción de impresión / exportación a PDF. | Media | `Frontend/src/views/DemoQuoteView.vue` y `QuoteView.vue` | Genera plantilla apta para impresión con resumen de cotización y precios. | Implementado |
| **RF-020** | Funcional | - | Gestión de Almacenes / Bodegas | Módulo CRUD para el registro, edición y control de bodegas físicas de inventario. | Alta | `backend-laravel/app/Http/Controllers/Api/BodegaController.php` | Permite dar de alta bodegas, asignar ubicaciones y gestionar estado activo/inactivo. | Implementado |
| **RF-021** | Funcional | - | Control de Stock de Inventario por Bodega | Seguimiento y ajuste directo de existencias de componentes por bodega específica. | Alta | `backend-laravel/app/Http/Controllers/Api/ComponenteController.php` (`adjustStock`) | Incrementa/decrementa el stock disponible y notifica inventario crítico. | Implementado |
| **RF-022** | Funcional | - | Traslado e Inventario entre Bodegas | Transferencia y reasignación de stock de componentes entre distintas bodegas. | Media | `backend-laravel/app/Http/Controllers/Api/BodegaController.php` | Registra el movimiento de stock descontando del origen e incrementando en el destino. | Implementado |
| **RF-023** | Funcional | - | Gestión de Proveedores (CRUD) | Administración del directorio de proveedores de hardware y datos de contacto comercial. | Alta | `backend-laravel/app/Http/Controllers/Api/ProveedorController.php` | CRUD completo de proveedores con estado de relación comercial y datos fiscales. | Implementado |
| **RF-024** | Funcional | - | Asociación de Catálogo de Precios de Proveedor | Sincronización e ingreso de lista de productos y precios mayoristas por proveedor. | Media | `backend-laravel/app/Http/Controllers/Api/ProveedorController.php` (`syncProductos`) | Asigna código de producto del proveedor y costo base de importación. | Implementado |
| **RF-025** | Funcional | - | Asistente Conversacional (Chatbot IA) | Chat interactivo alimentado con IA para consultar recomendaciones de hardware y resolución de dudas. | Media | `backend-laravel/app/Http/Controllers/Api/ChatbotController.php` (`chat`) | Procesa mensajes en lenguaje natural y sugiere combinaciones compatibles de hardware. | Implementado |
| **RF-026** | Funcional | - | Recomendador de PC Ideal por Presupuesto | Motor de sugerencia de configuraciones de PC personalizadas según presupuesto y caso de uso. | Alta | `backend-laravel/app/Http/Controllers/Api/RecomendacionController.php` (`buildPcIdeal`) | Filtra piezas dentro del rango monetario indicando uso (Gaming, Oficina, Edición). | Implementado |
| **RF-027** | Funcional | - | Dashboard de Analíticas | Panel estadístico con gráficos de componentes más demandados, rotación y volumen de cotizaciones. | Alta | `backend-laravel/app/Http/Controllers/Api/AnaliticaController.php` | Renderiza agregaciones de datos por periodo, bodega y consumo por proveedor. | Implementado |
| **RF-028** | Funcional | - | Auditoría de Acciones del Sistema (Logs) | Registro automático de eventos críticos (creación, edición, eliminación de entidades) por usuario. | Alta | `backend-laravel/app/Http/Controllers/Api/HistorialController.php` | Guarda timestamp, IP, ID de usuario y detalle de la operación realizada. | Implementado |
| **RF-029** | Funcional | - | Endpoint Health Check de Infraestructura | Endpoint para verificación del estado del servidor API y conectividad con la base de datos. | Media | `backend-laravel/routes/api.php` (`/health`) | Retorna status HTTP 200 con JSON confirmando conexión DB a servicios PaaS/Docker. | Implementado |
| **RNF-001** | No Funcional | Seguridad | Autenticación Segura y Hashing | Encriptación de contraseñas de usuario usando algoritmos fuertes (Bcrypt/Argon2) y protección Sanctum. | Alta | `backend-laravel/app/Http/Controllers/Api/AuthController.php` (`Hash::make`) | Ninguna clave se almacena en texto plano; peticiones API requieren cabecera `Authorization: Bearer`. | Implementado |
| **RNF-002** | No Funcional | Seguridad | Sanitización e Inmunidad OWASP | Protección contra vulnerabilidades de inyección SQL, XSS y CSRF a nivel de ORM y middleware. | Alta | `backend-laravel/routes/api.php` / Laravel Eloquent | Consultas parametrizadas por defecto en Eloquent; middleware de CORS y sanitización habilitados. | Implementado |
| **RNF-003** | No Funcional | Rendimiento | Tiempo de Respuesta API Optimizada | Latencia de respuesta de los endpoints de lectura por debajo de los 300ms en condiciones normales. | Alta | `backend-laravel/app/Http/Controllers/Api/CatalogoController.php` | Uso de selectores explícitos, joins eficientes e índices en Supabase PostgreSQL. | Implementado |
| **RNF-004** | No Funcional | Rendimiento | Carga Rápida de SPA (Code Splitting) | Carga e interactividad inicial de la aplicación frontend (Vue 3 + Vite) en menos de 2 segundos. | Media | `Frontend/vite.config.js` y Vue Lazy Loading Routes | Empaquetamiento modular comprimido con Vite y división de código por rutas. | Implementado |
| **RNF-005** | No Funcional | Usabilidad | Diseño Responsivo y Moderno | Interfaz adaptativa optimizada para dispositivos móviles, tabletas y computadoras de escritorio. | Alta | `Frontend/src/style.css` y Componentes Vue Tailwind CSS | Maquetación con clases utilitarias de Tailwind CSS asegurando flexibilidad en cualquier viewport. | Implementado |
| **RNF-006** | No Funcional | Escalabilidad | Arquitectura Desacoplada Contenedorizada | Separación total de capas (Frontend SPA en Vue 3 y Backend REST API Laravel) orquestadas con Docker. | Alta | `docker-compose.yml` y `docker/` | Despliegue independiente de servicios frontend/backend mediante contenedores aislados. | Implementado |
| **RNF-007** | No Funcional | Disponibilidad | Persistencia y Alta Disponibilidad de Datos | Almacenamiento relacional tolerante a fallos gestionado en la nube con Supabase PostgreSQL. | Alta | `README.md` y `.env.example` | Replicación, respaldos automáticos y pools de conexiones mediante Supabase BaaS. | Implementado |
| **RNF-008** | No Funcional | Mantenibilidad | Principios SOLID y Estructura PSR | Código mantenible siguiendo patrones MVC en Laravel, modularidad en Vue y estándares PHP PSR-12. | Media | Estrutura de `backend-laravel` y `Frontend/src` | Separación clara de responsabilidades entre controladores, modelos, componentes y composables. | Implementado |
| **RNF-009** | No Funcional | Mantenibilidad | Documentación de Casos de Prueba QA | Cobertura documental y trazabilidad mediante suites de casos de prueba estructurados en Markdown. | Media | Directorio `qa/casos-prueba/` | 10 archivos de prueba exhaustivos que validan la funcionalidad de cada módulo. | Implementado |
| **RNF-010** | No Funcional | Compatibilidad | Portabilidad Multi-plataforma | Capacidad de ejecución multiplataforma consistente en entornos Windows, Linux y macOS vía Docker. | Baja | `docker-compose.yml` | Entorno de ejecución reproducible con Node 18+ y PHP 8.2+ independientemente del SO host. | Implementado |

---

## 4. Resumen de Requisitos

- **Por Tipo:**
  - **Requisitos Funcionales (RF):** 29
  - **Requisitos No Funcionales (RNF):** 10
  - **Total General:** 39

- **Por Prioridad:**
  - **Alta:** 25
  - **Media:** 11
  - **Baja:** 3
  - **Total General:** 39
