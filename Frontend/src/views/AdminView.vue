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
          <h1 class="font-semibold theme-text">{{ currentSection.label }}</h1>
          <p class="text-xs theme-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button
          v-if="currentSection.cta"
          @click="activeSection === 'bodegas' ? openBodegaModal() : activeSection === 'gestionar-usuarios' ? activeSection = 'crear-usuario' : null"
          class="btn-primary text-sm"
        >
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

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
              <thead class="border-b border-dark-border">
                <tr><th v-for="h in ['Nombre','Teléfono','Correo','Proveedor','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs text-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center text-text-muted text-sm">Sin bodegas registradas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="hover:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium text-text-primary">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm text-text-muted">{{ b.telefono || '—' }}</td>
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
              <div class="flex items-center justify-between">
                <h2 class="font-semibold theme-text">Listado de componentes</h2>
                <div class="flex items-center gap-3">
                  <input v-model="filterComponente" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
                  <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2">
                    <span><Settings class="w-4 h-4 inline-block mr-1" /></span> Filtros
                  </button>
                </div>
              </div>
              
              <!-- Advanced Filters Panel -->
              <div v-if="showAdvancedFilters" class="p-4 theme-bg border theme-border rounded-xl grid grid-cols-2 md:grid-cols-5 gap-4 animate-fade-in mt-4">
                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Gama</label>
                  <select v-model="filterGama" class="w-full theme-card border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                    <option value="">Todas</option>
                    <option value="alta">Alta</option>
                    <option value="media">Media</option>
                    <option value="baja">Baja</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Enfoque</label>
                  <select v-model="filterEnfoque" class="w-full theme-card border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                    <option value="">Todos</option>
                    <option value="gaming">Gaming</option>
                    <option value="diseño">Diseño</option>
                    <option value="estudio">Estudio</option>
                    <option value="oficina">Oficina</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Núcleos</label>
                  <input v-model="filterNucleos" type="number" min="1" placeholder="Ej: 6" class="w-full theme-card border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
                </div>
                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Hilos</label>
                  <input v-model="filterHilos" type="number" min="1" placeholder="Ej: 12" class="w-full theme-card border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
                </div>
                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Frec. mínima (GHz)</label>
                  <input v-model="filterFrecuenciaMin" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full theme-card border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
                </div>
              </div>
            </div>
            <div v-if="loadingComponentes" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando componentes...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Componente','Categoría','Especificación','Gama','Precio','Bodega','Stock','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="8" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ c.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4 text-sm theme-text-muted max-w-48 truncate">{{ c.especificacion }}</td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[c.gama]">{{ c.gama }}</span></td>
                  <td class="px-6 py-4 text-sm text-accent font-mono font-medium">${{ Number(c.precio).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ c.bodega_nombre }}</td>
                  <td class="px-6 py-4 text-sm font-mono" :class="c.stock <= 3 ? 'text-yellow-400' : 'theme-text'">{{ c.stock }}</td>
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
                    🇨🇴 +57
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
              <tbody class="divide-y divide-dark-border">
                <tr v-if="perfiles.length === 0"><td colspan="5" class="px-6 py-12 text-center theme-text-muted text-sm">Sin perfiles</td></tr>
                <tr v-for="p in perfiles" :key="p.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
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
              <p class="text-text-muted text-xs uppercase tracking-wider mb-2">Inactivos</p>
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
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredUsuarios.length === 0"><td colspan="7" class="px-6 py-12 text-center text-text-muted text-sm">Sin usuarios</td></tr>
                <tr v-for="u in filteredUsuarios" :key="u.id" class="hover:bg-dark-bg/50 transition-colors" :class="u.activo == 0 ? 'opacity-50' : ''">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" :class="roleStyles[u.rol?.toLowerCase()]?.avatar ?? 'bg-dark-card text-text-muted'">
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
                      <button @click="openEditModal(u)" class="text-xs text-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">Editar</button>
                      
                      <button @click="openDeleteModal(u)" class="text-xs px-2 py-1 rounded transition-colors"
                        :class="u.activo == 1
                          ? 'text-text-muted hover:text-red-400 hover:bg-red-400/10'
                          : 'text-text-muted hover:text-green-400 hover:bg-green-400/10'">
                        {{ u.activo == 1 ? 'Desactivar' : 'Reactivar' }}
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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
                🇨🇴 +57
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
      {{ deletingUser?.activo == 1 ? '🚫' : '✅' }}
    </div>
    
    <h2 class="text-lg font-bold text-text-primary mb-2">
      {{ deletingUser?.activo == 1 ? 'Desactivar usuario' : 'Reactivar usuario' }}
    </h2>
    <p class="text-text-muted text-sm mb-1">
      {{ deletingUser?.activo == 1 ? '¿Desactivar a' : '¿Reactivar a' }}
    </p>
    <p class="text-text-primary font-semibold mb-2">{{ deletingUser?.nombre }} {{ deletingUser?.apellido }}?</p>
    <p class="text-xs text-text-muted mb-6 px-4">
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
            <label class="block text-sm font-medium text-text-primary mb-2">Proveedor asignado (opcional)</label>
            <select v-model="newBodega.proveedor_id" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors appearance-none">
              <option :value="null">Ninguno</option>
              <option v-for="p in proveedores.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div class="border-t border-dark-border pt-1">
            <p class="text-xs text-text-muted mb-4">Credenciales de acceso para el gestor</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input v-model="newBodega.correo" type="email" placeholder="gestor@bodega.com" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                🇨🇴 +57
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
          <h2 class="text-lg font-bold text-text-primary">Editar bodega</h2>
          <button @click="showEditBodegaModal = false" class="text-text-muted hover:text-text-primary text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:bg-dark-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Nombre</label>
            <input v-model="editingBodega.nombre" type="text" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg bg-dark-bg border border-dark-border text-text-muted text-sm select-none flex-shrink-0">
                🇨🇴 +57
              </div>
              <input v-model="editingBodega.telefonoLocal" @input="handleTelefonoInput(editingBodega, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors" />
            </div>
            <p class="text-xs text-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Proveedor asignado</label>
            <select v-model="editingBodega.proveedor_id" class="w-full bg-dark-bg border border-dark-border rounded-lg px-4 py-3 text-sm text-text-primary focus:outline-none focus:border-accent transition-colors appearance-none">
              <option :value="null">Ninguno</option>
              <option v-for="p in proveedores.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-text-primary mb-2">Estado</label>
            <div class="flex items-center gap-3">
              <button @click="editingBodega.activa = 1" class="flex-1 py-2 rounded-lg border text-sm font-medium transition-colors"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'border-dark-border text-text-muted'"><Check class="w-4 h-4 inline-block mr-1" /> Activa</button>
              <button @click="editingBodega.activa = 0" class="flex-1 py-2 rounded-lg border text-sm font-medium transition-colors"
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

    <!-- ===== MODAL ELIMINAR BODEGA ===== -->
    <div v-if="showDeleteBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar bodega</h2>
        <p class="text-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="text-text-primary font-semibold mb-2">{{ deletingBodega?.nombre }}?</p>
        <p class="text-xs text-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
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
            <h2 class="text-lg font-bold theme-text">Crear Componente Maestro</h2>
            <p class="text-xs theme-text-muted mt-0.5">Agrega especificaciones técnicas para que proveedores lo usen</p>
          </div>
          <button @click="closeAddModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-2">

          <!-- Select buscable de producto -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Producto Base <span class="text-red-400">*</span></label>
            <div class="relative">
              <input
                v-model="productoSearch"
                @input="showProductoDropdown = true"
                @focus="showProductoDropdown = true"
                type="text"
                placeholder="Buscar producto..."
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

          <!-- Especificación -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Especificación técnica <span class="text-red-400">*</span></label>
            <input v-model="newComp.especificacion" type="text" placeholder="Ej: 6 núcleos / 12 hilos · 3.7GHz · AM4" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>

          <!-- Gama -->
          <div>
            <label class="block text-sm font-medium theme-text mb-3">Gama <span class="text-red-400">*</span></label>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" @click="newComp.gama = tier"
                class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                :class="newComp.gama === tier ? 'border-accent bg-accent/10 text-accent' : 'theme-border theme-text-muted hover:border-accent/40'">
                {{ tier.charAt(0).toUpperCase() + tier.slice(1) }}
              </button>
            </div>
          </div>

          <!-- Especificaciones Avanzadas -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Núcleos (Opcional)</label>
              <input v-model="newComp.nucleos" type="number" min="1" placeholder="Ej: 8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Hilos (Opcional)</label>
              <input v-model="newComp.hilos" type="number" min="1" placeholder="Ej: 16" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Frecuencia GHz (Opcional)</label>
              <input v-model="newComp.frecuencia_hz" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Enfoque de uso</label>
              <select v-model="newComp.enfoque_uso" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option :value="null">Ninguno</option>
                <option value="estudio">Estudio</option>
                <option value="oficina">Oficina</option>
                <option value="gaming">Gaming</option>
                <option value="diseño">Diseño</option>
              </select>
            </div>
          </div>

          <p v-if="addCompError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ addCompError }}</p>
        </div>

        <div class="flex gap-3 mt-8">
          <button @click="saveNewComp" :disabled="savingAddComp" class="btn-primary flex-1 text-sm">
            {{ savingAddComp ? 'Creando...' : 'Crear Componente Maestro' }}
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

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Precio ($)</label>
              <input v-model="editingComp.precio" type="number" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Stock</label>
              <input v-model="editingComp.stock" type="number" min="0" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-3">Gama del componente</label>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" @click="editingComp.gama = tier"
                class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                :class="editingComp.gama === tier ? 'border-accent bg-accent/10 text-accent' : 'theme-border theme-text-muted hover:border-accent/40'">
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
import { Check, Trash2, Pencil, Sun, Moon, Info, Wrench, FileText, Shield, Briefcase, Gamepad2, Palette, BookOpen, Store, Users, Lock, User, Settings } from '@lucide/vue'
import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { ref, markRaw, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

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
const { getToken, logout } = useAuth()
const router = useRouter()
const toast = useToast()

function handleLogout() {
  logout()
  router.push('/login')
}

function formatDate(dateStr) {
  if (!dateStr) return '—'
  return new Date(dateStr).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' })
}

function perfilLabel(p) { return ({ office: 'Oficina', gaming: 'Gaming', design: 'Diseño', study: 'Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('bodegas')

const sections = computed(() => [
  { id: 'bodegas',            icon: markRaw(Store), label: 'Bodegas',            description: `${bodegas.value.length} bodegas registradas`,    cta: '+ Agregar bodega', count: bodegas.value.length    },
  { id: 'componentes',        icon: markRaw(Wrench), label: 'Componentes',        description: `${componentes.value.length} componentes en total`, cta: '+ Nuevo Componente Maestro',               count: componentes.value.length },
  { id: 'cotizaciones',       icon: markRaw(FileText), label: 'Cotizaciones',       description: 'Historial de cotizaciones',                       cta: null,               count: null },
  { id: 'crear-usuario',      icon: '➕', label: 'Crear usuario',      description: 'Registrar nuevo usuario',                        cta: null,               count: null },
  { id: 'gestionar-usuarios', icon: markRaw(Users), label: 'Gestionar usuarios', description: `${usuarios.value.length} usuarios registrados`,   cta: '+ Crear usuario',  count: usuarios.value.length   },
  { id: 'perfiles', icon: markRaw(Lock), label: 'Perfiles y Permisos', description: `${perfiles.value.length} perfiles`, cta: '+ Crear perfil', count: perfiles.value.length },
])

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

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

async function fetchProveedores() {
  try {
    const res = await fetch(`${API}/proveedores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) proveedores.value = data.proveedores
  } catch(e) { console.error(e) }
}

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

function openBodegaModal() {
  newBodega.value = { nombre: '', correo: '', telefono: '', password: '', proveedor_id: null }
  bodegaError.value = ''
  showBodegaModal.value = true
}

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

const productosFiltrados = computed(() => {
  if (!productoSearch.value) return productosCatalogo.value
  const q = productoSearch.value.toLowerCase()
  return productosCatalogo.value.filter(p => p.nombre.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
})

async function fetchProductosCatalogo() {
  try {
    const res = await fetch(`${API}/productos-catalogo/`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) productosCatalogo.value = data.productos || data.componentes || []
  } catch(e) { console.error(e) }
}

function openAddModal() {
  newComp.value = { producto_id: '', nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media' }
  addCompError.value = ''
  productoSearch.value = ''
  showProductoDropdown.value = false
  if (productosCatalogo.value.length === 0) fetchProductosCatalogo()
  showAddCompModal.value = true
}

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

async function saveNewComp() {
  addCompError.value = ''
  const c = newComp.value
  if (!c.producto_id || !c.especificacion || !c.gama) {
    return addCompError.value = 'El producto, especificación y gama son obligatorios'
  }
  savingAddComp.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        producto_id: c.producto_id,
        especificacion: c.especificacion,
        nucleos: c.nucleos,
        hilos: c.hilos,
        frecuencia_hz: c.frecuencia_hz,
        enfoque_uso: c.enfoque_uso,
        gama: c.gama
      })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al guardar')
      return addCompError.value = data.message ?? 'Error al guardar'
    }
    await fetchComponentes()
    closeAddModal()
    toast.success('Componente maestro creado exitosamente')
  } catch(e) {
    addCompError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally {
    savingAddComp.value = false
  }
}


const showAdvancedFilters = ref(false)
const filterGama = ref('')
const filterEnfoque = ref('')
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')

const filteredComponentes = computed(() => {
  let result = componentes.value
  
  if (filterComponente.value.trim()) {
    const q = filterComponente.value.toLowerCase()
    result = result.filter(c => c.nombre.toLowerCase().includes(q) || c.categoria.toLowerCase().includes(q))
  }
  
  if (filterGama.value) result = result.filter(c => c.gama === filterGama.value)
  if (filterEnfoque.value) result = result.filter(c => c.enfoque_uso === filterEnfoque.value)
  if (filterNucleos.value) result = result.filter(c => c.nucleos == filterNucleos.value)
  if (filterHilos.value) result = result.filter(c => c.hilos == filterHilos.value)
  if (filterFrecuenciaMin.value) result = result.filter(c => (c.frecuencia_hz || 0) >= parseFloat(filterFrecuenciaMin.value))
  
  return result
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

const filteredUsuarios = computed(() => {
  if (!filterUsuario.value.trim()) return usuarios.value
  const q = filterUsuario.value.toLowerCase()
  return usuarios.value.filter(u =>
    u.nombre.toLowerCase().includes(q) ||
    u.correo.toLowerCase().includes(q) ||
    u.apellido?.toLowerCase().includes(q)
  )
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
  createUserError.value = ''
  createUserSuccess.value = ''
}

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

function openDeleteModal(u) {
  deletingUser.value = u
  showDeleteModal.value = true
}

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

async function fetchPerfiles() {
  loadingPerfiles.value = true
  try {
    const res = await fetch(`${API}/perfiles`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) perfiles.value = data.perfiles || data
  } catch(e) { console.error(e) } finally { loadingPerfiles.value = false }
}

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

function closePerfilModal() {
  showPerfilModal.value = false
  perfilError.value = ''
}

function togglePermiso(code) {
  const idx = editingPerfil.value.permisos.indexOf(code)
  if (idx === -1) editingPerfil.value.permisos.push(code)
  else editingPerfil.value.permisos.splice(idx, 1)
}

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

async function fetchCotizaciones() {
  loadingCotizaciones.value = true
  try {
    const res = await fetch(`${API}/cotizaciones`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) cotizaciones.value = data.cotizaciones
  } catch(e) { console.error(e) } finally { loadingCotizaciones.value = false }
}


// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchBodegas()
  fetchUsuarios()
  fetchComponentes()
  fetchCotizaciones()
  fetchProveedores()
})
</script>
