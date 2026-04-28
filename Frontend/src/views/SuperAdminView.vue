<template>
  <div class="flex h-screen overflow-hidden bg-dark-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r border-dark-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b border-dark-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-xs">PC</div>
        <div>
          <p class="text-text-primary font-semibold text-sm leading-none">PCMATCH</p>
          <p class="text-text-muted text-xs mt-0.5">Super Admin</p>
        </div>
      </div>

      <nav class="flex-1 p-3 space-y-1">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 text-left"
          :class="activeSection === section.id
            ? 'bg-accent/10 text-accent border border-accent/20'
            : 'text-text-muted hover:text-text-primary hover:bg-dark-card'"
        >
          <span>{{ section.icon }}</span>
          {{ section.label }}
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ section.count }}</span>
        </button>
      </nav>

      <div class="p-3 border-t border-dark-border">
        <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-text-muted hover:text-text-primary hover:bg-dark-card transition-all duration-150">
          ← Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b border-dark-border px-8 flex items-center justify-between sticky top-0 bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold text-text-primary">{{ currentSection.label }}</h1>
          <p class="text-xs text-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button v-if="currentSection.cta" @click="handleCta" class="btn-primary text-sm">
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

        <!-- ===== PROVEEDORES ===== -->
        <template v-if="activeSection === 'proveedores'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Total proveedores</p>
              <p class="text-3xl font-bold text-text-primary font-mono">{{ proveedores.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Aprobados</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ proveedores.filter(p => p.estado_aprobacion === 'aprobado').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Pendientes</p>
              <p class="text-3xl font-bold text-yellow-400 font-mono">{{ proveedores.filter(p => p.estado_aprobacion === 'pendiente').length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Directorio de Proveedores</h2>
              <input v-model="filterProveedor" type="text" placeholder="Buscar..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingProveedores" class="px-6 py-12 text-center text-text-muted text-sm">Cargando proveedores...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead>
                <tr><th v-for="h in ['Razón Social','ID Legal','Contacto','Documento','Aprobación','Cuenta','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredProveedores.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin proveedores registrados</td></tr>
                <tr v-for="p in filteredProveedores" :key="p.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ p.razon_social || p.nombre }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted font-mono">{{ p.identificacion_legal || 'N/A' }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">
                    <div>{{ p.nombre }}</div>
                    <div class="text-xs opacity-70">{{ p.correo }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <a v-if="p.documento_soporte_url" :href="p.documento_soporte_url" target="_blank" class="text-xs text-accent hover:underline flex items-center gap-1">
                      📄 Ver Doc
                    </a>
                    <span v-else class="text-xs text-text-muted">—</span>
                  </td>
                    <td class="px-6 py-4">
                      <span class="badge text-xs px-2.5 py-1" :class="{
                        'bg-green-500/10 text-green-400 border border-green-500/20': p.estado_aprobacion === 'aprobado',
                        'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20': p.estado_aprobacion === 'pendiente',
                        'bg-red-500/10 text-red-400 border border-red-500/20': p.estado_aprobacion === 'rechazado'
                      }">
                        {{ p.estado_aprobacion?.toUpperCase() || 'PENDIENTE' }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <span v-if="p.activo == 0" class="badge text-[10px] px-2 py-0.5 bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">Inactiva</span>
                      <span v-else class="badge text-[10px] px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/20">Activa</span>
                    </td>
                  <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                       <button v-if="user?.rol === 'superadmin' && p.estado_aprobacion !== 'aprobado'" @click="cambiarEstadoProveedor(p, 'aprobado')" class="text-xs text-text-muted hover:text-green-400 px-2 py-1 rounded hover:bg-green-400/10 transition-colors">Aprobar</button>
                       <button v-if="user?.rol === 'superadmin' && p.estado_aprobacion !== 'rechazado'" @click="cambiarEstadoProveedor(p, 'rechazado')" class="text-xs text-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Rechazar</button>
                       <button @click="toggleActivoProveedor(p)" class="text-xs text-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">
                         {{ p.activo == 1 ? 'Desactivar' : 'Activar' }}
                       </button>
                       <button @click="openEditProveedor(p)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== BODEGAS ===== -->
        <template v-if="activeSection === 'bodegas'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Total bodegas</p>
              <p class="text-3xl font-bold text-text-primary font-mono">{{ bodegas.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Activas</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ bodegas.filter(b => b.activa == 1).length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Sin proveedor</p>
              <p class="text-3xl font-bold text-yellow-400 font-mono">{{ bodegas.filter(b => !b.proveedor_id).length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Listado de bodegas</h2>
              <input v-model="filterBodega" type="text" placeholder="Buscar..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center text-text-muted text-sm">Cargando bodegas...</div>
            <table v-else class="w-full min-w-[800px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Nombre','Correo','Proveedor','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin bodegas registradas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ b.correo }}</td>
                  <td class="px-6 py-4">
                    <span v-if="b.proveedor_nombre" class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ b.proveedor_nombre }}</span>
                    <span v-else class="text-xs text-text-muted">Sin proveedor</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-text-primary font-mono">{{ b.total_componentes }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button @click="openEditBodega(b)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="toggleBodega(b)" class="text-xs text-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">
                        {{ b.activa == 1 ? 'Desactivar' : 'Activar' }}
                      </button>
                      <button @click="openDeleteBodega(b)" class="text-xs text-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Listado de componentes</h2>
              <input v-model="filterComponente" type="text" placeholder="Buscar..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingComponentes" class="px-6 py-12 text-center text-text-muted text-sm">Cargando componentes...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Componente','Categoría','Gama','Precio','Bodega','Stock','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="7" class="px-6 py-12 text-center text-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ c.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[c.gama]">{{ c.gama }}</span></td>
                  <td class="px-6 py-4 text-sm text-accent font-mono">${{ Number(c.precio).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ c.bodega_nombre }}</td>
                  <td class="px-6 py-4 text-sm font-mono" :class="c.stock <= 3 ? 'text-yellow-400' : 'text-text-primary'">{{ c.stock }}</td>
                  <td class="px-6 py-4">
                    <button @click="openEditComp(c)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COTIZACIONES ===== -->
        <template v-if="activeSection === 'cotizaciones'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border">
              <h2 class="font-semibold text-text-primary">Historial de cotizaciones</h2>
            </div>
            <div v-if="loadingCotizaciones" class="px-6 py-12 text-center text-text-muted text-sm">Cargando...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['#','Cliente','Perfil','Componentes','Total','Fecha']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="cotizaciones.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin cotizaciones</td></tr>
                <tr v-for="c in cotizaciones" :key="c.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-mono text-text-muted">#{{ c.id }}</td>
                  <td class="px-6 py-4 text-sm text-text-primary">{{ c.nombre }} {{ c.apellido }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ perfilLabel(c.perfil) }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-text-primary">{{ c.total_items }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-accent font-medium">${{ Number(c.total).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ formatDate(c.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== CREAR USUARIO ===== -->
        <template v-if="activeSection === 'crear-usuario'">
          <div class="max-w-xl">
            <div class="card-dark rounded-2xl p-8 space-y-6">
              <div>
                <label class="block text-sm font-medium text-text-primary mb-3">Rol del usuario</label>
                <div class="grid grid-cols-3 gap-3">
                  <button v-for="role in roles" :key="role.id" @click="newUser.rol = role.id"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border transition-all duration-150"
                    :class="newUser.rol === role.id ? 'border-accent bg-accent/5 text-accent' : 'border-dark-border text-text-muted hover:border-accent/40 hover:text-text-primary'"
                  >
                    <span class="text-2xl">{{ role.icon }}</span>
                    <span class="text-xs font-medium">{{ role.label }}</span>
                  </button>
                </div>
              </div>
              <div class="border-t border-dark-border"></div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
                  <input v-model="newUser.nombre" type="text" placeholder="Juan" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-text-primary mb-2">Apellido</label>
                  <input v-model="newUser.apellido" type="text" placeholder="Pérez" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Correo electrónico</label>
                <input v-model="newUser.correo" type="email" placeholder="usuario@email.com" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
                <input v-model="newUser.telefono" type="tel" placeholder="+57 300 123 4567" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium text-text-primary mb-2">Contraseña temporal</label>
                <input v-model="newUser.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>
              <p v-if="createUserError"   class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ createUserError }}</p>
              <p v-if="createUserSuccess" class="text-xs text-green-400 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-2.5">{{ createUserSuccess }}</p>
              <div class="flex gap-3 pt-2">
                <button @click="saveNewUser" :disabled="savingUser" class="btn-primary flex-1 text-sm">{{ savingUser ? 'Creando...' : 'Crear usuario' }}</button>
                <button @click="resetNewUser" class="btn-secondary text-sm px-5">Limpiar</button>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== GESTIONAR USUARIOS ===== -->
        <template v-if="activeSection === 'gestionar-usuarios'">
          <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Total</p>
              <p class="text-3xl font-bold text-text-primary font-mono">{{ usuarios.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Superadmins</p>
              <p class="text-3xl font-bold text-red-400 font-mono">{{ usuarios.filter(u => u.rol === 'superadmin').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Admins</p>
              <p class="text-3xl font-bold text-purple-400 font-mono">{{ usuarios.filter(u => u.rol === 'admin').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Clientes</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ usuarios.filter(u => u.rol === 'cliente').length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Usuarios registrados</h2>
              <input v-model="filterUsuario" type="text" placeholder="Buscar por nombre o correo..." class="bg-dark-bg border border-dark-border rounded-lg px-4 py-2 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-64" />
            </div>
            <div v-if="loadingUsuarios" class="px-6 py-12 text-center text-text-muted text-sm">Cargando usuarios...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Usuario','Correo','Teléfono','Rol','Estado','Registrado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredUsuarios.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin usuarios</td></tr>
                <tr v-for="u in filteredUsuarios" :key="u.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" :class="roleStyles[u.rol]?.avatar ?? 'bg-dark-card text-text-muted'">
                        {{ u.nombre.charAt(0) }}
                      </div>
                      <span class="text-sm font-medium text-text-primary">{{ u.nombre }} {{ u.apellido }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ u.correo }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ u.telefono || '—' }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="roleStyles[u.rol]?.badge ?? ''">{{ roleStyles[u.rol]?.label ?? u.rol }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <span v-if="u.activo == 0" class="badge text-[10px] px-2 py-0.5 bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">Inactivo</span>
                    <span v-else class="badge text-[10px] px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/20">Activo</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ formatDate(u.created_at) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button @click="toggleActivoUsuario(u)" class="text-xs text-text-muted hover:text-green-400 px-2 py-1 rounded hover:bg-green-400/10 transition-colors">
                           {{ u.activo == 1 ? 'Desactivar' : 'Activar' }}
                        </button>
                      <button @click="openEditUsuario(u)" class="text-xs text-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="openDeleteUsuario(u)" class="text-xs text-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== HISTORIAL ===== -->
        <template v-if="activeSection === 'historial'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b border-dark-border flex items-center justify-between">
              <h2 class="font-semibold text-text-primary">Historial de Acciones</h2>
            </div>
            <div v-if="loadingHistorial" class="px-6 py-12 text-center text-text-muted text-sm">Cargando historial...</div>
            <table v-else class="w-full min-w-[800px]">
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Fecha','Usuario','Rol','Acción','Módulo']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="historial.length === 0"><td colspan="5" class="px-6 py-12 text-center text-text-muted text-sm">Sin registros</td></tr>
                <tr v-for="h in historial" :key="h.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm text-text-muted whitespace-nowrap">{{ new Date(h.created_at).toLocaleString('es-CL') }}</td>
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ h.usuario_nombre || 'Usuario Eliminado' }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-[10px] px-2 py-0.5 border" :class="roleStyles[h.rol_usuario]?.badge ?? 'bg-dark-card border-dark-border'">{{ h.rol_usuario?.toUpperCase() ?? 'DESCONOCIDO' }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm text-text-primary">{{ h.accion }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted"><span class="badge text-xs bg-dark-bg border border-dark-border">{{ h.modulo }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

      </div>
    </main>

    <!-- ===== MODAL AGREGAR PROVEEDOR ===== -->
    <div v-if="showProveedorModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeProveedorModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Registrar proveedor</h2>
            <p class="text-xs text-text-muted mt-0.5">Ingresa los datos para registrar un proveedor</p>
          </div>
          <button @click="closeProveedorModal" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Razón Social</label>
            <input v-model="newProveedor.razon_social" type="text" placeholder="Empresa S.A." class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Identificación Legal (RUT/NIT)</label>
            <input v-model="newProveedor.identificacion_legal" type="text" placeholder="12345678-9" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div class="border-t border-dark-border pt-1">
            <p class="text-xs text-text-muted mb-2">Datos de acceso del proveedor</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Nombre representante</label>
            <input v-model="newProveedor.nombre" type="text" placeholder="Juan Pérez" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Correo electrónico</label>
            <input v-model="newProveedor.correo" type="email" placeholder="contacto@empresa.com" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Contraseña</label>
            <input v-model="newProveedor.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Documento de Soporte (PDF/Img)</label>
            <input @change="handleFileChange" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm text-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-dark-bg file:text-accent hover:file:bg-accent/10 transition-colors" />
          </div>
          <div class="rounded-lg border border-accent/20 bg-accent/5 p-3 flex items-start gap-2">
            <span class="text-accent text-sm mt-0.5">ℹ️</span>
            <p class="text-xs text-text-muted leading-relaxed">El proveedor iniciará en estado 'Pendiente' de aprobación. Puedes aprobarlo inmediatamente desde la lista.</p>
          </div>
          <p v-if="proveedorError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ proveedorError }}</p>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="saveNewProveedor" :disabled="savingProveedor" class="btn-primary flex-1 text-sm pt-2 pb-2">
            {{ savingProveedor ? 'Creando...' : 'Crear proveedor' }}
          </button>
          <button @click="closeProveedorModal" class="btn-secondary text-sm px-5 pt-2 pb-2">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR PROVEEDOR ===== -->
    <div v-if="showEditProveedorModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditProveedorModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Editar proveedor</h2>
            <p class="text-xs text-text-muted mt-0.5">Modifica y corrige los datos del proveedor</p>
          </div>
          <button @click="showEditProveedorModal = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Razón Social</label>
            <input v-model="editingProveedor.razon_social" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Identificación Legal</label>
            <input v-model="editingProveedor.identificacion_legal" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-1">Nombre representante</label>
            <input v-model="editingProveedor.nombre" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <p v-if="editProveedorError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editProveedorError }}</p>
        </div>
        <div class="flex gap-3 mt-6">
          <button @click="saveEditProveedor" :disabled="savingEditProveedor" class="btn-primary flex-1 text-sm pt-2 pb-2">
            {{ savingEditProveedor ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditProveedorModal = false" class="btn-secondary text-sm px-5 pt-2 pb-2">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR BODEGA ===== -->
    <div v-if="showEditBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-text-primary">Editar bodega</h2>
          <button @click="showEditBodegaModal = false" class="text-text-muted hover:text-text-primary text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
            <input v-model="editingBodega.nombre" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
            <input v-model="editingBodega.telefono" type="tel" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Proveedor asignado</label>
            <select v-model="editingBodega.proveedor_id" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors">
              <option :value="null">Sin proveedor</option>
              <option v-for="p in proveedores.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Estado</label>
            <div class="grid grid-cols-2 gap-3">
              <button @click="editingBodega.activa = 1" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-dark-border text-text-muted'">✓ Activa</button>
              <button @click="editingBodega.activa = 0" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 0 ? 'border-red-500/40 bg-red-500/10 text-red-400' : 'border-dark-border text-text-muted'">✕ Inactiva</button>
            </div>
          </div>
          <p v-if="editBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editBodegaError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveEditBodega" :disabled="savingEditBodega" class="btn-primary flex-1 text-sm">{{ savingEditBodega ? 'Guardando...' : 'Guardar cambios' }}</button>
          <button @click="showEditBodegaModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL AGREGAR BODEGA ===== -->
    <div v-if="showBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeBodegaModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Agregar bodega</h2>
            <p class="text-xs text-text-muted mt-0.5">Crea el acceso para el gestor de la bodega</p>
          </div>
          <button @click="closeBodegaModal" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Nombre de la bodega</label>
            <input v-model="newBodega.nombre" type="text" placeholder="Ej: TecnoStore Santiago" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div class="border-t border-dark-border pt-1">
            <p class="text-xs text-text-muted mb-4">Credenciales de acceso para el gestor</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Correo electrónico</label>
            <input v-model="newBodega.correo" type="email" placeholder="gestor@bodega.com" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Número de teléfono</label>
            <input v-model="newBodega.telefono" type="tel" placeholder="+56 9 1234 5678" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Contraseña de acceso</label>
            <input v-model="newBodega.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div class="rounded-lg border border-accent/20 bg-accent/5 p-3 flex items-start gap-2">
            <span class="text-accent text-sm mt-0.5">ℹ️</span>
            <p class="text-xs text-text-muted leading-relaxed">El gestor usará estas credenciales para ingresar al sistema y administrar el stock de su bodega.</p>
          </div>
          <p v-if="bodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ bodegaError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveNewBodega" :disabled="savingBodega" class="btn-primary flex-1 text-sm">
            {{ savingBodega ? 'Creando...' : 'Crear bodega' }}
          </button>
          <button @click="closeBodegaModal" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR BODEGA ===== -->
    <div v-if="showDeleteBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar bodega</h2>
        <p class="text-text-muted text-sm mb-1">¿Eliminar <span class="text-text-primary font-semibold">{{ deletingBodega?.nombre }}</span>?</p>
        <p v-if="deleteBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-2">{{ deleteBodegaError }}</p>
        <p class="text-xs text-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteBodega" :disabled="savingDeleteBodega" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteBodega ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteBodegaModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR USUARIO ===== -->
    <div v-if="showEditUsuarioModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditUsuarioModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold text-text-primary">Editar usuario</h2>
          <button @click="showEditUsuarioModal = false" class="text-text-muted hover:text-text-primary text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
              <input v-model="editingUsuario.nombre" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Apellido</label>
              <input v-model="editingUsuario.apellido" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Correo</label>
            <input v-model="editingUsuario.correo" type="email" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Teléfono</label>
            <input v-model="editingUsuario.telefono" type="tel" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-3">Rol</label>
            <div class="grid grid-cols-3 gap-2">
              <button v-for="role in roles" :key="role.id" @click="editingUsuario.rol = role.id"
                class="flex flex-col items-center gap-1.5 p-3 rounded-xl border text-xs font-medium transition-all"
                :class="editingUsuario.rol === role.id ? 'border-accent bg-accent/5 text-accent' : 'border-dark-border text-text-muted hover:border-accent/40'">
                <span class="text-lg">{{ role.icon }}</span>{{ role.label }}
              </button>
            </div>
          </div>
          <p v-if="editUsuarioError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editUsuarioError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveEditUsuario" :disabled="savingEditUsuario" class="btn-primary flex-1 text-sm">{{ savingEditUsuario ? 'Guardando...' : 'Guardar cambios' }}</button>
          <button @click="showEditUsuarioModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR USUARIO ===== -->
    <div v-if="showDeleteUsuarioModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteUsuarioModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar usuario</h2>
        <p class="text-text-muted text-sm mb-1">¿Eliminar a <span class="text-text-primary font-semibold">{{ deletingUsuario?.nombre }} {{ deletingUsuario?.apellido }}</span>?</p>
        <p class="text-xs text-text-muted mb-6">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteUsuario" :disabled="savingDeleteUsuario" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteUsuario ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteUsuarioModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditCompModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold text-text-primary">Editar componente</h2>
            <p class="text-xs text-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditCompModal = false" class="text-text-muted hover:text-text-primary transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>

        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Especificación técnica</label>
            <input v-model="editingComp.especificacion" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Precio ($)</label>
              <input v-model="editingComp.precio" type="number" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-primary mb-2">Stock</label>
              <input v-model="editingComp.stock" type="number" min="0" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-text-primary mb-3">Gama del componente</label>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" @click="editingComp.gama = tier"
                class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                :class="editingComp.gama === tier ? 'border-accent bg-accent/10 text-accent' : 'border-dark-border text-text-muted hover:border-accent/40'">
                {{ tier.charAt(0).toUpperCase() + tier.slice(1) }}
              </button>
            </div>
          </div>

          <p v-if="editCompError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editCompError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveEditComp" :disabled="savingEditComp" class="btn-primary flex-1 text-sm">
            {{ savingEditComp ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditCompModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const API = 'http://127.0.0.1:8000/api'
const { getToken, logout, user } = useAuth()
const router = useRouter()

function handleLogout() { logout(); router.push('/login') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function perfilLabel(p) { return ({ office: '💼 Oficina', gaming: '🎮 Gaming', design: '🎨 Diseño', study: '📚 Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('proveedores')

const sections = computed(() => [
  { id: 'proveedores',       icon: '🏢', label: 'Proveedores',       description: `${proveedores.value.length} proveedores`,       cta: '+ Agregar proveedor', count: proveedores.value.length   },
  { id: 'bodegas',           icon: '🏪', label: 'Bodegas',           description: `${bodegas.value.length} bodegas`,               cta: '+ Agregar bodega',                  count: bodegas.value.length        },
  { id: 'componentes',       icon: '🔧', label: 'Componentes',       description: `${componentes.value.length} componentes`,       cta: null,                  count: componentes.value.length    },
  { id: 'cotizaciones',      icon: '📄', label: 'Cotizaciones',      description: `${cotizaciones.value.length} cotizaciones`,     cta: null,                  count: cotizaciones.value.length   },
  { id: 'crear-usuario',     icon: '➕', label: 'Crear usuario',     description: 'Registrar nuevo usuario',                       cta: null,                  count: null                        },
  { id: 'gestionar-usuarios',icon: '👥', label: 'Gestionar usuarios',description: `${usuarios.value.length} usuarios`,            cta: '+ Crear usuario',     count: usuarios.value.length       },
  { id: 'historial',         icon: '📋', label: 'Historial',         description: 'Registro global de acciones',                   cta: null,                  count: historial.value.length      },
])

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

function handleCta() {
  if (activeSection.value === 'proveedores')        showProveedorModal.value = true
  if (activeSection.value === 'bodegas')            showBodegaModal.value = true
  if (activeSection.value === 'gestionar-usuarios') activeSection.value = 'crear-usuario'
}

// ── Estilos ───────────────────────────────────────────────
const roles = [
  { id: 'superadmin', icon: '👑', label: 'Superadmin' },
  { id: 'admin',      icon: '🛡️', label: 'Admin'      },
  { id: 'cliente',    icon: '👤', label: 'Cliente'    },
]

const roleStyles = {
  superadmin: { label: 'Superadmin', badge: 'bg-red-500/10 text-red-400 border border-red-500/20',       avatar: 'bg-red-500/20 text-red-400'       },
  admin:      { label: 'Admin',      badge: 'bg-purple-500/10 text-purple-400 border border-purple-500/20', avatar: 'bg-purple-500/20 text-purple-400' },
  cliente:    { label: 'Cliente',    badge: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',     avatar: 'bg-blue-500/20 text-blue-400'     },
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

// ── Proveedores ───────────────────────────────────────────────
const proveedores = ref([])
const loadingProveedores = ref(false)
const filterProveedor = ref('')

const showProveedorModal = ref(false)
const savingProveedor = ref(false)
const proveedorError = ref('')
const newProveedor = ref({ razon_social: '', identificacion_legal: '', nombre: '', correo: '', password: '', documento: null })

const showEditProveedorModal = ref(false)
const savingEditProveedor = ref(false)
const editProveedorError = ref('')
const editingProveedor = ref({})

const filteredProveedores = computed(() => {
  if (!filterProveedor.value.trim()) return proveedores.value
  const q = filterProveedor.value.toLowerCase()
  return proveedores.value.filter(p => 
    p.nombre.toLowerCase().includes(q) || 
    p.correo.toLowerCase().includes(q) || 
    (p.razon_social && p.razon_social.toLowerCase().includes(q)) || 
    (p.identificacion_legal && p.identificacion_legal.toLowerCase().includes(q))
  )
})

async function fetchProveedores() {
  loadingProveedores.value = true
  try {
    const res = await fetch(`${API}/proveedores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) proveedores.value = data.proveedores
  } catch(e) { console.error(e) } finally { loadingProveedores.value = false }
}

function openProveedorModal() {
  newProveedor.value = { razon_social: '', identificacion_legal: '', nombre: '', correo: '', password: '', documento: null }
  proveedorError.value = ''
  showProveedorModal.value = true
}

function closeProveedorModal() {
  showProveedorModal.value = false
  proveedorError.value = ''
}

function handleFileChange(event) {
  const file = event.target.files[0]
  if (file) {
    newProveedor.value.documento = file
  }
}

async function saveNewProveedor() {
  proveedorError.value = ''
  if (!newProveedor.value.razon_social || !newProveedor.value.identificacion_legal || !newProveedor.value.nombre || !newProveedor.value.correo || !newProveedor.value.password)
    return proveedorError.value = 'Todos los campos son requeridos excepto el documento (opcional)'
  
  savingProveedor.value = true
  
  const formData = new FormData()
  formData.append('razon_social', newProveedor.value.razon_social)
  formData.append('identificacion_legal', newProveedor.value.identificacion_legal)
  formData.append('nombre', newProveedor.value.nombre)
  formData.append('correo', newProveedor.value.correo)
  formData.append('password', newProveedor.value.password)
  
  if (newProveedor.value.documento) {
    formData.append('documento_soporte', newProveedor.value.documento)
  }

  try {
    const res = await fetch(`${API}/proveedores`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${getToken()}` },
      body: formData
    })
    const data = await res.json()
    if (!res.ok) return proveedorError.value = data.message ?? 'Error al crear'
    await fetchProveedores() // Como es uno nuevo, dejamos que traiga todo para obtener el ID real
    await fetchHistorial()
    closeProveedorModal()
  } catch(e) { proveedorError.value = 'Error de conexión' } finally { savingProveedor.value = false }
}

async function cambiarEstadoProveedor(p, estadoNuevo) {
  try {
    const res = await fetch(`${API}/proveedores`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: p.id,
        nombre: p.nombre,
        estado_aprobacion: estadoNuevo
      })
    });

    if (res.ok) {
      // ✅ FORZAMOS LA REACTIVIDAD: Creamos un array nuevo reemplazando solo el modificado
      proveedores.value = proveedores.value.map(prov => 
        prov.id === p.id ? { ...prov, estado_aprobacion: estadoNuevo } : prov
      );
      
      // Actualizamos historial (le agregamos un timestamp para evitar caché)
      fetch(`${API}/historial?t=${Date.now()}`, { headers: { Authorization: `Bearer ${getToken()}` } })
        .then(r => r.json())
        .then(d => historial.value = d.historial);

    } else {
      const errorData = await res.json();
      console.error("Laravel rechazó la petición:", errorData);
      alert("Laravel devolvió un error. Mira la consola para más detalles.");
    }
  } catch(e) { 
    console.error('Error de red al cambiar estado', e);
  }
}

async function toggleActivoProveedor(p) {
  const activaNuevo = p.activo == 1 ? 0 : 1;
  try {
    const res = await fetch(`${API}/proveedores`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: p.id,
        nombre: p.nombre,
        activo: activaNuevo
      })
    });

    if (res.ok) {
      // ✅ FORZAMOS LA REACTIVIDAD
      proveedores.value = proveedores.value.map(prov => 
        prov.id === p.id ? { ...prov, activo: activaNuevo } : prov
      );
      
      fetch(`${API}/historial?t=${Date.now()}`, { headers: { Authorization: `Bearer ${getToken()}` } })
        .then(r => r.json())
        .then(d => historial.value = d.historial);

    } else {
      const errorData = await res.json();
      console.error("Laravel rechazó la petición:", errorData);
      alert("Laravel devolvió un error. Mira la consola para más detalles.");
    }
  } catch(e) { 
    console.error('Error de red al desactivar', e);
  }
}

function openEditProveedor(p) {
  editingProveedor.value = { ...p }
  editProveedorError.value = ''
  showEditProveedorModal.value = true
}

async function saveEditProveedor() {
  editProveedorError.value = ''
  if (!editingProveedor.value.nombre || !editingProveedor.value.razon_social || !editingProveedor.value.identificacion_legal)
    return editProveedorError.value = 'Nombre, Razón Social e Identificación son requeridos'
    
  savingEditProveedor.value = true
  try {
    const res = await fetch(`${API}/proveedores`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: editingProveedor.value.id,
        nombre: editingProveedor.value.nombre,
        razon_social: editingProveedor.value.razon_social,
        identificacion_legal: editingProveedor.value.identificacion_legal
      })
    })
    const data = await res.json()
    if (!res.ok) return editProveedorError.value = data.message ?? 'Error al guardar cambios'
    
    // ✅ SOLUCIÓN: Actualización local para edición
    const index = proveedores.value.findIndex(p => p.id === editingProveedor.value.id)
    if (index !== -1) {
      proveedores.value[index].nombre = editingProveedor.value.nombre
      proveedores.value[index].razon_social = editingProveedor.value.razon_social
      proveedores.value[index].identificacion_legal = editingProveedor.value.identificacion_legal
    }
    
    await fetchHistorial()
    showEditProveedorModal.value = false
  } catch(e) { 
    editProveedorError.value = 'Error de conexión' 
  } finally { 
    savingEditProveedor.value = false 
  }
}

// ── Bodegas ───────────────────────────────────────────────
const bodegas            = ref([])
const loadingBodegas     = ref(false)
const filterBodega       = ref('')
const showEditBodegaModal   = ref(false)
const showDeleteBodegaModal = ref(false)
const showBodegaModal       = ref(false)
const newBodega             = ref({ nombre: '', correo: '', telefono: '', password: '' })
const bodegaError           = ref('')
const savingBodega          = ref(false)
const editingBodega      = ref({})
const deletingBodega     = ref(null)
const deleteBodegaError  = ref('')
const editBodegaError    = ref('')
const savingEditBodega   = ref(false)
const savingDeleteBodega = ref(false)

const filteredBodegas = computed(() => {
  if (!filterBodega.value.trim()) return bodegas.value
  const q = filterBodega.value.toLowerCase()
  return bodegas.value.filter(b => b.nombre.toLowerCase().includes(q) || b.correo.toLowerCase().includes(q))
})

async function fetchBodegas() {
  loadingBodegas.value = true
  try {
    const res = await fetch(`${API}/bodegas`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) bodegas.value = data.bodegas
  } catch(e) { console.error(e) } finally { loadingBodegas.value = false }
}

function openEditBodega(b) { editingBodega.value = { ...b }; editBodegaError.value = ''; showEditBodegaModal.value = true }

async function saveEditBodega() {
  editBodegaError.value = ''
  if (!editingBodega.value.nombre) return editBodegaError.value = 'El nombre es requerido'
  savingEditBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: editingBodega.value.id, nombre: editingBodega.value.nombre, telefono: editingBodega.value.telefono, activa: editingBodega.value.activa, proveedor_id: editingBodega.value.proveedor_id })
    })
    const data = await res.json()
    if (!res.ok) return editBodegaError.value = data.message ?? 'Error'
    
    // ✅ SOLUCIÓN: Actualización local completa (incluyendo proveedor y estado)
    const index = bodegas.value.findIndex(b => b.id === editingBodega.value.id)
    if (index !== -1) {
      bodegas.value[index].nombre = editingBodega.value.nombre
      bodegas.value[index].telefono = editingBodega.value.telefono
      bodegas.value[index].activa = editingBodega.value.activa
      bodegas.value[index].proveedor_id = editingBodega.value.proveedor_id
      // Actualizar el nombre del proveedor para que se refleje en la tabla
      const prov = proveedores.value.find(p => p.id === editingBodega.value.proveedor_id)
      bodegas.value[index].proveedor_nombre = prov ? prov.nombre : null
    }

    await fetchHistorial()
    showEditBodegaModal.value = false
  } catch(e) { editBodegaError.value = 'Error de conexión' } finally { savingEditBodega.value = false }
}

async function toggleBodega(b) {
  const activaNuevo = b.activa == 1 ? 0 : 1
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: b.id, nombre: b.nombre, telefono: b.telefono, activa: activaNuevo, proveedor_id: b.proveedor_id })
    })
    
    if (res.ok) {
      // ✅ SOLUCIÓN: Actualización local
      const index = bodegas.value.findIndex(bod => bod.id === b.id)
      if (index !== -1) bodegas.value[index].activa = activaNuevo
      await fetchHistorial()
    }
  } catch(e) { console.error('Error al cambiar estado de bodega', e) }
}

function openDeleteBodega(b) {
  deletingBodega.value = b;
  deleteBodegaError.value = '';
  showDeleteBodegaModal.value = true
}

async function confirmDeleteBodega() {
  deleteBodegaError.value = ''
  savingDeleteBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas?id=${deletingBodega.value.id}`, {
      method: 'DELETE', headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
        deleteBodegaError.value = data.message ?? 'Error al eliminar'
        return
    }
    
    // ✅ SOLUCIÓN: Eliminar localmente
    bodegas.value = bodegas.value.filter(b => b.id !== deletingBodega.value.id)
    await fetchHistorial()
    showDeleteBodegaModal.value = false
  } catch(e) { console.error(e); deleteBodegaError.value = 'Error de conexión' } finally { savingDeleteBodega.value = false }
}

function closeBodegaModal() {
  showBodegaModal.value = false
  bodegaError.value = ''
}

async function saveNewBodega() {
  bodegaError.value = ''
  if (!newBodega.value.nombre || !newBodega.value.correo || !newBodega.value.password)
    return bodegaError.value = 'Nombre, correo y contraseña son requeridos'
  savingBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newBodega.value)
    })
    const data = await res.json()
    if (!res.ok) return bodegaError.value = data.message ?? 'Error al crear'
    await fetchBodegas() // Fetch necesario para el nuevo ID
    await fetchHistorial()
    closeBodegaModal()
  } catch(e) { bodegaError.value = 'Error de conexión' } finally { savingBodega.value = false }
}

// ── Componentes ───────────────────────────────────────────
const componentes       = ref([])
const loadingComponentes = ref(false)
const filterComponente  = ref('')
const showEditCompModal = ref(false)
const editingComp = ref({})
const editCompError = ref('')
const savingEditComp = ref(false)

const filteredComponentes = computed(() => {
  if (!filterComponente.value.trim()) return componentes.value
  const q = filterComponente.value.toLowerCase()
  return componentes.value.filter(c => c.nombre.toLowerCase().includes(q) || c.categoria.toLowerCase().includes(q))
})

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/componentes/admin`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.componentes
  } catch(e) { console.error(e) } finally { loadingComponentes.value = false }
}

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editCompError.value = ''
  showEditCompModal.value = true
}

async function saveEditComp() {
  editCompError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(editingComp.value.precio) <= 0) {
    return editCompError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && Number(editingComp.value.stock) < 0) {
    return editCompError.value = 'El stock no puede ser negativo'
  }

  savingEditComp.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id:             editingComp.value.id,
        especificacion: editingComp.value.especificacion,
        gama:           editingComp.value.gama,
        precio:         editingComp.value.precio,
        stock:          editingComp.value.stock,
      })
    })
    const data = await res.json()
    if (!res.ok) return editCompError.value = data.message ?? 'Error al guardar'
    
    // ✅ SOLUCIÓN: Actualización local
    const index = componentes.value.findIndex(c => c.id === editingComp.value.id)
    if (index !== -1) {
      componentes.value[index].especificacion = editingComp.value.especificacion
      componentes.value[index].gama = editingComp.value.gama
      componentes.value[index].precio = editingComp.value.precio
      componentes.value[index].stock = editingComp.value.stock
    }

    await fetchHistorial()
    showEditCompModal.value = false
  } catch (e) {
    editCompError.value = 'Error de conexión'
  } finally {
    savingEditComp.value = false
  }
}

// ── Cotizaciones ──────────────────────────────────────────
const cotizaciones       = ref([])
const loadingCotizaciones = ref(false)

async function fetchCotizaciones() {
  loadingCotizaciones.value = true
  try {
    const res = await fetch(`${API}/cotizaciones`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) cotizaciones.value = data.cotizaciones
  } catch(e) { console.error(e) } finally { loadingCotizaciones.value = false }
}

// ── Usuarios ──────────────────────────────────────────────
const usuarios              = ref([])
const loadingUsuarios       = ref(false)
const filterUsuario         = ref('')
const showEditUsuarioModal  = ref(false)
const showDeleteUsuarioModal = ref(false)
const editingUsuario        = ref({})
const deletingUsuario       = ref(null)
const editUsuarioError      = ref('')
const savingEditUsuario     = ref(false)
const savingDeleteUsuario   = ref(false)
const createUserError       = ref('')
const createUserSuccess     = ref('')
const savingUser            = ref(false)
const newUser = ref({ rol: 'cliente', nombre: '', apellido: '', correo: '', telefono: '', password: '' })

const filteredUsuarios = computed(() => {
  if (!filterUsuario.value.trim()) return usuarios.value
  const q = filterUsuario.value.toLowerCase()
  return usuarios.value.filter(u => u.nombre.toLowerCase().includes(q) || u.correo.toLowerCase().includes(q) || u.apellido?.toLowerCase().includes(q))
})

async function fetchUsuarios() {
  loadingUsuarios.value = true
  try {
    const res = await fetch(`${API}/usuarios`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) usuarios.value = data.usuarios
  } catch(e) { console.error(e) } finally { loadingUsuarios.value = false }
}

function resetNewUser() {
  newUser.value = { rol: 'cliente', nombre: '', apellido: '', correo: '', telefono: '', password: '' }
  createUserError.value = ''; createUserSuccess.value = ''
}

async function saveNewUser() {
  createUserError.value = ''; createUserSuccess.value = ''
  if (!newUser.value.nombre || !newUser.value.correo || !newUser.value.password)
    return createUserError.value = 'Nombre, correo y contraseña son requeridos'
  savingUser.value = true
  try {
    const res = await fetch(`${API}/usuarios`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newUser.value)
    })
    const data = await res.json()
    if (!res.ok) return createUserError.value = data.message ?? 'Error al crear'
    createUserSuccess.value = 'Usuario creado correctamente'
    resetNewUser()
    await fetchUsuarios() // Fetch para obtener el nuevo usuario con ID
    await fetchHistorial()
  } catch(e) { createUserError.value = 'Error de conexión' } finally { savingUser.value = false }
}

function openEditUsuario(u) { editingUsuario.value = { ...u }; editUsuarioError.value = ''; showEditUsuarioModal.value = true }

async function saveEditUsuario() {
  editUsuarioError.value = ''
  savingEditUsuario.value = true
  try {
    const res = await fetch(`${API}/usuarios`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(editingUsuario.value)
    })
    const data = await res.json()
    if (!res.ok) return editUsuarioError.value = data.message ?? 'Error'
    
    // ✅ SOLUCIÓN: Actualización local
    const index = usuarios.value.findIndex(u => u.id === editingUsuario.value.id)
    if (index !== -1) {
      Object.assign(usuarios.value[index], editingUsuario.value)
    }

    await fetchHistorial()
    showEditUsuarioModal.value = false
  } catch(e) { editUsuarioError.value = 'Error de conexión' } finally { savingEditUsuario.value = false }
}

function openDeleteUsuario(u) { deletingUsuario.value = u; showDeleteUsuarioModal.value = true }

async function confirmDeleteUsuario() {
  savingDeleteUsuario.value = true
  try {
    const res = await fetch(`${API}/usuarios?id=${deletingUsuario.value.id}`, {
      method: 'DELETE', headers: { Authorization: `Bearer ${getToken()}` }
    })
    
    if (res.ok) {
      // ✅ SOLUCIÓN: Eliminar localmente
      usuarios.value = usuarios.value.filter(u => u.id !== deletingUsuario.value.id)
      await fetchHistorial()
    }
    showDeleteUsuarioModal.value = false
  } catch(e) { console.error(e) } finally { savingDeleteUsuario.value = false }
}

async function toggleActivoUsuario(u) {
  const activoNuevo = u.activo == 1 ? 0 : 1
  try {
    const res = await fetch(`${API}/usuarios`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: u.id,
        nombre: u.nombre,
        correo: u.correo,
        rol: u.rol,
        activo: activoNuevo
      })
    })
    if (res.ok) {
      // ✅ SOLUCIÓN: Actualización local
      const index = usuarios.value.findIndex(usr => usr.id === u.id)
      if (index !== -1) usuarios.value[index].activo = activoNuevo
      await fetchHistorial()
    }
  } catch(e) { console.error('Error al cambiar de estado', e) }
}

// ── Historial ─────────────────────────────────────────────
const historial = ref([])
const loadingHistorial = ref(false)

async function fetchHistorial() {
  loadingHistorial.value = true
  try {
    const res = await fetch(`${API}/historial`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) historial.value = data.historial
  } catch(e) { console.error(e) } finally { loadingHistorial.value = false }
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchProveedores()
  fetchBodegas()
  fetchComponentes()
  fetchCotizaciones()
  fetchUsuarios()
  fetchHistorial()
})
</script>
