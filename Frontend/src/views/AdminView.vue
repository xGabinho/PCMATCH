<template>
  <div class="flex h-screen overflow-hidden theme-bg">

    <!-- Admin Sidebar -->
    <aside class="w-60 border-r theme-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b theme-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-xs">PC</div>
        <div>
          <p class="theme-text font-semibold text-sm leading-none">PCMATCH</p>
          <p class="theme-text-muted text-xs mt-0.5">Panel Admin</p>
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
            : 'theme-text-muted hover:theme-text hover:theme-card'"
        >
          <component :is="section.icon" class="w-5 h-5 inline-block" />
          {{ section.label }}
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ section.count }}</span>
        </button>
      </nav>

      <div class="p-3 border-t theme-border space-y-1">
        <button @click="toggleTheme" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm theme-text-muted hover:theme-text hover:theme-card transition-all duration-150">
          <span v-if="isDark"><Sun class="w-4 h-4 inline-block mr-1" /> Modo claro</span>
          <span v-else><Moon class="w-4 h-4 inline-block mr-1" /> Modo oscuro</span>
        </button>
        <button @click="handleLogout" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm theme-text-muted hover:theme-text hover:theme-card transition-all duration-150">
          ← Cerrar sesión
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b theme-border px-8 flex items-center justify-between sticky top-0 bg-light-bg/90 dark:bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold theme-text">{{ currentSection?.label || 'Panel Admin' }}</h1>
          <p class="text-xs theme-text-muted mt-0.5">{{ currentSection?.description || 'Administración de sistema' }}</p>
        </div>
        <button
          v-if="currentSection?.cta"
          @click="activeSection === 'bodegas' ? openBodegaModal() : activeSection === 'gestionar-usuarios' ? activeSection = 'crear-usuario' : null"
          class="btn-primary text-sm"
        >
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

        <div v-if="sections.length === 0" class="card-dark rounded-xl p-12 text-center theme-text-muted my-12 max-w-lg mx-auto border theme-border">
          <Lock class="w-12 h-12 mx-auto mb-4 text-accent" />
          <h3 class="text-lg font-bold theme-text mb-2">Sin módulos habilitados</h3>
          <p class="text-sm leading-relaxed">Tu perfil no cuenta con permisos suficientes para administrar los módulos habilitados. Contacta a un administrador principal.</p>
        </div>

        <!-- ===== BODEGAS ===== -->
        <template v-if="activeSection === 'bodegas'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total bodegas</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ bodegas.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Bodegas activas</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ bodegas.filter(b => b.activa == 1).length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Bodegas inactivas</p>
              <p class="text-3xl font-bold text-red-400 font-mono">{{ bodegas.filter(b => b.activa == 0).length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Listado de bodegas</h2>
              <input v-model="filterBodega" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando bodegas...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Nombre','Teléfono','Correo','Proveedor','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-border">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin bodegas registradas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ b.telefono || '—' }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ b.correo }}</td>
                  <td class="px-6 py-4">
                    <span v-if="b.proveedor_nombre" class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ b.proveedor_nombre }}</span>
                    <span v-else class="text-xs theme-text-muted">Sin proveedor</span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text font-mono">{{ b.total_componentes }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button @click="openEditBodega(b)" class="text-xs theme-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="toggleBodega(b)" class="text-xs theme-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">
                        {{ b.activa == 1 ? 'Desactivar' : 'Activar' }}
                      </button>
                      <button @click="openDeleteBodega(b)" class="text-xs theme-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
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
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Listado de componentes</h2>
              <div class="flex items-center gap-3">
                <input v-model="filterComponente" type="text" placeholder="Buscar por nombre o categoría..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-72" />
              </div>
            </div>
            <div v-if="loadingComponentes" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando componentes...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Componente','Categoría','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="4" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">
                    <div>{{ c.nombre }}</div>
                    <div class="text-xs theme-text-muted opacity-75 truncate max-w-xs">{{ c.especificacion }}</div>
                  </td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="c.activo == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ c.activo == 1 ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button @click="toggleComponente(c)" class="text-xs theme-text-muted hover:text-green-400 px-2 py-1 rounded hover:bg-green-400/10 transition-colors">
                        {{ c.activo == 1 ? 'Desactivar' : 'Activar' }}
                      </button>
                      <button @click="openEditComp(c)" class="text-xs theme-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="openDeleteComp(c)" class="text-xs theme-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COTIZACIONES ===== -->
        <template v-if="activeSection === 'cotizaciones'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border">
              <h2 class="font-semibold theme-text">Historial de cotizaciones</h2>
            </div>
            <div v-if="loadingCotizaciones" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['#','Cliente','Perfil','Componentes','Total','Fecha']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-border">
                <tr v-if="cotizaciones.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin cotizaciones</td></tr>
                <tr v-for="c in cotizaciones" :key="c.id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-mono theme-text-muted">#{{ c.id }}</td>
                  <td class="px-6 py-4 text-sm theme-text">{{ c.nombre }} {{ c.apellido }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ perfilLabel(c.perfil) }}</td>
                  <td class="px-6 py-4 text-sm font-mono theme-text">{{ c.total_items }}</td>
                  <td class="px-6 py-4 text-sm font-mono text-accent font-medium">${{ Number(c.total).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ formatDate(c.created_at) }}</td>
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
                <label class="block text-sm font-medium theme-text mb-3">Rol del usuario</label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    v-for="role in roles"
                    :key="role.id"
                    @click="newUser.rol = role.id"
                    class="flex flex-col items-center gap-2 p-4 rounded-xl border transition-all duration-150"
                    :class="newUser.rol === role.id
                      ? 'border-accent bg-accent/5 text-accent'
                      : 'theme-border theme-text-muted hover:border-accent/40 hover:theme-text'"
                  >
                    <component :is="role.icon" class="text-2xl inline-block" />
                    <span class="text-xs font-medium">{{ role.label }}</span>
                  </button>
                </div>
                <p class="text-xs theme-text-muted mt-2 min-h-[1rem]">{{ roles.find(r => r.id === newUser.rol)?.description }}</p>
              </div>

              
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Perfil de Permisos (Opcional)</label>
                <select v-model="newUser.perfil_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                  <option :value="null">Sin perfil</option>
                  <option v-for="p in perfiles.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                </select>
              </div>

              <div class="border-t theme-border"></div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Nombre</label>
                  <input v-model="newUser.nombre" type="text" placeholder="Juan" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
                </div>
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Apellido</label>
                  <input v-model="newUser.apellido" type="text" placeholder="Pérez" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
                <input v-model="newUser.correo" type="email" placeholder="usuario@email.com" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>

              <div>
                <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
                <div class="flex gap-2">
                  <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                    <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
                  </div>
                  <input v-model="newUser.telefonoLocal" @input="handleTelefonoInput(newUser, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
                </div>
                <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
              </div>

              <div>
                <label class="block text-sm font-medium theme-text mb-2">Contraseña temporal</label>
                <input v-model="newUser.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>

              <p v-if="createUserError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ createUserError }}</p>
              <p v-if="createUserSuccess" class="text-xs text-green-400 bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-2.5">{{ createUserSuccess }}</p>

              <div class="flex gap-3 pt-2">
                <button @click="saveNewUser" :disabled="savingUser" class="btn-primary flex-1 text-sm">
                  {{ savingUser ? 'Creando...' : 'Crear usuario' }}
                </button>
                <button @click="resetNewUser" class="btn-secondary text-sm px-5">Limpiar</button>
              </div>
            </div>
          </div>
        </template>


        <!-- ===== PERFILES Y PERMISOS ===== -->
        <template v-if="activeSection === 'perfiles'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Perfiles de Permisos</h2>
            </div>
            <div v-if="loadingPerfiles" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando perfiles...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Nombre','Descripción','Permisos','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-border">
                <tr v-if="perfiles.length === 0"><td colspan="5" class="px-6 py-12 text-center theme-text-muted text-sm">Sin perfiles</td></tr>
                <tr v-for="p in perfiles" :key="p.id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ p.nombre }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted max-w-48 truncate">{{ p.descripcion || '—' }}</td>
                  <td class="px-6 py-4 text-sm theme-text font-mono">{{ p.permisos?.length || 0 }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="p.activo == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ p.activo == 1 ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-right space-x-2">
                    <button @click="openEditPerfil(p)" class="p-2 theme-bg border theme-border rounded-lg theme-text-muted hover:text-accent hover:border-accent/40 transition-colors"><Pencil class="w-4 h-4 inline-block" /></button>
                    <button @click="confirmDeletePerfilAction(p)" class="p-2 theme-bg border theme-border rounded-lg theme-text-muted hover:text-red-400 hover:border-red-500/40 transition-colors"><Trash2 class="w-4 h-4 inline-block" /></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== GESTIONAR USUARIOS ===== -->
<template v-if="activeSection === 'gestionar-usuarios'">
          <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total usuarios</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ usuarios.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Admins</p>
              <p class="text-3xl font-bold text-purple-400 font-mono">{{ usuarios.filter(u => u.rol === 'admin').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Clientes</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ usuarios.filter(u => u.rol === 'cliente').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Inactivos</p>
              <p class="text-3xl font-bold text-red-400 font-mono">{{ usuarios.filter(u => u.activo == 0).length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Usuarios registrados</h2>
              <input v-model="filterUsuario" type="text" placeholder="Buscar por nombre o correo..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-64" />
            </div>
            <div v-if="loadingUsuarios" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando usuarios...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Usuario','Correo','Teléfono','Rol','Estado','Registrado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-border">
                <tr v-if="filteredUsuarios.length === 0"><td colspan="7" class="px-6 py-12 text-center theme-text-muted text-sm">Sin usuarios</td></tr>
                <tr v-for="u in filteredUsuarios" :key="u.id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors" :class="u.activo == 0 ? 'opacity-50' : ''">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" :class="roleStyles[u.rol?.toLowerCase()]?.avatar ?? 'theme-card theme-text-muted'">
                        {{ u.nombre.charAt(0) }}
                      </div>
                      <span class="text-sm font-medium theme-text">{{ u.nombre }} {{ u.apellido }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ u.correo }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ u.telefono || '—' }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="roleStyles[u.rol?.toLowerCase()]?.badge ?? ''">{{ roleStyles[u.rol?.toLowerCase()]?.label ?? u.rol }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="u.activo == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ u.activo == 1 ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ formatDate(u.created_at) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                      <button @click="openEditModal(u)" class="text-xs theme-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">Editar</button>
                      
                      <button @click="openDeleteModal(u)" class="text-xs px-2 py-1 rounded transition-colors"
                        :class="u.activo == 1
                          ? 'theme-text-muted hover:text-red-400 hover:bg-red-400/10'
                          : 'theme-text-muted hover:text-green-400 hover:bg-green-400/10'">
                        {{ u.activo == 1 ? 'Desactivar' : 'Reactivar' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== REPORTES Y ANALÍTICAS ===== -->
        <template v-if="activeSection === 'reportes'">
          <!-- Tabs -->
          <div class="flex gap-2 mb-6">
            <button @click="reporteTab = 'rotacion'" class="px-4 py-2 rounded-lg text-sm font-medium transition-all" :class="reporteTab === 'rotacion' ? 'bg-accent text-white shadow-lg shadow-accent/20' : 'card-dark theme-text-muted hover:theme-text'">
              <BarChart3 class="w-5 h-5 mr-2 inline-block text-accent" /> Rotación por Bodega
            </button>
            <button @click="reporteTab = 'consumo'" class="px-4 py-2 rounded-lg text-sm font-medium transition-all" :class="reporteTab === 'consumo' ? 'bg-accent text-white shadow-lg shadow-accent/20' : 'card-dark theme-text-muted hover:theme-text'">
              <Package class="w-5 h-5 mr-2 inline-block text-accent" /> Consumo por Proveedor
            </button>
          </div>

          <!-- ROTACIÓN POR BODEGA -->
          <template v-if="reporteTab === 'rotacion'">
            <div class="card-dark rounded-xl p-6 mb-6">
              <h3 class="text-sm font-semibold theme-text mb-4">Filtros</h3>
              <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                  <label class="block text-xs theme-text-muted mb-1.5">Bodega</label>
                  <select v-model="reporteBodegaId" class="w-full theme-bg border theme-border rounded-lg px-3 py-2.5 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                    <option value="">Seleccionar bodega...</option>
                    <option v-for="b in reporteBodegas" :key="b.id" :value="b.id">{{ b.nombre }}</option>
                  </select>
                </div>
                <div class="min-w-[180px]">
                  <label class="block text-xs theme-text-muted mb-1.5">Rango de tiempo</label>
                  <select v-model="reporteRango" class="w-full theme-bg border theme-border rounded-lg px-3 py-2.5 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                    <option value="1_mes">Último mes</option>
                    <option value="3_meses">Últimos 3 meses</option>
                    <option value="historico">Histórico</option>
                  </select>
                </div>
                <button @click="fetchRotacion" :disabled="!reporteBodegaId || loadingRotacion" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-accent text-white hover:bg-accent-hover transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-accent/20">
                  {{ loadingRotacion ? 'Cargando...' : 'Generar reporte' }}
                </button>
              </div>
            </div>

            <div v-if="loadingRotacion" class="card-dark rounded-xl p-12 text-center">
              <div class="inline-block w-8 h-8 border-2 border-accent/30 border-t-accent rounded-full animate-spin mb-3"></div>
              <p class="theme-text-muted text-sm">Generando reporte...</p>
            </div>

            <div v-else-if="rotacionData.length > 0" class="space-y-6">
              <!-- Gráfico -->
              <div class="card-dark rounded-xl p-6">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <h3 class="font-semibold theme-text">Top componentes — {{ rotacionBodegaNombre }}</h3>
                    <p class="text-xs theme-text-muted mt-0.5">{{ { '1_mes': 'Último mes', '3_meses': 'Últimos 3 meses', 'historico': 'Histórico' }[reporteRango] }}</p>
                  </div>
                  <div class="flex gap-1">
                    <button @click="rotacionChartType = 'bar'; renderRotacionChart()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" :class="rotacionChartType === 'bar' ? 'bg-accent/10 text-accent border border-accent/20' : 'theme-text-muted hover:theme-text'">Barras</button>
                    <button @click="rotacionChartType = 'pie'; renderRotacionChart()" class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all" :class="rotacionChartType === 'pie' ? 'bg-accent/10 text-accent border border-accent/20' : 'theme-text-muted hover:theme-text'">Pastel</button>
                  </div>
                </div>
                <div class="relative" style="height: 350px;">
                  <canvas ref="rotacionCanvasRef"></canvas>
                </div>
              </div>

              <!-- Tabla -->
              <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
                <div class="px-6 py-4 border-b theme-border">
                  <h3 class="font-semibold theme-text">Detalle de rotación</h3>
                </div>
                <table class="w-full">
                  <thead class="border-b theme-border">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">#</th>
                      <th class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">Producto</th>
                      <th class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">Categoría</th>
                      <th class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">Especificación</th>
                      <th class="px-6 py-3 text-right text-xs theme-text-muted uppercase tracking-wider font-medium">Unidades vendidas</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y theme-border">
                    <tr v-for="(item, idx) in rotacionData" :key="idx" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                      <td class="px-6 py-3.5 text-sm font-mono theme-text-muted">{{ idx + 1 }}</td>
                      <td class="px-6 py-3.5 text-sm font-medium theme-text">{{ item.producto_nombre }}</td>
                      <td class="px-6 py-3.5 text-sm theme-text-muted">{{ item.categoria }}</td>
                      <td class="px-6 py-3.5 text-sm theme-text-muted">{{ item.especificacion }}</td>
                      <td class="px-6 py-3.5 text-sm font-mono text-accent font-semibold text-right">{{ Number(item.total_salida).toLocaleString() }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-else-if="rotacionFetched && rotacionData.length === 0" class="card-dark rounded-xl p-12 text-center">
              <Mailbox class="w-10 h-10 mx-auto mb-3 theme-text-muted" stroke-width="1.5" />
              <p class="theme-text font-semibold mb-1">Sin movimientos</p>
              <p class="theme-text-muted text-sm">No se encontraron cotizaciones para esta bodega en el rango seleccionado.</p>
            </div>
          </template>

          <!-- CONSUMO POR PROVEEDOR -->
          <template v-if="reporteTab === 'consumo'">
            <div class="card-dark rounded-xl p-6 mb-6">
              <h3 class="text-sm font-semibold theme-text mb-4">Filtros</h3>
              <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                  <label class="block text-xs theme-text-muted mb-1.5">Proveedor</label>
                  <select v-model="reporteProveedorId" class="w-full theme-bg border theme-border rounded-lg px-3 py-2.5 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                    <option value="">Seleccionar proveedor...</option>
                    <option v-for="p in reporteProveedores" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                  </select>
                </div>
                <button @click="fetchConsumo" :disabled="!reporteProveedorId || loadingConsumo" class="px-5 py-2.5 rounded-lg text-sm font-medium bg-accent text-white hover:bg-accent-hover transition-colors disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-accent/20">
                  {{ loadingConsumo ? 'Cargando...' : 'Generar reporte' }}
                </button>
              </div>
            </div>

            <div v-if="loadingConsumo" class="card-dark rounded-xl p-12 text-center">
              <div class="inline-block w-8 h-8 border-2 border-accent/30 border-t-accent rounded-full animate-spin mb-3"></div>
              <p class="theme-text-muted text-sm">Generando reporte...</p>
            </div>

            <div v-else-if="consumoData.length > 0" class="space-y-6">
              <!-- Summary cards -->
              <div class="grid grid-cols-3 gap-4">
                <div class="card-dark rounded-xl p-5">
                  <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Proveedor</p>
                  <p class="text-lg font-bold theme-text">{{ consumoProveedorNombre }}</p>
                </div>
                <div class="card-dark rounded-xl p-5">
                  <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total unidades consumidas</p>
                  <p class="text-3xl font-bold text-accent font-mono">{{ consumoTotalGeneral.toLocaleString() }}</p>
                </div>
                <div class="card-dark rounded-xl p-5">
                  <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Bodegas activas</p>
                  <p class="text-3xl font-bold theme-text font-mono">{{ consumoData.length }}</p>
                </div>
              </div>

              <!-- Chart -->
              <div class="card-dark rounded-xl p-6">
                <h3 class="font-semibold theme-text mb-4">Distribución de consumo por bodega</h3>
                <div class="relative" style="height: 350px;">
                  <canvas ref="consumoCanvasRef"></canvas>
                </div>
              </div>

              <!-- Table -->
              <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
                <div class="px-6 py-4 border-b theme-border">
                  <h3 class="font-semibold theme-text">Detalle por bodega</h3>
                </div>
                <table class="w-full">
                  <thead class="border-b theme-border">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">Bodega</th>
                      <th class="px-6 py-3 text-right text-xs theme-text-muted uppercase tracking-wider font-medium">Unidades consumidas</th>
                      <th class="px-6 py-3 text-right text-xs theme-text-muted uppercase tracking-wider font-medium">Participación</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y theme-border">
                    <tr v-for="item in consumoData" :key="item.bodega_id" class="hover:bg-gray-100 dark:hover:bg-dark-bg/50 transition-colors">
                      <td class="px-6 py-3.5 text-sm font-medium theme-text">{{ item.bodega_nombre }}</td>
                      <td class="px-6 py-3.5 text-sm font-mono text-accent font-semibold text-right">{{ item.total_consumido.toLocaleString() }}</td>
                      <td class="px-6 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <div class="w-20 h-2 rounded-full bg-gray-700 overflow-hidden">
                            <div class="h-full rounded-full bg-accent transition-all duration-500" :style="{ width: item.porcentaje + '%' }"></div>
                          </div>
                          <span class="text-sm font-mono theme-text-muted">{{ item.porcentaje }}%</span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div v-else-if="consumoFetched && consumoData.length === 0" class="card-dark rounded-xl p-12 text-center">
              <Mailbox class="w-10 h-10 mx-auto mb-3 theme-text-muted" stroke-width="1.5" />
              <p class="theme-text font-semibold mb-1">Sin consumo registrado</p>
              <p class="theme-text-muted text-sm">Este proveedor aún no tiene componentes cotizados en sus bodegas.</p>
            </div>
          </template>
        </template>

      </div>
    </main>

    <!-- ===== MODAL EDITAR USUARIO ===== -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar usuario</h2>
            <p class="text-xs theme-text-muted mt-0.5">Modifica los datos o el rol del usuario</p>
          </div>
          <button @click="showEditModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Nombre</label>
              <input v-model="editingUser.nombre" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Apellido</label>
              <input v-model="editingUser.apellido" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input v-model="editingUser.correo" type="email" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
              </div>
              <input v-model="editingUser.telefonoLocal" @input="handleTelefonoInput(editingUser, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-3">Cambiar rol</label>
            <div class="grid grid-cols-2 gap-2">
              <button
                v-for="role in roles"
                :key="role.id"
                @click="editingUser.rol = role.id"
                class="flex flex-col items-center gap-1.5 p-3 rounded-xl border text-xs font-medium transition-all duration-150"
                :class="editingUser.rol === role.id
                  ? 'border-accent bg-accent/5 text-accent'
                  : 'theme-border theme-text-muted hover:border-accent/40 hover:theme-text'"
              >
                <component :is="role.icon" class="text-lg inline-block" />
                {{ role.label }}
              </button>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Perfil de Permisos (Opcional)</label>
            <select v-model="editingUser.perfil_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
              <option :value="null">Sin perfil</option>
              <option v-for="p in perfiles.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>

          <p v-if="editUserError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ editUserError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveEditUser" :disabled="savingEditUser" class="btn-primary flex-1 text-sm">
            {{ savingEditUser ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
  <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
    
    <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl"
      :class="deletingUser?.activo == 1 ? 'bg-red-500/10 border border-red-500/20' : 'bg-green-500/10 border border-green-500/20'">
      <Ban v-if="deletingUser?.activo == 1" class="w-7 h-7" />
      <CheckCircle2 v-else class="w-7 h-7" />
    </div>
    
    <h2 class="text-lg font-bold theme-text mb-2">
      {{ deletingUser?.activo == 1 ? 'Desactivar usuario' : 'Reactivar usuario' }}
    </h2>
    <p class="theme-text-muted text-sm mb-1">
      {{ deletingUser?.activo == 1 ? '¿Desactivar a' : '¿Reactivar a' }}
    </p>
    <p class="theme-text font-semibold mb-2">{{ deletingUser?.nombre }} {{ deletingUser?.apellido }}?</p>
    <p class="text-xs theme-text-muted mb-6 px-4">
      {{ deletingUser?.activo == 1 ? 'El usuario no podrá iniciar sesión mientras esté inactivo.' : 'El usuario podrá volver a iniciar sesión.' }}
    </p>
    
    <div class="flex gap-3">
      <button @click="procesarCambioEstado" :disabled="savingDeleteUser"
        class="flex-1 py-3 rounded-lg text-sm font-medium border transition-colors"
        :class="deletingUser?.activo == 1
          ? 'bg-red-500/10 text-red-400 border-red-500/20 hover:bg-red-500/20'
          : 'bg-green-500/10 text-green-400 border-green-500/20 hover:bg-green-500/20'">
        {{ savingDeleteUser ? 'Procesando...' : (deletingUser?.activo == 1 ? 'Sí, desactivar' : 'Sí, reactivar') }}
      </button>
      
      <button @click="showDeleteModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
    </div>
  </div>
</div>

    <!-- ===== MODAL AGREGAR BODEGA ===== -->
    <div v-if="showBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeBodegaModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Agregar bodega</h2>
            <p class="text-xs theme-text-muted mt-0.5">Crea el acceso para el gestor de la bodega</p>
          </div>
          <button @click="closeBodegaModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Nombre de la bodega</label>
            <input v-model="newBodega.nombre" type="text" placeholder="Ej: TecnoStore Santiago" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Proveedor asignado (opcional)</label>
            <select v-model="newBodega.proveedor_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors appearance-none">
              <option :value="null">Ninguno</option>
              <option v-for="p in proveedores.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div class="border-t theme-border pt-1">
            <p class="text-xs theme-text-muted mb-4">Credenciales de acceso para el gestor</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input v-model="newBodega.correo" type="email" placeholder="gestor@bodega.com" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
              </div>
              <input v-model="newBodega.telefonoLocal" @input="handleTelefonoInput(newBodega, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
            </div>
            <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Contraseña de acceso</label>
            <input v-model="newBodega.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div class="rounded-lg border border-accent/20 bg-accent/5 p-3 flex items-start gap-2">
            <span class="text-accent text-sm mt-0.5"><Info class="w-4 h-4 inline-block" /></span>
            <p class="text-xs theme-text-muted leading-relaxed">El gestor usará estas credenciales para ingresar al sistema y administrar el stock de su bodega.</p>
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

    <!-- ===== MODAL EDITAR BODEGA ===== -->
    <div v-if="showEditBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-lg font-bold theme-text">Editar bodega</h2>
          <button @click="showEditBodegaModal = false" class="theme-text-muted hover:theme-text text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Nombre</label>
            <input v-model="editingBodega.nombre" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
              </div>
              <input v-model="editingBodega.telefonoLocal" @input="handleTelefonoInput(editingBodega, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Proveedor asignado</label>
            <select v-model="editingBodega.proveedor_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors appearance-none">
              <option :value="null">Ninguno</option>
              <option v-for="p in proveedores.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Estado</label>
            <div class="flex items-center gap-3">
              <button @click="editingBodega.activa = 1" class="flex-1 py-2 rounded-lg border text-sm font-medium transition-colors"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'theme-border theme-text-muted'"><Check class="w-4 h-4 inline-block mr-1" /> Activa</button>
              <button @click="editingBodega.activa = 0" class="flex-1 py-2 rounded-lg border text-sm font-medium transition-colors"
                :class="editingBodega.activa == 0 ? 'border-red-500/40 bg-red-500/10 text-red-400' : 'theme-border theme-text-muted'">✕ Inactiva</button>
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

    <!-- ===== MODAL ELIMINAR BODEGA ===== -->
    <div v-if="showDeleteBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold theme-text mb-2">Eliminar bodega</h2>
        <p class="theme-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="theme-text font-semibold mb-2">{{ deletingBodega?.nombre }}?</p>
        <p class="text-xs theme-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
        <p v-if="deleteBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-4 text-center">{{ deleteBodegaError }}</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteBodega" :disabled="savingDeleteBodega" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteBodega ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteBodegaModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL AGREGAR COMPONENTE ===== -->
    <div v-if="showAddCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Nuevo Componente Maestro</h2>
            <p class="text-xs theme-text-muted mt-0.5">Selecciona un producto base del catálogo para activarlo como componente maestro</p>
          </div>
          <button @click="closeAddModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5">
          <!-- Select buscable de producto -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Producto Base <span class="text-red-400">*</span></label>
            <div class="relative">
              <input
                v-model="productoSearch"
                @input="showProductoDropdown = true"
                @focus="showProductoDropdown = true"
                type="text"
                placeholder="Buscar producto base en catálogo..."
                class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                :class="{ 'border-accent': newComp.producto_id }"
                autocomplete="off"
              />
              <div v-if="showProductoDropdown && productosFiltrados.length > 0" class="absolute top-full left-0 right-0 mt-1 theme-card border theme-border rounded-lg shadow-xl z-20 max-h-52 overflow-y-auto">
                <button v-for="prod in productosFiltrados" :key="prod.id" @click="selectProducto(prod)" class="w-full flex items-center justify-between px-4 py-2.5 text-sm hover:theme-bg transition-colors text-left">
                  <span class="theme-text">{{ prod.nombre }}</span>
                  <span class="text-xs theme-text-muted ml-3 flex-shrink-0">{{ prod.categoria }}</span>
                </button>
              </div>
            </div>
            <p v-if="newComp.categoria" class="text-xs text-accent mt-1.5 flex items-center gap-1">
              <span><Check class="w-4 h-4 inline-block mr-1" /></span> Categoría: {{ newComp.categoria }}
            </p>
          </div>

          <p v-if="addCompError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ addCompError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveNewComp" :disabled="savingAddComp" class="btn-primary flex-1 text-sm">
            {{ savingAddComp ? 'Activando...' : 'Activar Componente Maestro' }}
          </button>
          <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditCompModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditCompModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Especificación técnica</label>
            <input v-model="editingComp.especificacion" type="text" class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
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

    <!-- ===== MODAL ELIMINAR COMPONENTE ===== -->
    <div v-if="showDeleteCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteCompModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold theme-text mb-2">Eliminar componente</h2>
        <p class="theme-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="theme-text font-semibold mb-2">{{ deletingComp?.nombre }}?</p>
        <p class="text-xs theme-text-muted mb-6 px-4">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteComp" :disabled="savingDeleteComp" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteComp ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteCompModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR PERFIL ===== -->
    <div v-if="showDeletePerfilModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeletePerfilModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold theme-text mb-2">Eliminar perfil</h2>
        <p class="theme-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="theme-text font-semibold mb-2">{{ deletingPerfil?.nombre }}?</p>
        <div class="flex gap-3 mt-6">
          <button @click="deletePerfil" :disabled="savingDeletePerfil" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeletePerfil ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeletePerfilModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR PERFIL ===== -->
    <div v-if="showPerfilModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closePerfilModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-4xl my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">{{ editingPerfil?.id ? 'Editar Perfil' : 'Crear Perfil' }}</h2>
            <p class="text-sm theme-text-muted mt-1">Configura los permisos de acceso para este perfil.</p>
          </div>
          <button @click="closePerfilModal" class="p-2 hover:theme-bg rounded-lg theme-text-muted hover:theme-text transition-colors">✕</button>
        </div>

        <div class="space-y-6">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Nombre del Perfil</label>
              <input v-model="editingPerfil.nombre" type="text" placeholder="Ej: Vendedor" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Estado</label>
              <select v-model="editingPerfil.activo" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option :value="1">Activo</option>
                <option :value="0">Inactivo</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Descripción (Opcional)</label>
            <input v-model="editingPerfil.descripcion" type="text" placeholder="Breve descripción del perfil" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-4">Permisos Asignados</label>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
              <div v-for="(permisos, module) in availablePermisos" :key="module" class="theme-bg rounded-xl border theme-border p-4">
                <h3 class="text-xs font-semibold theme-text uppercase tracking-wider mb-3">{{ module }}</h3>
                <div class="space-y-2">
                  <label v-for="(label, code) in permisos" :key="code" class="flex items-start gap-2 cursor-pointer group">
                    <div class="relative flex items-center justify-center mt-0.5">
                      <input type="checkbox" :checked="editingPerfil.permisos.includes(code)" @change="togglePermiso(code)" class="appearance-none w-4 h-4 rounded border theme-border theme-bg checked:bg-accent checked:border-accent transition-colors" />
                      <svg v-if="editingPerfil.permisos.includes(code)" class="absolute w-2.5 h-2.5 text-white pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                      </svg>
                    </div>
                    <span class="text-sm theme-text-muted group-hover:theme-text transition-colors">{{ label }}</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <p v-if="perfilError" class="text-sm text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-3">{{ perfilError }}</p>
        </div>

        <div class="mt-8 flex gap-3">
          <button @click="savePerfil" :disabled="savingPerfil" class="flex-1 py-3 rounded-lg text-sm font-medium bg-accent text-white hover:bg-accent-hover transition-colors shadow-lg shadow-accent/20">
            {{ savingPerfil ? 'Guardando...' : 'Guardar perfil' }}
          </button>
          <button @click="closePerfilModal" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { MapPin, BarChart3, Package, Mailbox, Ban, CheckCircle2, UserPlus, Check, Trash2, Pencil, Sun, Moon, Info, Wrench, FileText, Shield, Briefcase, Gamepad2, Palette, BookOpen, Store, Users, Lock, User, Settings } from 'lucide-vue-next';



import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { ref, markRaw, computed, onMounted, nextTick, watch } from 'vue'
import { Chart, BarController, PieController, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js'
Chart.register(BarController, PieController, ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend)
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

/**
 * Formatea un número telefónico al formato local de Colombia (ej. 300 123 4567)
 * Esto mejora la legibilidad en la interfaz de usuario.
 */
function formatTelefonoLocal(val) {
  if (!val) return ''
  val = String(val).replace(/[^\d]/g, '')
  if (val.length > 10) val = val.slice(0, 10)
  if (val.length > 6) {
    return val.slice(0, 3) + ' ' + val.slice(3, 6) + ' ' + val.slice(6)
  } else if (val.length > 3) {
    return val.slice(0, 3) + ' ' + val.slice(3)
  }
  return val
}

function handleTelefonoInput(obj, key) {
  obj[key] = formatTelefonoLocal(obj[key])
}

import { API } from '@/config/api'
const { getToken, logout, user, hasPermission } = useAuth()
const router = useRouter()
const toast = useToast()

function handleLogout() {
  logout()
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' })
}

function perfilLabel(p) { return ({ office: 'Oficina', gaming: 'Gaming', design: 'Diseño', study: 'Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('bodegas')

const sectionPermissions = {
  bodegas: 'bodegas.ver',
  componentes: 'componentes.ver',
  cotizaciones: 'cotizaciones.ver',
  'crear-usuario': 'usuarios.crear',
  'gestionar-usuarios': 'usuarios.ver',
  perfiles: 'perfiles.ver',
  reportes: 'reportes.ver'
}



// ── Estilos ───────────────────────────────────────────────
const roles = [
  { id: 'admin',   icon: markRaw(Shield), label: 'Admin',   description: 'Acceso total al panel administrativo.' },
  { id: 'cliente', icon: markRaw(User), label: 'Cliente',  description: 'Solo puede cotizar y ver su historial.' },
]

const roleStyles = {
  admin:   { label: 'Admin',   badge: 'bg-purple-500/10 text-purple-400 border border-purple-500/20', avatar: 'bg-purple-500/20 text-purple-400' },
  cliente: { label: 'Cliente', badge: 'bg-blue-500/10 text-blue-400 border border-blue-500/20',       avatar: 'bg-blue-500/20 text-blue-400'    },
  bodega:  { label: 'Bodega',  badge: 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20', avatar: 'bg-yellow-500/20 text-yellow-400' },
}

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

// ── Bodegas ───────────────────────────────────────────────
const bodegas = ref([])
const loadingBodegas = ref(false)
const filterBodega = ref('')
const showBodegaModal = ref(false)
const showDeleteBodegaModal = ref(false)
const deletingBodega = ref(null)
const savingBodega = ref(false)
const savingDeleteBodega = ref(false)
const deleteBodegaError  = ref('')
const bodegaError = ref('')
const newBodega = ref({ nombre: '', correo: '', telefono: '', password: '', proveedor_id: null })
const showEditBodegaModal = ref(false)
const editingBodega = ref({})
const editBodegaError = ref('')
const savingEditBodega = ref(false)
const proveedores = ref([])

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchProveedores() {
  try {
    const res = await fetch(`${API}/proveedores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      const list = data.proveedores?.data || data.proveedores
      proveedores.value = Array.isArray(list) ? list : []
    }
  } catch(e) { console.error(e) }
}

/**
 * Computed property que filtra la lista de bodegas en tiempo real
 * basado en lo que el usuario escribe en el campo de búsqueda (por nombre o correo).
 */
/**
 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.
 */
const filteredBodegas = computed(() => {
  if (!filterBodega.value.trim()) return bodegas.value
  const q = filterBodega.value.toLowerCase()
  return bodegas.value.filter(b => b.nombre.toLowerCase().includes(q) || b.correo.toLowerCase().includes(q))
})

/**
 * Obtiene la lista de bodegas desde el backend a través de la API.
 * Maneja el estado de carga (loadingBodegas) para mostrar feedback visual al usuario.
 */
/**
 * Obtiene datos desde el backend mediante API.
 * Mantiene sincronizada la vista con la base de datos.
 */
async function fetchBodegas() {
  loadingBodegas.value = true
  try {
    const res = await fetch(`${API}/bodegas`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) bodegas.value = data.bodegas
  } catch(e) { console.error(e) } finally { loadingBodegas.value = false }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openBodegaModal() {
  newBodega.value = { nombre: '', correo: '', telefono: '', password: '', proveedor_id: null }
  bodegaError.value = ''
  showBodegaModal.value = true
}

/**

 * Cierra el modal activo y limpia los errores.

 */

function closeBodegaModal() {
  showBodegaModal.value = false
  bodegaError.value = ''
}

async function procesarCambioEstado() {
  savingDeleteUser.value = true
  try {
    const u = deletingUser.value
    const nuevoEstado = u.activo == 1 ? 0 : 1
    const res = await fetch(`${API}/usuarios`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: u.id,
        nombre: u.nombre,
        apellido: u.apellido,
        correo: u.correo,
        telefono: u.telefono,
        rol: u.rol,
        activo: nuevoEstado
      })
    })
    if (!res.ok) {
      const data = await res.json()
      console.error('Error al cambiar estado:', data.message)
    }
    await fetchUsuarios()
    showDeleteModal.value = false
  } catch (e) {
    console.error('Error de conexión:', e)
  } finally {
    savingDeleteUser.value = false
  }
}

/**
 * Valida los datos del formulario y envía la petición POST para crear una nueva bodega.
 * Incluye validación específica para asegurar que el número de teléfono sea válido en Colombia.
 */
/**
 * Valida y envía los datos del formulario al backend (POST/PUT).
 * Maneja la lógica de guardado y muestra feedback al usuario.
 */
async function saveNewBodega() {
  bodegaError.value = ''
  if (!newBodega.value.nombre || !newBodega.value.correo || !newBodega.value.password)
    return bodegaError.value = 'Nombre, correo y contraseña son requeridos'
  if (newBodega.value.telefonoLocal) {
    const digitos = newBodega.value.telefonoLocal.replace(/\s/g, '')
    if (digitos.length !== 10 || !digitos.startsWith('3')) {
      return bodegaError.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
    }
    newBodega.value.telefono = '+57' + digitos
  } else {
    newBodega.value.telefono = null
  }
  savingBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newBodega.value)
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.error ?? 'Error al crear bodega')
      return bodegaError.value = data.error ?? 'Error al crear'
    }
    await fetchBodegas()
    closeBodegaModal()
    toast.success('Bodega agregada exitosamente')
  } catch(e) {
    toast.error('Error de conexión')
    bodegaError.value = 'Error de conexión'
  } finally { savingBodega.value = false }
}

function openEditBodega(b) {
  editingBodega.value = { ...b }
  if (editingBodega.value.telefono) {
    let t = editingBodega.value.telefono.replace('+57', '').replace(/\D/g, '');
    editingBodega.value.telefonoLocal = formatTelefonoLocal(t);
  } else {
    editingBodega.value.telefonoLocal = '';
  }
  editBodegaError.value = ''
  showEditBodegaModal.value = true
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditBodega() {
  editBodegaError.value = ''
  if (!editingBodega.value.nombre) return editBodegaError.value = 'El nombre es requerido'
  if (editingBodega.value.telefonoLocal) {
    const digitos = editingBodega.value.telefonoLocal.replace(/\s/g, '')
    if (digitos.length !== 10 || !digitos.startsWith('3')) {
      return editBodegaError.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
    }
    editingBodega.value.telefono = '+57' + digitos
  } else {
    editingBodega.value.telefono = null
  }
  savingEditBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: editingBodega.value.id, nombre: editingBodega.value.nombre, telefono: editingBodega.value.telefono, activa: editingBodega.value.activa, proveedor_id: editingBodega.value.proveedor_id })
    })
    const data = await res.json()
    if (!res.ok) return editBodegaError.value = data.message ?? 'Error al guardar'
    await fetchBodegas()
    showEditBodegaModal.value = false
  } catch(e) { editBodegaError.value = 'Error de conexión' } finally { savingEditBodega.value = false }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleBodega(b) {
  const activa = b.activa == 1 ? 0 : 1
  await fetch(`${API}/bodegas`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
    body: JSON.stringify({ id: b.id, nombre: b.nombre, telefono: b.telefono, activa })
  })
  await fetchBodegas()
}

function openDeleteBodega(b) {
  deletingBodega.value = b
  deleteBodegaError.value = ''
  showDeleteBodegaModal.value = true
}

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

async function confirmDeleteBodega() {
  deleteBodegaError.value = ''
  savingDeleteBodega.value = true
  try {
    const res = await fetch(`${API}/bodegas?id=${deletingBodega.value.id}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
        deleteBodegaError.value = data.message ?? 'Error al eliminar'
        return
    }
    await fetchBodegas()
    showDeleteBodegaModal.value = false
  } catch(e) { 
    console.error(e)
    deleteBodegaError.value = 'Error de conexión'
  } finally { 
    savingDeleteBodega.value = false 
  }
}

// ── Componentes ───────────────────────────────────────────
const componentes = ref([])
const loadingComponentes = ref(false)
const filterComponente = ref('')
const showEditCompModal = ref(false)
const showDeleteCompModal = ref(false)
const deletingComp = ref(null)
const savingDeleteComp = ref(false)
const editingComp = ref({})
const editCompError = ref('')
const savingEditComp = ref(false)

// Variables para Add Component
const showAddCompModal = ref(false)
const newComp = ref({ producto_id: '', nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media' })
const addCompError = ref('')
const savingAddComp = ref(false)
const productoSearch = ref('')
const showProductoDropdown = ref(false)
const productosCatalogo = ref([])

/**

 * Propiedad computada que filtra el catálogo de productos disponible en tiempo real.

 */

const productosFiltrados = computed(() => {
  if (!productoSearch.value) return productosCatalogo.value
  const q = productoSearch.value.toLowerCase()
  return productosCatalogo.value.filter(p => p.nombre.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchProductosCatalogo() {
  try {
    const res = await fetch(`${API}/productos-catalogo/`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) productosCatalogo.value = data.productos || data.componentes || []
  } catch(e) { console.error(e) }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openAddModal() {
  newComp.value = { producto_id: '', nombre: '', categoria: '' }
  addCompError.value = ''
  productoSearch.value = ''
  showProductoDropdown.value = false
  if (productosCatalogo.value.length === 0) fetchProductosCatalogo()
  showAddCompModal.value = true
}

/**

 * Cierra el modal activo y limpia los errores.

 */

function closeAddModal() {
  showAddCompModal.value = false
  addCompError.value = ''
}

function selectProducto(prod) {
  newComp.value.producto_id = prod.id
  newComp.value.nombre = prod.nombre
  newComp.value.categoria = prod.categoria
  productoSearch.value = prod.nombre
  showProductoDropdown.value = false
}

/**
 * Guarda y activa un componente maestro asociado a un producto del catálogo base.
 */
async function saveNewComp() {
  addCompError.value = ''
  const c = newComp.value
  if (!c.producto_id) {
    return addCompError.value = 'Debes seleccionar un producto base del catálogo'
  }
  savingAddComp.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        producto_id: c.producto_id
      })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al activar')
      return addCompError.value = data.message ?? 'Error al activar'
    }
    await fetchComponentes()
    closeAddModal()
    toast.success('Componente maestro activado exitosamente')
  } catch(e) {
    addCompError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally {
    savingAddComp.value = false
  }
}

/**
 * Propiedad computada que filtra dinámicamente los componentes por nombre o categoría.
 */
const filteredComponentes = computed(() => {
  const list = componentes.value.filter(c => !c.bodega_id)
  if (!filterComponente.value.trim()) return list
  const q = filterComponente.value.toLowerCase()
  return list.filter(c => c.nombre?.toLowerCase().includes(q) || c.categoria?.toLowerCase().includes(q) || c.especificacion?.toLowerCase().includes(q))
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/componentes/admin?solo_maestros=true`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.componentes
  } catch(e) { console.error(e) } finally { loadingComponentes.value = false }
}

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editCompError.value = ''
  showEditCompModal.value = true
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditComp() {
  editCompError.value = ''
  savingEditComp.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id:             editingComp.value.id,
        especificacion: editingComp.value.especificacion,
      })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al guardar')
      return editCompError.value = data.message ?? 'Error al guardar'
    }
    await fetchComponentes()
    showEditCompModal.value = false
    toast.success('Componente actualizado exitosamente')
  } catch (e) {
    toast.error('Error de conexión')
    editCompError.value = 'Error de conexión'
  } finally {
    savingEditComp.value = false
  }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleComponente(c) {
  const activo = c.activo == 1 ? 0 : 1
  try {
    await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: c.id, activo })
    })
    await fetchComponentes()
    toast.success(activo === 1 ? 'Componente activado' : 'Componente desactivado')
  } catch (e) {
    toast.error('Error al cambiar estado del componente')
  }
}

function openDeleteComp(c) {
  deletingComp.value = c
  showDeleteCompModal.value = true
}

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

async function confirmDeleteComp() {
  savingDeleteComp.value = true
  try {
    await fetch(`${API}/componentes?id=${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    await fetchComponentes()
    showDeleteCompModal.value = false
    toast.success('Componente eliminado exitosamente')
  } catch(e) {
    console.error(e)
    toast.error('Error al eliminar componente')
  } finally { savingDeleteComp.value = false }
}

// ── Usuarios ──────────────────────────────────────────────
const usuarios = ref([])
const loadingUsuarios = ref(false)
const filterUsuario = ref('')
const showEditModal = ref(false)
const showDeleteModal = ref(false)
const editingUser = ref({})
const deletingUser = ref(null)
const createUserError = ref('')
const createUserSuccess = ref('')
const savingUser = ref(false)
const editUserError = ref('')
const savingEditUser = ref(false)
const savingDeleteUser = ref(false)
const newUser = ref({ rol: 'cliente', nombre: '', apellido: '', correo: '', telefono: '', password: '' })

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredUsuarios = computed(() => {
  if (!filterUsuario.value.trim()) return usuarios.value
  const q = filterUsuario.value.toLowerCase()
  return usuarios.value.filter(u =>
    u.nombre.toLowerCase().includes(q) ||
    u.correo.toLowerCase().includes(q) ||
    u.apellido?.toLowerCase().includes(q)
  )
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

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
  createUserError.value = ''
  createUserSuccess.value = ''
}

/**
 * Procesa la creación de un nuevo usuario desde el panel de administración.
 * Realiza validación de campos obligatorios y formato de teléfono antes de comunicarse con la API.
 */
/**
 * Valida y envía los datos del formulario al backend (POST/PUT).
 * Maneja la lógica de guardado y muestra feedback al usuario.
 */
async function saveNewUser() {
  createUserError.value = ''
  createUserSuccess.value = ''
  if (!newUser.value.nombre || !newUser.value.correo || !newUser.value.password)
    return createUserError.value = 'Nombre, correo y contraseña son requeridos'
  if (newUser.value.telefonoLocal) {
    const digitos = newUser.value.telefonoLocal.replace(/\s/g, '')
    if (digitos.length !== 10 || !digitos.startsWith('3')) {
      return createUserError.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
    }
    newUser.value.telefono = '+57' + digitos
  } else {
    newUser.value.telefono = null
  }
  savingUser.value = true
  try {
    const res = await fetch(`${API}/usuarios`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newUser.value)
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.error ?? 'Error al crear usuario')
      return createUserError.value = data.error ?? 'Error al crear'
    }
    createUserSuccess.value = 'Usuario creado correctamente'
    toast.success('Usuario creado correctamente')
    resetNewUser()
    await fetchUsuarios()
  } catch(e) {
    toast.error('Error de conexión')
    createUserError.value = 'Error de conexión'
  } finally { savingUser.value = false }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openEditModal(u) {
  editingUser.value = { ...u }
  if (editingUser.value.telefono) {
    let t = editingUser.value.telefono.replace('+57', '').replace(/\D/g, '');
    editingUser.value.telefonoLocal = formatTelefonoLocal(t);
  } else {
    editingUser.value.telefonoLocal = '';
  }
  editUserError.value = ''
  showEditModal.value = true
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditUser() {
  editUserError.value = ''
  if (editingUser.value.telefonoLocal) {
    const digitos = editingUser.value.telefonoLocal.replace(/\s/g, '')
    if (digitos.length !== 10 || !digitos.startsWith('3')) {
      return editUserError.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
    }
    editingUser.value.telefono = '+57' + digitos
  } else {
    editingUser.value.telefono = null
  }
  savingEditUser.value = true
  try {
    const res = await fetch(`${API}/usuarios`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(editingUser.value)
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.error ?? 'Error al guardar usuario')
      return editUserError.value = data.error ?? 'Error al guardar'
    }
    await fetchUsuarios()
    showEditModal.value = false
    toast.success('Usuario actualizado exitosamente')
  } catch(e) {
    toast.error('Error de conexión')
    editUserError.value = 'Error de conexión'
  } finally { savingEditUser.value = false }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openDeleteModal(u) {
  deletingUser.value = u
  showDeleteModal.value = true
}

/**
 * Alterna el estado activo/inactivo de un usuario (Soft Delete).
 * En lugar de borrar el registro de la base de datos, se deshabilita su acceso.
 */
/**
 * Confirma y procesa la eliminación de un registro mediante la API.
 */
async function confirmDeleteUser() {
  savingDeleteUser.value = true
  try {
    const nuevoEstado = deletingUser.value.estado == 1 ? 0 : 1
    await fetch(`${API}/usuarios?id=${deletingUser.value.id}&estado=${nuevoEstado}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    await fetchUsuarios()
    showDeleteModal.value = false
    toast.success(activoNuevo === 1 ? 'Usuario reactivado' : 'Usuario desactivado')
  } catch(e) {
    console.error(e)
    toast.error('Error al cambiar el estado del usuario')
  } finally { savingDeleteUser.value = false }
}


// ── Perfiles y Permisos ───────────────────────────────────
const perfiles = ref([])
const loadingPerfiles = ref(false)
const showPerfilModal = ref(false)
const showDeletePerfilModal = ref(false)
const editingPerfil = ref({ id: null, nombre: '', descripcion: '', activo: 1, permisos: [] })
const deletingPerfil = ref(null)
const savingPerfil = ref(false)
const savingDeletePerfil = ref(false)
const perfilError = ref('')
const availablePermisos = ref({})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchPerfiles() {
  loadingPerfiles.value = true
  try {
    const res = await fetch(`${API}/perfiles`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) perfiles.value = data.perfiles || data
  } catch(e) { console.error(e) } finally { loadingPerfiles.value = false }
}

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchPermisosDisponibles() {
  try {
    const res = await fetch(`${API}/perfiles/permisos`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) availablePermisos.value = data.permisos || data
  } catch(e) { console.error(e) }
}

function openEditPerfil(p = null) {
  if (p) {
    editingPerfil.value = { ...p, permisos: [...(p.permisos || [])] }
  } else {
    editingPerfil.value = { id: null, nombre: '', descripcion: '', permisos: [], activo: 1 }
  }
  perfilError.value = ''
  showPerfilModal.value = true
}

/**

 * Cierra el modal activo y limpia los errores.

 */

function closePerfilModal() {
  showPerfilModal.value = false
  perfilError.value = ''
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

function togglePermiso(code) {
  const idx = editingPerfil.value.permisos.indexOf(code)
  if (idx === -1) editingPerfil.value.permisos.push(code)
  else editingPerfil.value.permisos.splice(idx, 1)
}

/**
 * Guarda o actualiza un perfil de permisos. 
 * Determina dinámicamente si debe hacer un POST (nuevo) o PUT (actualización)
 * dependiendo de si el perfil ya tiene un ID asignado.
 */
/**
 * Valida y envía los datos del formulario al backend (POST/PUT).
 * Maneja la lógica de guardado y muestra feedback al usuario.
 */
async function savePerfil() {
  perfilError.value = ''
  if (!editingPerfil.value.nombre) {
    return perfilError.value = 'El nombre es requerido'
  }
  
  savingPerfil.value = true
  const method = editingPerfil.value.id ? 'PUT' : 'POST'
  const url = editingPerfil.value.id ? `${API}/perfiles/${editingPerfil.value.id}` : `${API}/perfiles`
  try {
    const res = await fetch(url, {
      method: method,
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(editingPerfil.value)
    })
    if (res.ok) {
      toast.success(editingPerfil.value.id ? 'Perfil actualizado' : 'Perfil creado')
      closePerfilModal()
      fetchPerfiles()
    } else {
      const data = await res.json()
      perfilError.value = data.message || 'Error al guardar el perfil'
    }
  } catch (error) {
    perfilError.value = 'Error de red al guardar el perfil'
  } finally {
    savingPerfil.value = false
  }
}

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

function confirmDeletePerfilAction(p) {
  deletingPerfil.value = p
  showDeletePerfilModal.value = true
}

async function deletePerfil() {
  savingDeletePerfil.value = true
  try {
    const res = await fetch(`${API}/perfiles/${deletingPerfil.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    if (res.ok) {
      toast.success('Perfil eliminado')
      showDeletePerfilModal.value = false
      fetchPerfiles()
    }
  } catch(e) {
    console.error(e)
  } finally {
    savingDeletePerfil.value = false
  }
}

// ── Cotizaciones ──────────────────────────────────────────
const cotizaciones = ref([])
const loadingCotizaciones = ref(false)

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchCotizaciones() {
  loadingCotizaciones.value = true
  try {
    const res = await fetch(`${API}/cotizaciones`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) cotizaciones.value = data.cotizaciones
  } catch(e) { console.error(e) } finally { loadingCotizaciones.value = false }
}


// ── Analíticas ────────────────────────────────────────────
const reporteTab = ref('rotacion')
const reporteBodegas = ref([])
const reporteProveedores = ref([])

// Rotación
const reporteBodegaId = ref('')
const reporteRango = ref('historico')
const loadingRotacion = ref(false)
const rotacionData = ref([])
const rotacionBodegaNombre = ref('')
const rotacionFetched = ref(false)
const rotacionChartType = ref('bar')
const rotacionCanvasRef = ref(null)
let rotacionChartInstance = null

// Consumo
const reporteProveedorId = ref('')
const loadingConsumo = ref(false)
const consumoData = ref([])
const consumoProveedorNombre = ref('')
const consumoTotalGeneral = ref(0)
const consumoFetched = ref(false)
const consumoCanvasRef = ref(null)
let consumoChartInstance = null

const chartColors = [
  'rgba(99, 102, 241, 0.8)',   // indigo
  'rgba(16, 185, 129, 0.8)',   // emerald
  'rgba(245, 158, 11, 0.8)',   // amber
  'rgba(239, 68, 68, 0.8)',    // red
  'rgba(139, 92, 246, 0.8)',   // violet
  'rgba(6, 182, 212, 0.8)',    // cyan
  'rgba(236, 72, 153, 0.8)',   // pink
  'rgba(34, 197, 94, 0.8)',    // green
  'rgba(251, 146, 60, 0.8)',   // orange
  'rgba(168, 85, 247, 0.8)',   // purple
]

async function fetchSelectores() {
  try {
    const res = await fetch(`${API}/analiticas/selectores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      reporteBodegas.value = data.bodegas || []
      reporteProveedores.value = data.proveedores || []
    }
  } catch (e) { console.error(e) }
}

async function fetchRotacion() {
  if (!reporteBodegaId.value) return
  loadingRotacion.value = true
  rotacionFetched.value = false
  try {
    const res = await fetch(`${API}/analiticas/rotacion-bodega?bodega_id=${reporteBodegaId.value}&rango_fecha=${reporteRango.value}&limit=10`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      rotacionData.value = data.data || []
      rotacionBodegaNombre.value = data.bodega_nombre || ''
      rotacionFetched.value = true
    }
  } catch (e) { 
    console.error(e) 
  } finally { 
    loadingRotacion.value = false 
    await nextTick()
    renderRotacionChart()
  }
}

function renderRotacionChart() {
  if (!rotacionCanvasRef.value || rotacionData.value.length === 0) return
  if (rotacionChartInstance) rotacionChartInstance.destroy()

  const labels = rotacionData.value.map(d => d.producto_nombre.length > 20 ? d.producto_nombre.slice(0, 20) + '…' : d.producto_nombre)
  const values = rotacionData.value.map(d => Number(d.total_salida))
  const colors = rotacionData.value.map((_, i) => chartColors[i % chartColors.length])
  const textColor = isDark.value ? '#94a3b8' : '#64748b'
  const gridColor = isDark.value ? 'rgba(148, 163, 184, 0.08)' : 'rgba(100, 116, 139, 0.1)'

  const config = rotacionChartType.value === 'bar'
    ? {
        type: 'bar',
        data: { labels, datasets: [{ label: 'Unidades vendidas', data: values, backgroundColor: colors, borderRadius: 6, borderSkipped: false, maxBarThickness: 48 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: isDark.value ? '#1e293b' : '#fff', titleColor: isDark.value ? '#e2e8f0' : '#1e293b', bodyColor: isDark.value ? '#94a3b8' : '#64748b', borderColor: isDark.value ? '#334155' : '#e2e8f0', borderWidth: 1, padding: 12, cornerRadius: 8 } }, scales: { x: { ticks: { color: textColor, font: { size: 11 } }, grid: { display: false } }, y: { beginAtZero: true, ticks: { color: textColor, font: { size: 11 } }, grid: { color: gridColor } } }, animation: { duration: 700, easing: 'easeOutQuart' } }
      }
    : {
        type: 'pie',
        data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: textColor, font: { size: 12 }, padding: 12 } }, tooltip: { backgroundColor: isDark.value ? '#1e293b' : '#fff', titleColor: isDark.value ? '#e2e8f0' : '#1e293b', bodyColor: isDark.value ? '#94a3b8' : '#64748b', borderColor: isDark.value ? '#334155' : '#e2e8f0', borderWidth: 1, padding: 12, cornerRadius: 8 } }, animation: { duration: 700, easing: 'easeOutQuart' } }
      }

  rotacionChartInstance = new Chart(rotacionCanvasRef.value, config)
}

async function fetchConsumo() {
  if (!reporteProveedorId.value) return
  loadingConsumo.value = true
  consumoFetched.value = false
  try {
    const res = await fetch(`${API}/analiticas/consumo-proveedor?proveedor_id=${reporteProveedorId.value}`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      consumoData.value = data.data || []
      consumoProveedorNombre.value = data.proveedor_nombre || ''
      consumoTotalGeneral.value = data.total_general || 0
      consumoFetched.value = true
    }
  } catch (e) { 
    console.error(e) 
  } finally { 
    loadingConsumo.value = false 
    await nextTick()
    renderConsumoChart()
  }
}

function renderConsumoChart() {
  if (!consumoCanvasRef.value || consumoData.value.length === 0) return
  if (consumoChartInstance) consumoChartInstance.destroy()

  const labels = consumoData.value.map(d => d.bodega_nombre)
  const values = consumoData.value.map(d => d.total_consumido)
  const colors = consumoData.value.map((_, i) => chartColors[i % chartColors.length])
  const textColor = isDark.value ? '#94a3b8' : '#64748b'

  consumoChartInstance = new Chart(consumoCanvasRef.value, {
    type: 'pie',
    data: { labels, datasets: [{ data: values, backgroundColor: colors, borderWidth: 0 }] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'right', labels: { color: textColor, font: { size: 12 }, padding: 12, generateLabels(chart) {
          const ds = chart.data.datasets[0]
          return chart.data.labels.map((label, i) => ({
            text: `${label} (${consumoData.value[i]?.porcentaje ?? 0}%)`,
            fillStyle: ds.backgroundColor[i],
            hidden: false,
            index: i
          }))
        } } },
        tooltip: { backgroundColor: isDark.value ? '#1e293b' : '#fff', titleColor: isDark.value ? '#e2e8f0' : '#1e293b', bodyColor: isDark.value ? '#94a3b8' : '#64748b', borderColor: isDark.value ? '#334155' : '#e2e8f0', borderWidth: 1, padding: 12, cornerRadius: 8 }
      },
      animation: { duration: 700, easing: 'easeOutQuart' }
    }
  })
}

/**
 * Configuración dinámica de las secciones del menú lateral.
 * Filtra las secciones visibles según el perfil de permisos asignado al usuario.
 */
const sections = computed(() => {
  const all = [
    { id: 'bodegas',            icon: markRaw(Store), label: 'Bodegas',            description: `${bodegas.value.length} bodegas registradas`,    cta: '+ Agregar bodega', count: bodegas.value.length    },
    { id: 'componentes',        icon: markRaw(Wrench), label: 'Componentes',        description: `${componentes.value.filter(c => !c.bodega_id).length} componentes maestros`, cta: '+ Nuevo Componente Maestro',               count: componentes.value.filter(c => !c.bodega_id).length },
    { id: 'cotizaciones',       icon: markRaw(FileText), label: 'Cotizaciones',       description: 'Historial de cotizaciones',                       cta: null,               count: null },
    { id: 'crear-usuario',      icon: markRaw(UserPlus), label: 'Crear usuario',      description: 'Registrar nuevo usuario',                        cta: null,               count: null },
    { id: 'gestionar-usuarios', icon: markRaw(Users), label: 'Gestionar usuarios', description: `${usuarios.value.length} usuarios registrados`,   cta: '+ Crear usuario',  count: usuarios.value.length   },
    { id: 'perfiles', icon: markRaw(Lock), label: 'Perfiles y Permisos', description: `${perfiles.value.length} perfiles`, cta: '+ Crear perfil', count: perfiles.value.length },
    { id: 'reportes', icon: markRaw(BarChart3), label: 'Reportes', description: 'Analíticas y estadísticas', cta: null, count: null },
  ]
  return all.filter(s => {
    const code = sectionPermissions[s.id]
    return !code || hasPermission(code)
  })
})

watch(sections, (newSections) => {
  if (newSections.length > 0 && !newSections.some(s => s.id === activeSection.value)) {
    activeSection.value = newSections[0].id
  }
}, { immediate: true })

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value) || sections.value[0] || {})

// ── Lifecycle ─────────────────────────────────────────────
onMounted(async () => {
  await Promise.allSettled([
    hasPermission('bodegas.ver') ? fetchBodegas() : Promise.resolve(),
    hasPermission('usuarios.ver') ? fetchUsuarios() : Promise.resolve(),
    hasPermission('componentes.ver') ? fetchComponentes() : Promise.resolve(),
    hasPermission('cotizaciones.ver') ? fetchCotizaciones() : Promise.resolve(),
    hasPermission('proveedores.ver') ? fetchProveedores() : Promise.resolve(),
    hasPermission('perfiles.ver') ? fetchPerfiles() : Promise.resolve(),
    fetchSelectores()
  ])
})
</script>
