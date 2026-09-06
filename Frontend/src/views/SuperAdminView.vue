<template>
  <div class="flex h-screen overflow-hidden theme-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r theme-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b theme-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-xs">PC</div>
        <div>
          <p class="theme-text font-semibold text-sm leading-none">PCMATCH</p>
          <p class="theme-text-muted text-xs mt-0.5">Super Admin</p>
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

    <!-- Main -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b theme-border px-8 flex items-center justify-between sticky top-0 bg-light-bg/90 dark:bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold theme-text">{{ currentSection?.label || 'Super Admin' }}</h1>
          <p class="text-xs theme-text-muted mt-0.5">{{ currentSection?.description || '' }}</p>
        </div>
        <button v-if="currentSection?.cta" @click="handleCta" class="btn-primary text-sm">
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

        <!-- ===== PROVEEDORES ===== -->
        <template v-if="activeSection === 'proveedores'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total proveedores</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ proveedores.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Aprobados</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ proveedores.filter(p => p.estado_aprobacion === 'aprobado').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Pendientes</p>
              <p class="text-3xl font-bold text-yellow-400 font-mono">{{ proveedores.filter(p => p.estado_aprobacion === 'pendiente').length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Directorio de Proveedores</h2>
              <input v-model="filterProveedor" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingProveedores" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando proveedores...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border bg-black/10 dark:bg-white/[0.02]">
                <tr><th v-for="h in ['Razón Social','ID Legal','Contacto','Documento','Aprobación','Cuenta','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-divide">
                <tr v-if="filteredProveedores.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin proveedores registrados</td></tr>
                <tr v-for="p in filteredProveedores" :key="p.id" class="theme-row-hover">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ p.razon_social || p.nombre }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted font-mono">{{ p.identificacion_legal || 'N/A' }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">
                    <div>{{ p.nombre }}</div>
                    <div class="text-xs opacity-70">{{ p.correo }}</div>
                  </td>
                  <td class="px-6 py-4">
                    <a v-if="p.documento_soporte_url" :href="p.documento_soporte_url" target="_blank" class="text-xs text-accent hover:underline flex items-center gap-1">
                      <FileText class="w-4 h-4 mr-1 inline-block" /> Ver Doc
                    </a>
                    <span v-else class="text-xs theme-text-muted">—</span>
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
                    <div class="flex flex-wrap items-center gap-1.5">
                       <button v-if="user?.rol === 'superadmin' && p.estado_aprobacion !== 'aprobado'" @click="requestConfirm({ title: 'Aprobar proveedor', message: `¿Aprobar al proveedor <strong>${p.razon_social || p.nombre}</strong>?`, confirmLabel: 'Sí, aprobar', variant: 'success', onConfirm: () => cambiarEstadoProveedor(p, 'aprobado') })" :disabled="isActionLoading(`prov_${p.id}_aprobado`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border border-emerald-500/30 active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer shadow-sm shadow-emerald-950/40" :class="isActionLoading(`prov_${p.id}_aprobado`) ? 'opacity-50 cursor-wait' : ''">
                         <Loader2 v-if="isActionLoading(`prov_${p.id}_aprobado`)" class="w-3 h-3 animate-spin" />
                         <Check v-else class="w-3 h-3 stroke-[2.5]" />
                         Aprobar
                       </button>
                       <button v-if="user?.rol === 'superadmin' && p.estado_aprobacion !== 'rechazado'" @click="requestConfirm({ title: 'Rechazar proveedor', message: `¿Rechazar al proveedor <strong>${p.razon_social || p.nombre}</strong>?`, confirmLabel: 'Sí, rechazar', variant: 'danger', onConfirm: () => cambiarEstadoProveedor(p, 'rechazado') })" :disabled="isActionLoading(`prov_${p.id}_rechazado`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-500/15 text-rose-400 hover:bg-rose-500/25 border border-rose-500/30 active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer shadow-sm shadow-rose-950/40" :class="isActionLoading(`prov_${p.id}_rechazado`) ? 'opacity-50 cursor-wait' : ''">
                         <Loader2 v-if="isActionLoading(`prov_${p.id}_rechazado`)" class="w-3 h-3 animate-spin" />
                         <X v-else class="w-3 h-3 stroke-[2.5]" />
                         Rechazar
                       </button>
                       <button @click="requestConfirm({ title: p.activo == 1 ? 'Desactivar proveedor' : 'Activar proveedor', message: `¿${p.activo == 1 ? 'Desactivar' : 'Activar'} a <strong>${p.razon_social || p.nombre}</strong>?`, confirmLabel: p.activo == 1 ? 'Sí, desactivar' : 'Sí, activar', variant: p.activo == 1 ? 'warning' : 'success', onConfirm: () => toggleActivoProveedor(p) })" :disabled="isActionLoading(`prov_${p.id}_toggle`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer border shadow-sm" :class="p.activo == 1 ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25 border-amber-500/30 shadow-amber-950/40' : 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border-emerald-500/30 shadow-emerald-950/40'">
                         <Loader2 v-if="isActionLoading(`prov_${p.id}_toggle`)" class="w-3 h-3 animate-spin" />
                         {{ p.activo == 1 ? 'Desactivar' : 'Activar' }}
                       </button>
                       <button @click="openEditProveedor(p)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium theme-text-muted hover:text-accent hover:border-accent/40 border theme-border theme-bg active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                         <Pencil class="w-3 h-3" />
                         Editar
                       </button>
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
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total bodegas</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ bodegas.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Activas</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ bodegas.filter(b => b.activa == 1).length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Con proveedores</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ bodegas.filter(b => b.proveedor_nombre).length }}</p>
            </div>
          </div>

          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Listado de bodegas</h2>
              <input v-model="filterBodega" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando bodegas...</div>
            <table v-else class="w-full min-w-[800px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Nombre','Correo','Proveedores asociados','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-divide">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin bodegas registradas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="theme-row-hover">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ b.correo }}</td>
                  <td class="px-6 py-4">
                    <div v-if="b.proveedor_nombre" class="flex flex-wrap gap-1">
                      <span v-for="pname in b.proveedor_nombre.split(', ')" :key="pname" class="badge text-xs bg-accent/10 text-accent border border-accent/20">
                        {{ pname }}
                      </span>
                    </div>
                    <span v-else class="text-xs theme-text-muted">Sin proveedores</span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text font-mono">{{ b.total_componentes }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1.5">
                      <button @click="openEditBodega(b)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium theme-text-muted hover:text-accent hover:border-accent/40 border theme-border theme-bg active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                        <Pencil class="w-3 h-3" />
                        Editar
                      </button>
                      <button @click="requestConfirm({ title: b.activa == 1 ? 'Desactivar bodega' : 'Activar bodega', message: `¿${b.activa == 1 ? 'Desactivar' : 'Activar'} la bodega <strong>${b.nombre}</strong>?`, confirmLabel: b.activa == 1 ? 'Sí, desactivar' : 'Sí, activar', variant: b.activa == 1 ? 'warning' : 'success', onConfirm: () => toggleBodega(b) })" :disabled="isActionLoading(`bod_${b.id}_toggle`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer border shadow-sm" :class="b.activa == 1 ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25 border-amber-500/30 shadow-amber-950/40' : 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border-emerald-500/30 shadow-emerald-950/40'">
                        <Loader2 v-if="isActionLoading(`bod_${b.id}_toggle`)" class="w-3 h-3 animate-spin" />
                        {{ b.activa == 1 ? 'Desactivar' : 'Activar' }}
                      </button>
                      <button @click="openDeleteBodega(b)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-400/80 hover:text-rose-400 hover:bg-rose-500/10 border border-rose-500/20 active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                        <Trash2 class="w-3 h-3" />
                        Eliminar
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== CATÁLOGO BASE ===== -->
        <template v-if="activeSection === 'catalogo'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Productos Predefinidos</h2>
              <input v-model="filterCatalogo" type="text" placeholder="Buscar producto..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingCatalogo" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando catálogo...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['ID', 'Nombre de Producto', 'Categoría']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-divide">
                <tr v-if="filteredCatalogoList.length === 0"><td colspan="3" class="px-6 py-12 text-center theme-text-muted text-sm">Sin productos en el catálogo</td></tr>
                <tr v-for="p in filteredCatalogoList" :key="p.id" class="theme-row-hover">
                  <td class="px-6 py-4 text-sm theme-text-muted font-mono">#{{ p.id }}</td>
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ p.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ p.categoria }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex flex-col lg:flex-row items-center justify-between gap-4">
              <h2 class="font-semibold theme-text whitespace-nowrap">Listado de componentes</h2>
              <div class="flex flex-wrap items-center gap-3">
                <input v-model="filterComponente" type="text" placeholder="Buscar por nombre o categoría..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-72" />
              </div>
            </div>
            <div v-if="loadingComponentes" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando componentes...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Componente','Categoría','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-divide">
                <tr v-if="filteredComponentes.length === 0"><td colspan="4" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes registrados</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="theme-row-hover">
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
                    <div class="flex items-center gap-1.5">
                      <button @click="requestConfirm({ title: c.activo == 1 ? 'Desactivar componente' : 'Activar componente', message: `¿${c.activo == 1 ? 'Desactivar' : 'Activar'} <strong>${c.nombre}</strong>?`, confirmLabel: c.activo == 1 ? 'Sí, desactivar' : 'Sí, activar', variant: c.activo == 1 ? 'warning' : 'success', onConfirm: () => toggleComponente(c) })" :disabled="isActionLoading(`comp_${c.id}_toggle`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer border shadow-sm" :class="c.activo == 1 ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25 border-amber-500/30 shadow-amber-950/40' : 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border-emerald-500/30 shadow-emerald-950/40'">
                        <Loader2 v-if="isActionLoading(`comp_${c.id}_toggle`)" class="w-3 h-3 animate-spin" />
                        {{ c.activo == 1 ? 'Desactivar' : 'Activar' }}
                      </button>
                      <button @click="openDeleteComp(c)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-400/80 hover:text-rose-400 hover:bg-rose-500/10 border border-rose-500/20 active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                        <Trash2 class="w-3 h-3" />
                        Eliminar
                      </button>
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
              <tbody class="divide-y theme-divide">
                <tr v-if="cotizaciones.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin cotizaciones</td></tr>
                <tr v-for="c in cotizaciones" :key="c.id" class="theme-row-hover">
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
          <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

            <!-- Columna Izquierda: Formulario (col-span-7) -->
            <div class="lg:col-span-7 space-y-6">

              <!-- Card 1: Selección de Rol y Permisos -->
              <div class="card-dark rounded-2xl p-6 border theme-border space-y-5">
                <h3 class="text-xs font-semibold theme-text-muted uppercase tracking-wider flex items-center gap-2">
                  <Crown class="w-4 h-4 text-accent" /> Configuración de Rol y Accesos
                </h3>

                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-2.5">Rol del usuario *</label>
                  <div class="grid grid-cols-3 gap-3">
                    <button
                      v-for="role in roles"
                      :key="role.id"
                      @click="newUser.rol = role.id"
                      type="button"
                      class="flex flex-col items-center justify-center gap-2 p-3.5 rounded-xl border transition-all duration-150"
                      :class="newUser.rol === role.id ? 'border-accent bg-accent/10 text-accent font-semibold shadow-sm' : 'theme-border theme-text-muted hover:border-accent/40 hover:theme-text theme-bg'"
                    >
                      <component :is="role.icon" class="text-xl inline-block" />
                      <span class="text-xs font-medium">{{ role.label }}</span>
                    </button>
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-2">Perfil de Permisos <span class="text-xs font-normal opacity-70">(Opcional)</span></label>
                  <select
                    v-model="newUser.perfil_id"
                    class="w-full theme-bg border theme-border rounded-xl px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors"
                  >
                    <option :value="null">Sin perfil asignado</option>
                    <option v-for="p in perfiles.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
                  </select>
                </div>
              </div>

              <!-- Card 2: Información Personal -->
              <div class="card-dark rounded-2xl p-6 border theme-border space-y-5">
                <h3 class="text-xs font-semibold theme-text-muted uppercase tracking-wider flex items-center gap-2">
                  <User class="w-4 h-4 text-accent" /> Datos de Identificación y Acceso
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium theme-text-muted mb-1.5">Nombre *</label>
                    <input
                      v-model="newUser.nombre"
                      type="text"
                      placeholder="Ej: Juan"
                      class="w-full theme-bg border theme-border rounded-xl px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>

                  <div>
                    <label class="block text-xs font-medium theme-text-muted mb-1.5">Apellido</label>
                    <input
                      v-model="newUser.apellido"
                      type="text"
                      placeholder="Ej: Pérez"
                      class="w-full theme-bg border theme-border rounded-xl px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                </div>

                <div>
                  <label class="block text-xs font-medium theme-text-muted mb-1.5">Correo electrónico *</label>
                  <input
                    v-model="newUser.correo"
                    type="email"
                    placeholder="usuario@email.com"
                    class="w-full theme-bg border theme-border rounded-xl px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                  />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-xs font-medium theme-text-muted mb-1.5">Teléfono celular</label>
                    <div class="flex gap-2">
                      <div class="flex items-center px-3 rounded-xl theme-bg theme-border border theme-text-muted text-xs select-none flex-shrink-0">
                        +57
                      </div>
                      <input
                        v-model="newUser.telefonoLocal"
                        @input="handleTelefonoInput(newUser, 'telefonoLocal')"
                        type="tel"
                        placeholder="300 123 4567"
                        maxlength="13"
                        class="flex-1 theme-bg border theme-border rounded-xl px-3.5 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                      />
                    </div>
                  </div>

                  <div>
                    <label class="block text-xs font-medium theme-text-muted mb-1.5">Contraseña temporal *</label>
                    <input
                      v-model="newUser.password"
                      type="password"
                      placeholder="Mínimo 8 caracteres"
                      class="w-full theme-bg border theme-border rounded-xl px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                    />
                  </div>
                </div>
              </div>

            </div>

            <!-- Columna Derecha: Vista Previa en Tiempo Real y Acciones (col-span-5) -->
            <div class="lg:col-span-5">
              <div class="sticky top-20 card-dark rounded-2xl p-6 border theme-border space-y-6 shadow-lg">
                <div class="flex items-center justify-between border-b theme-border pb-4">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                    <h4 class="text-xs font-semibold theme-text uppercase tracking-wider">Vista previa de la cuenta</h4>
                  </div>
                  <span class="badge text-[10px] px-2 py-0.5" :class="roleStyles[newUser.rol]?.badge">
                    {{ roleStyles[newUser.rol]?.label }}
                  </span>
                </div>

                <!-- Avatar y Nombre -->
                <div class="flex flex-col items-center text-center py-2">
                  <div
                    class="w-16 h-16 rounded-2xl flex items-center justify-center text-xl font-bold mb-3 transition-colors shadow-inner"
                    :class="roleStyles[newUser.rol]?.avatar || 'theme-bg theme-text'"
                  >
                    {{ (newUser.nombre || 'U').charAt(0).toUpperCase() }}{{ (newUser.apellido || '').charAt(0).toUpperCase() }}
                  </div>
                  <h3 class="text-base font-bold theme-text leading-snug">
                    {{ (newUser.nombre || newUser.apellido) ? `${newUser.nombre || ''} ${newUser.apellido || ''}`.trim() : 'Nombre del Usuario' }}
                  </h3>
                  <p class="text-xs theme-text-muted mt-1 font-mono">
                    {{ newUser.correo || 'correo@ejemplo.com' }}
                  </p>
                </div>

                <!-- Detalles resumen -->
                <div class="space-y-3 pt-2 text-xs border-t theme-border">
                  <div class="flex items-center justify-between py-1">
                    <span class="theme-text-muted">Rol asignado:</span>
                    <span class="font-medium theme-text capitalize">{{ roleStyles[newUser.rol]?.label }}</span>
                  </div>

                  <div class="flex items-center justify-between py-1">
                    <span class="theme-text-muted">Perfil de permisos:</span>
                    <span class="font-medium theme-text">
                      {{ perfiles.find(p => p.id === newUser.perfil_id)?.nombre || 'Sin perfil asignado' }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between py-1">
                    <span class="theme-text-muted">Teléfono:</span>
                    <span class="font-mono theme-text">
                      {{ newUser.telefonoLocal ? '+57 ' + newUser.telefonoLocal : '—' }}
                    </span>
                  </div>

                  <div class="flex items-center justify-between py-1">
                    <span class="theme-text-muted">Estado inicial:</span>
                    <span class="badge text-[10px] px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/20">
                      Activo
                    </span>
                  </div>
                </div>

                <!-- Info note -->
                <div class="bg-accent/5 border border-accent/20 rounded-xl p-3.5 text-xs text-accent flex items-start gap-2.5">
                  <ShieldCheck class="w-4 h-4 flex-shrink-0 mt-0.5" />
                  <p class="leading-relaxed text-[11px]">
                    El usuario podrá autenticarse inmediatamente usando el correo y la contraseña temporal establecida.
                  </p>
                </div>

                <!-- Mensajes de feedback -->
                <p v-if="createUserError"   class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-xl px-4 py-3">{{ createUserError }}</p>
                <p v-if="createUserSuccess" class="text-xs text-green-400 bg-green-500/10 border border-green-500/20 rounded-xl px-4 py-3">{{ createUserSuccess }}</p>

                <!-- Botones de Acción al pie de la tarjeta -->
                <div class="pt-2 border-t theme-border space-y-3">
                  <button
                    @click="saveNewUser"
                    :disabled="savingUser"
                    class="w-full btn-primary py-3.5 rounded-xl flex items-center justify-center gap-2 font-semibold text-sm shadow-lg shadow-accent/25 transition-all hover:scale-[1.01]"
                  >
                    <UserPlus class="w-4 h-4" />
                    {{ savingUser ? 'Creando usuario...' : 'Crear usuario' }}
                  </button>

                  <button
                    @click="resetNewUser"
                    type="button"
                    class="w-full btn-secondary py-2.5 rounded-xl text-xs font-medium transition-all text-center opacity-80 hover:opacity-100"
                  >
                    Limpiar campos
                  </button>
                </div>

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
              <tbody class="divide-y theme-divide">
                <tr v-if="perfiles.length === 0"><td colspan="5" class="px-6 py-12 text-center theme-text-muted text-sm">Sin perfiles</td></tr>
                <tr v-for="p in perfiles" :key="p.id" class="theme-row-hover">
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
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ usuarios.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Superadmins</p>
              <p class="text-3xl font-bold text-red-400 font-mono">{{ usuarios.filter(u => u.rol === 'superadmin').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Admins</p>
              <p class="text-3xl font-bold text-purple-400 font-mono">{{ usuarios.filter(u => u.rol === 'admin').length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Clientes</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ usuarios.filter(u => u.rol === 'cliente').length }}</p>
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
              <tbody class="divide-y theme-divide">
                <tr v-if="filteredUsuarios.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin usuarios</td></tr>
                <tr v-for="u in filteredUsuarios" :key="u.id" class="theme-row-hover">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" :class="roleStyles[u.rol]?.avatar ?? 'theme-card theme-text-muted'">
                        {{ u.nombre.charAt(0) }}
                      </div>
                      <span class="text-sm font-medium theme-text">{{ u.nombre }} {{ u.apellido }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ u.correo }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ u.telefono || '—' }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="roleStyles[u.rol]?.badge ?? ''">{{ roleStyles[u.rol]?.label ?? u.rol }}</span>
                  </td>
                  <td class="px-6 py-4">
                    <span v-if="u.activo == 0" class="badge text-[10px] px-2 py-0.5 bg-zinc-500/10 text-zinc-400 border border-zinc-500/20">Inactivo</span>
                    <span v-else class="badge text-[10px] px-2 py-0.5 bg-green-500/10 text-green-400 border border-green-500/20">Activo</span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ formatDate(u.created_at) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1.5">
                        <button @click="requestConfirm({ title: u.activo == 1 ? 'Desactivar usuario' : 'Activar usuario', message: `¿${u.activo == 1 ? 'Desactivar' : 'Activar'} a <strong>${u.nombre} ${u.apellido || ''}</strong>?`, confirmLabel: u.activo == 1 ? 'Sí, desactivar' : 'Sí, activar', variant: u.activo == 1 ? 'warning' : 'success', onConfirm: () => toggleActivoUsuario(u) })" :disabled="isActionLoading(`user_${u.id}_toggle`)" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer border shadow-sm" :class="u.activo == 1 ? 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25 border-amber-500/30 shadow-amber-950/40' : 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25 border-emerald-500/30 shadow-emerald-950/40'">
                           <Loader2 v-if="isActionLoading(`user_${u.id}_toggle`)" class="w-3 h-3 animate-spin" />
                           {{ u.activo == 1 ? 'Desactivar' : 'Activar' }}
                        </button>
                      <button @click="openEditUsuario(u)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium theme-text-muted hover:text-accent hover:border-accent/40 border theme-border theme-bg active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                        <Pencil class="w-3 h-3" />
                        Editar
                      </button>
                      <button @click="openDeleteUsuario(u)" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-400/80 hover:text-rose-400 hover:bg-rose-500/10 border border-rose-500/20 active:scale-95 transition-all inline-flex items-center gap-1 cursor-pointer">
                        <Trash2 class="w-3 h-3" />
                        Eliminar
                      </button>
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
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Historial de Acciones</h2>
            </div>
            <div v-if="loadingHistorial" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando historial...</div>
            <table v-else class="w-full min-w-[800px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Fecha','Usuario','Rol','Acción','Módulo']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y theme-divide">
                <tr v-if="historial.length === 0"><td colspan="5" class="px-6 py-12 text-center theme-text-muted text-sm">Sin registros</td></tr>
                <tr v-for="h in historial" :key="h.id" class="theme-row-hover">
                  <td class="px-6 py-4 text-sm theme-text-muted whitespace-nowrap">{{ new Date(h.created_at).toLocaleString('es-CL') }}</td>
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ h.usuario_nombre || 'Usuario Eliminado' }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-[10px] px-2 py-0.5 border" :class="roleStyles[h.rol_usuario]?.badge ?? 'theme-card theme-border'">{{ h.rol_usuario?.toUpperCase() ?? 'DESCONOCIDO' }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text">{{ h.accion }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted"><span class="badge text-xs theme-bg border theme-border">{{ h.modulo }}</span></td>
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
            <h2 class="text-lg font-bold theme-text">Registrar proveedor</h2>
            <p class="text-xs theme-text-muted mt-0.5">Ingresa los datos para registrar un proveedor</p>
          </div>
          <button @click="closeProveedorModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Nombre de proveedor</label>
            <input v-model="newProveedor.razon_social" type="text" placeholder="Ej: Intel S.A." class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Identificación Legal (RUT/NIT)</label>
            <input 
              v-model="newProveedor.identificacion_legal" 
              @input="handleNitInput($event, newProveedor)"
              type="text" 
              maxlength="11"
              placeholder="123456789-0" 
              class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors font-mono" 
            />
            <p class="text-[11px] theme-text-muted mt-1">Formato: 9 dígitos + guion + 1 dígito de verificación (ej: 123456789-0)</p>
          </div>
          <div class="border-t theme-border pt-1">
            <p class="text-xs theme-text-muted mb-2">Datos de acceso del proveedor</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Nombre representante</label>
            <input v-model="newProveedor.nombre" type="text" placeholder="Juan Pérez" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Correo electrónico</label>
            <input v-model="newProveedor.correo" type="email" placeholder="contacto@empresa.com" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Contraseña</label>
            <input v-model="newProveedor.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Documento de Soporte (PDF/Img)</label>
            <input @change="handleFileChange" type="file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm theme-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:theme-bg file:text-accent hover:file:bg-accent/10 transition-colors" />
          </div>
          <div class="rounded-lg border border-accent/20 bg-accent/5 p-3 flex items-start gap-2">
            <span class="text-accent text-sm mt-0.5"><Info class="w-4 h-4 inline-block" /></span>
            <p class="text-xs theme-text-muted leading-relaxed">El proveedor iniciará en estado 'Pendiente' de aprobación. Puedes aprobarlo inmediatamente desde la lista.</p>
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
            <h2 class="text-lg font-bold theme-text">Editar proveedor</h2>
            <p class="text-xs theme-text-muted mt-0.5">Modifica y corrige los datos del proveedor</p>
          </div>
          <button @click="showEditProveedorModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Nombre de proveedor</label>
            <input v-model="editingProveedor.razon_social" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Identificación Legal (RUT/NIT)</label>
            <input 
              v-model="editingProveedor.identificacion_legal" 
              @input="handleNitInput($event, editingProveedor)"
              type="text" 
              maxlength="11"
              placeholder="123456789-0" 
              class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" 
            />
            <p class="text-[11px] theme-text-muted mt-1">Formato: 9 dígitos + guion + 1 dígito de verificación (ej: 123456789-0)</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Nombre representante</label>
            <input v-model="editingProveedor.nombre" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
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

    <!-- ===== MODAL ASIGNAR CATALOGO ===== -->
    <div v-if="showCatalogoModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showCatalogoModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Catálogo del Proveedor</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ selectedProveedor?.nombre }}</p>
          </div>
          <button @click="showCatalogoModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        
        <div class="mb-4">
          <input v-model="catalogoSearch" type="text" placeholder="Buscar producto..." class="w-full theme-bg border theme-border rounded-lg px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
        </div>
        
        <div class="space-y-2 max-h-[50vh] overflow-y-auto pr-2">
          <div v-for="prod in catalogoFiltrado" :key="prod.id" 
               @click="toggleProductoCatalogo(prod.id)"
               class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition-colors"
               :class="selectedCatalogoIds.includes(prod.id) ? 'border-accent bg-accent/10' : 'theme-border hover:theme-border/80 theme-bg'">
            <div>
              <p class="text-sm font-medium theme-text">{{ prod.nombre }}</p>
              <p class="text-xs theme-text-muted">{{ prod.categoria }}</p>
            </div>
            <div class="w-5 h-5 rounded border flex items-center justify-center" :class="selectedCatalogoIds.includes(prod.id) ? 'bg-accent border-accent text-dark-card' : 'theme-border'">
              <span v-if="selectedCatalogoIds.includes(prod.id)" class="text-xs"><Check class="w-4 h-4 inline-block mr-1" /></span>
            </div>
          </div>
          <div v-if="catalogoFiltrado.length === 0" class="text-center py-4 theme-text-muted text-sm">No hay productos.</div>
        </div>
        
        <div class="flex gap-3 mt-6 pt-4 border-t theme-border">
          <button @click="saveCatalogo" :disabled="savingCatalogo" class="btn-primary flex-1 text-sm pt-2 pb-2">
            {{ savingCatalogo ? 'Guardando...' : 'Guardar Catálogo' }}
          </button>
          <button @click="showCatalogoModal = false" class="btn-secondary text-sm px-5 pt-2 pb-2">Cancelar</button>
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
          <div class="p-3.5 rounded-xl bg-accent/5 border border-accent/20 text-xs">
            <p class="font-medium text-accent mb-1">Proveedores asociados a esta bodega:</p>
            <p v-if="editingBodega.proveedor_nombre" class="theme-text font-medium">{{ editingBodega.proveedor_nombre }}</p>
            <p v-else class="theme-text-muted">Sin proveedores asociados aún (se asocian al incorporar componentes).</p>
            <p class="text-[11px] theme-text-muted mt-2">Las bodegas pueden seleccionar componentes de múltiples proveedores desde el catálogo.</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Estado</label>
            <div class="grid grid-cols-2 gap-3">
              <button @click="editingBodega.activa = 1" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'theme-border theme-text-muted'"><Check class="w-4 h-4 inline-block mr-1" /> Activa</button>
              <button @click="editingBodega.activa = 0" class="py-3 rounded-xl border text-sm font-medium transition-all"
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

    <!-- ===== MODAL ELIMINAR BODEGA ===== -->
    <div v-if="showDeleteBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold theme-text mb-2">Eliminar bodega</h2>
        <p class="theme-text-muted text-sm mb-1">¿Eliminar <span class="theme-text font-semibold">{{ deletingBodega?.nombre }}</span>?</p>
        <p v-if="deleteBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-2">{{ deleteBodegaError }}</p>
        <p class="text-xs theme-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
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
          <h2 class="text-lg font-bold theme-text">Editar usuario</h2>
          <button @click="showEditUsuarioModal = false" class="theme-text-muted hover:theme-text text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Nombre</label>
              <input v-model="editingUsuario.nombre" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Apellido</label>
              <input v-model="editingUsuario.apellido" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo</label>
            <input v-model="editingUsuario.correo" type="email" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Número de celular</label>
            <div class="flex gap-2">
              <div class="flex items-center px-3 rounded-lg theme-bg theme-border border theme-text-muted text-sm select-none flex-shrink-0">
                <MapPin class="w-4 h-4 mr-1 inline-block" /> +57
              </div>
              <input v-model="editingUsuario.telefonoLocal" @input="handleTelefonoInput(editingUsuario, 'telefonoLocal')" type="tel" placeholder="300 123 4567" maxlength="13" class="flex-1 theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <p class="text-xs theme-text-muted mt-1">Debe ser un número colombiano válido (3XX XXX XXXX)</p>
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-3">Rol</label>
            <div class="grid grid-cols-3 gap-2">
              <button v-for="role in roles" :key="role.id" @click="editingUsuario.rol = role.id"
                class="flex flex-col items-center gap-1.5 p-3 rounded-xl border text-xs font-medium transition-all"
                :class="editingUsuario.rol === role.id ? 'border-accent bg-accent/5 text-accent' : 'theme-border theme-text-muted hover:border-accent/40'">
                <component :is="role.icon" class="text-lg inline-block" />{{ role.label }}
              </button>
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Perfil de Permisos (Opcional)</label>
            <select v-model="editingUsuario.perfil_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
              <option :value="null">Sin perfil</option>
              <option v-for="p in perfiles.filter(p => p.activo == 1)" :key="p.id" :value="p.id">{{ p.nombre }}</option>
            </select>
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
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
        <h2 class="text-lg font-bold theme-text mb-2">Eliminar usuario</h2>
        <p class="theme-text-muted text-sm mb-1">¿Eliminar a <span class="theme-text font-semibold">{{ deletingUsuario?.nombre }} {{ deletingUsuario?.apellido }}</span>?</p>
        <p class="text-xs theme-text-muted mb-6">Esta acción no se puede deshacer.</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteUsuario" :disabled="savingDeleteUsuario" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteUsuario ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteUsuarioModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
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

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Núcleos (Opcional)</label>
              <input v-model="editingComp.nucleos" type="number" min="1" placeholder="Ej: 8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Hilos (Opcional)</label>
              <input v-model="editingComp.hilos" type="number" min="1" placeholder="Ej: 16" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-2">Frecuencia GHz (Opcional)</label>
            <input v-model="editingComp.frecuencia_hz" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-1">Actualizar Imagen (Opcional)</label>
            <input @change="handleEditCompImageChange" type="file" accept=".jpeg,.png,.jpg,.webp" class="w-full text-sm theme-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:theme-bg file:text-accent hover:file:bg-accent/10 transition-colors" />
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

    <!-- ===== MODAL AGREGAR PRODUCTO BASE ===== -->
    <div v-if="showAddProductoModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showAddProductoModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Añadir Producto Base</h2>
            <p class="text-xs theme-text-muted mt-0.5">Crear un nuevo producto predefinido</p>
          </div>
          <button @click="showAddProductoModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Nombre del producto <span class="text-red-400">*</span></label>
            <input v-model="newProducto.nombre" type="text" placeholder="Ej: Intel Core i9 14900K" class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-1">Categoría <span class="text-red-400">*</span></label>
            <select v-model="newProducto.categoria" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
              <option value="" disabled>Seleccionar...</option>
              <option value="CPU">Procesadores</option>
              <option value="GPU">Tarjetas de Video</option>
              <option value="Motherboard">Placas Madre</option>
              <option value="RAM">Memorias RAM</option>
              <option value="Storage">Almacenamiento</option>
              <option value="PSU">Fuentes de Poder</option>
              <option value="Case">Gabinetes</option>
              <option value="Cooler">Refrigeración</option>
            </select>
          </div>
          <p v-if="addProductoError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ addProductoError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveNewProducto" :disabled="savingProducto || !newProducto.nombre || !newProducto.categoria" class="btn-primary flex-1 text-sm">{{ savingProducto ? 'Guardando...' : 'Crear Producto' }}</button>
          <button @click="showAddProductoModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
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
                      <input type="checkbox" :checked="editingPerfil.permisos && editingPerfil.permisos.includes(code)" @change="togglePermiso(code)" class="appearance-none w-4 h-4 rounded border theme-border theme-bg checked:bg-accent checked:border-accent transition-colors" />
                      <svg v-if="editingPerfil.permisos && editingPerfil.permisos.includes(code)" class="absolute w-2.5 h-2.5 text-white pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
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

    <!-- ===== MODAL CONFIRMACIÓN GENÉRICO ===== -->
    <Teleport to="body">
      <div v-if="confirmAction.show" class="fixed inset-0 z-[99999] flex items-center justify-center px-4">
        <div class="fixed inset-0 bg-black/75 backdrop-blur-md transition-opacity" @click="cancelConfirm"></div>
        <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm shadow-2xl border theme-border text-center animate-fade-in z-10">
          <!-- Ícono -->
          <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
            :class="{
              'bg-green-500/15 border border-green-500/30 text-green-400': confirmAction.variant === 'success',
              'bg-red-500/15 border border-red-500/30 text-red-400': confirmAction.variant === 'danger',
              'bg-yellow-500/15 border border-yellow-500/30 text-yellow-400': confirmAction.variant === 'warning',
              'bg-accent/15 border border-accent/30 text-accent': confirmAction.variant === 'default'
            }">
            <CheckCircle2 v-if="confirmAction.variant === 'success'" class="w-7 h-7" />
            <AlertTriangle v-else-if="confirmAction.variant === 'danger'" class="w-7 h-7" />
            <AlertTriangle v-else-if="confirmAction.variant === 'warning'" class="w-7 h-7" />
            <Info v-else class="w-7 h-7" />
          </div>

          <h2 class="text-lg font-bold theme-text mb-2">{{ confirmAction.title }}</h2>
          <p class="theme-text-muted text-sm mb-6 px-2 leading-relaxed" v-html="confirmAction.message"></p>

          <div class="flex gap-3">
            <button
              @click="executeConfirm"
              :disabled="confirmAction.loading"
              class="flex-1 py-3 rounded-xl text-sm font-semibold transition-all flex items-center justify-center gap-2 cursor-pointer shadow-lg"
              :class="{
                'bg-green-600 hover:bg-green-500 text-white shadow-green-900/30': confirmAction.variant === 'success',
                'bg-red-600 hover:bg-red-500 text-white shadow-red-900/30': confirmAction.variant === 'danger',
                'bg-yellow-600 hover:bg-yellow-500 text-white shadow-yellow-900/30': confirmAction.variant === 'warning',
                'bg-accent hover:bg-blue-500 text-white shadow-blue-900/30': confirmAction.variant === 'default',
                'opacity-50 cursor-wait': confirmAction.loading
              }"
            >
              <Loader2 v-if="confirmAction.loading" class="w-4 h-4 animate-spin" />
              {{ confirmAction.loading ? 'Procesando...' : confirmAction.confirmLabel }}
            </button>
            <button @click="cancelConfirm" :disabled="confirmAction.loading" class="flex-1 btn-secondary text-sm rounded-xl">Cancelar</button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { FileText, MapPin, Package, UserPlus, Check, Trash2, Pencil, Sun, Moon, Info, Wrench, Shield, Briefcase, Gamepad2, Palette, BookOpen, Building2, Store, Users, Lock, ClipboardList, Crown, User, AlertTriangle, ShieldCheck, CheckCircle2, Loader2, X } from 'lucide-vue-next';



import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { ref, markRaw, computed, onMounted, nextTick, watch, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

// ── Confirm Modal State ───────────────────────────────────
const confirmAction = reactive({
  show: false,
  title: '',
  message: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  variant: 'default', // 'danger' | 'success' | 'warning'
  loading: false,
  onConfirm: null
})

function requestConfirm({ title, message, confirmLabel = 'Confirmar', variant = 'default', onConfirm }) {
  confirmAction.title = title
  confirmAction.message = message
  confirmAction.confirmLabel = confirmLabel
  confirmAction.variant = variant
  confirmAction.loading = false
  confirmAction.onConfirm = onConfirm
  confirmAction.show = true
}

async function executeConfirm() {
  if (confirmAction.onConfirm) {
    confirmAction.loading = true
    try {
      await confirmAction.onConfirm()
    } finally {
      confirmAction.loading = false
      confirmAction.show = false
    }
  }
}

function cancelConfirm() {
  confirmAction.show = false
  confirmAction.loading = false
}

// ── Action Loading Tracker ────────────────────────────────
const actionLoading = ref(new Set())

function isActionLoading(key) {
  return actionLoading.value.has(key)
}

function startLoading(key) {
  actionLoading.value = new Set([...actionLoading.value, key])
}

function stopLoading(key) {
  const next = new Set(actionLoading.value)
  next.delete(key)
  actionLoading.value = next
}

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
const { getToken, logout, user } = useAuth()
const router = useRouter()
const toast = useToast()

function handleLogout() { logout() }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function perfilLabel(p) { return ({ office: 'Oficina', gaming: 'Gaming', design: 'Diseño', study: 'Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('proveedores')

const sections = computed(() => [
  { id: 'proveedores',       icon: markRaw(Building2), label: 'Proveedores',       description: `${proveedores.value.length} proveedores`,       cta: '+ Agregar proveedor', count: proveedores.value.length   },
  { id: 'bodegas',           icon: markRaw(Store), label: 'Bodegas',           description: `${bodegas.value.length} bodegas`,               cta: '+ Agregar bodega',                  count: bodegas.value.length        },
  { id: 'catalogo',          icon: markRaw(Package), label: 'Catálogo Base',     description: 'Productos predefinidos',                        cta: '+ Añadir Producto',   count: null                        },
  { id: 'componentes',       icon: markRaw(Wrench), label: 'Componentes',       description: `${componentes.value.filter(c => !c.bodega_id).length} componentes maestros`, cta: '+ Nuevo Componente Maestro', count: componentes.value.filter(c => !c.bodega_id).length    },
  { id: 'cotizaciones',      icon: markRaw(FileText), label: 'Cotizaciones',      description: `${cotizaciones.value.length} cotizaciones`,     cta: null,                  count: cotizaciones.value.length   },
  { id: 'crear-usuario',     icon: markRaw(UserPlus), label: 'Crear usuario',     description: 'Registrar nuevo usuario',                       cta: null,                  count: null                        },
  { id: 'gestionar-usuarios',icon: markRaw(Users), label: 'Gestionar usuarios',description: `${usuarios.value.length} usuarios`,            cta: '+ Crear usuario',     count: usuarios.value.length       },
  { id: 'perfiles',          icon: markRaw(Lock), label: 'Perfiles y Permisos',description: `${perfiles.value.length} perfiles`,           cta: '+ Crear perfil',      count: perfiles.value.length       },
  { id: 'historial',         icon: markRaw(ClipboardList), label: 'Historial',         description: 'Registro global de acciones',                   cta: null,                  count: historial.value.length      },
])

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

function handleCta() {
  if (activeSection.value === 'proveedores')        showProveedorModal.value = true
  if (activeSection.value === 'bodegas')            showBodegaModal.value = true
  if (activeSection.value === 'catalogo')           showAddProductoModal.value = true
  if (activeSection.value === 'componentes')        openAddModal()
  if (activeSection.value === 'gestionar-usuarios') activeSection.value = 'crear-usuario'
  if (activeSection.value === 'perfiles')           openEditPerfil(null)
}

// ── Estilos ───────────────────────────────────────────────
const roles = [
  { id: 'superadmin', icon: markRaw(Crown), label: 'Superadmin' },
  { id: 'admin',      icon: markRaw(Shield), label: 'Admin'      },
  { id: 'cliente',    icon: markRaw(User), label: 'Cliente'    },
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

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredProveedores = computed(() => {
  if (!Array.isArray(proveedores.value)) return []
  if (!filterProveedor.value.trim()) return proveedores.value
  const q = filterProveedor.value.toLowerCase()
  return proveedores.value.filter(p => 
    (p.nombre && p.nombre.toLowerCase().includes(q)) || 
    (p.correo && p.correo.toLowerCase().includes(q)) || 
    (p.razon_social && p.razon_social.toLowerCase().includes(q)) || 
    (p.identificacion_legal && p.identificacion_legal.toLowerCase().includes(q))
  )
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchProveedores() {
  loadingProveedores.value = true
  try {
    const res = await fetch(`${API}/proveedores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      const list = data.proveedores?.data || data.proveedores
      proveedores.value = Array.isArray(list) ? list : []
    }
  } catch(e) { console.error(e) } finally { loadingProveedores.value = false }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openProveedorModal() {
  newProveedor.value = { razon_social: '', identificacion_legal: '', nombre: '', correo: '', password: '', documento: null }
  proveedorError.value = ''
  showProveedorModal.value = true
}

/**

 * Cierra el modal activo y limpia los errores.

 */

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

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

function handleNitInput(event, target) {
  const raw = event.target.value || ''
  let digits = raw.replace(/\D/g, '')
  if (digits.length > 10) {
    digits = digits.slice(0, 10)
  }
  if (digits.length > 9) {
    target.identificacion_legal = `${digits.slice(0, 9)}-${digits.slice(9, 10)}`
  } else {
    target.identificacion_legal = digits
  }
}

async function saveNewProveedor() {
  proveedorError.value = ''
  if (!newProveedor.value.razon_social || !newProveedor.value.identificacion_legal || !newProveedor.value.nombre || !newProveedor.value.correo || !newProveedor.value.password)
    return proveedorError.value = 'Todos los campos son requeridos excepto el documento (opcional)'
  
  const nitRegex = /^\d{9}-\d$/
  if (!nitRegex.test((newProveedor.value.identificacion_legal || '').trim())) {
    return proveedorError.value = 'La identificación legal (RUT/NIT) debe tener exactamente 9 dígitos seguidos de un guion y 1 dígito de verificación (ej: 123456789-0)'
  }
  
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
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: formData
    })
    const data = await res.json()
    if (!res.ok) return proveedorError.value = data.message ?? 'Error al crear'
    await fetchProveedores() // Como es uno nuevo, dejamos que traiga todo para obtener el ID real
    fetchHistorial()
    closeProveedorModal()
    toast.success('Proveedor agregado exitosamente')
  } catch(e) { 
    proveedorError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingProveedor.value = false }
}

async function cambiarEstadoProveedor(p, estadoNuevo) {
  const key = `prov_${p.id}_${estadoNuevo}`
  startLoading(key)
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
      proveedores.value = proveedores.value.map(prov => 
        prov.id === p.id ? { ...prov, estado_aprobacion: estadoNuevo } : prov
      );
      toast.success(`Proveedor ${estadoNuevo} exitosamente`)
      fetchHistorial()
    } else {
      const errorData = await res.json();
      toast.error(errorData.message || 'Error al cambiar estado del proveedor')
    }
  } catch(e) { 
    console.error('Error de red al cambiar estado', e);
    toast.error('Error de conexión')
  } finally {
    stopLoading(key)
  }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleActivoProveedor(p) {
  const activaNuevo = p.activo == 1 ? 0 : 1;
  const key = `prov_${p.id}_toggle`
  startLoading(key)
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
      proveedores.value = proveedores.value.map(prov => 
        prov.id === p.id ? { ...prov, activo: activaNuevo } : prov
      );
      toast.success(activaNuevo === 1 ? 'Proveedor activado' : 'Proveedor desactivado')
      fetchHistorial()
    } else {
      const errorData = await res.json();
      toast.error(errorData.message || 'Error al cambiar estado')
    }
  } catch(e) { 
    console.error('Error de red al desactivar', e);
    toast.error('Error de conexión')
  } finally {
    stopLoading(key)
  }
}

const openEditProveedor = (p) => {
  editingProveedor.value = { ...p }
  editProveedorError.value = ''
  showEditProveedorModal.value = true
}

// ... remove Asignar Catalogo variables ...

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditProveedor() {
  editProveedorError.value = ''
  if (!editingProveedor.value.nombre || !editingProveedor.value.razon_social || !editingProveedor.value.identificacion_legal)
    return editProveedorError.value = 'Nombre de proveedor, Representante e Identificación son requeridos'
    
  const nitRegex = /^\d{9}-\d$/
  if (!nitRegex.test((editingProveedor.value.identificacion_legal || '').trim())) {
    return editProveedorError.value = 'La identificación legal (RUT/NIT) debe tener exactamente 9 dígitos seguidos de un guion y 1 dígito de verificación (ej: 123456789-0)'
  }
    
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
    if (!res.ok) {
      toast.error(data.message ?? 'Error al guardar cambios')
      return editProveedorError.value = data.message ?? 'Error al guardar cambios'
    }
    
    // ✅ SOLUCIÓN: Actualización local para edición
    const index = proveedores.value.findIndex(p => p.id === editingProveedor.value.id)
    if (index !== -1) {
      proveedores.value[index].nombre = editingProveedor.value.nombre
      proveedores.value[index].razon_social = editingProveedor.value.razon_social
      proveedores.value[index].identificacion_legal = editingProveedor.value.identificacion_legal
    }
    
    fetchHistorial()
    showEditProveedorModal.value = false
    toast.success('Proveedor actualizado exitosamente')
  } catch(e) { 
    editProveedorError.value = 'Error de conexión' 
    toast.error('Error de conexión')
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

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredBodegas = computed(() => {
  if (!filterBodega.value.trim()) return bodegas.value
  const q = filterBodega.value.toLowerCase()
  return bodegas.value.filter(b => b.nombre.toLowerCase().includes(q) || b.correo.toLowerCase().includes(q))
})

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

function openEditBodega(b) { 
  editingBodega.value = { ...b }; 
  if (editingBodega.value.telefono) {
    let t = editingBodega.value.telefono.replace('+57', '').replace(/\D/g, '');
    editingBodega.value.telefonoLocal = formatTelefonoLocal(t);
  } else {
    editingBodega.value.telefonoLocal = '';
  }
  editBodegaError.value = ''; 
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

    fetchHistorial()
    showEditBodegaModal.value = false
    toast.success('Bodega actualizada exitosamente')
  } catch(e) { 
    editBodegaError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingEditBodega.value = false }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleBodega(b) {
  const activaNuevo = b.activa == 1 ? 0 : 1
  const key = `bod_${b.id}_toggle`
  startLoading(key)
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: b.id, nombre: b.nombre, telefono: b.telefono, activa: activaNuevo, proveedor_id: b.proveedor_id })
    })
    
    if (res.ok) {
      const index = bodegas.value.findIndex(bod => bod.id === b.id)
      if (index !== -1) bodegas.value[index].activa = activaNuevo
      toast.success(activaNuevo === 1 ? 'Bodega activada' : 'Bodega desactivada')
      fetchHistorial()
    } else {
      toast.error('Error al cambiar estado de bodega')
    }
  } catch(e) {
    console.error('Error al cambiar estado de bodega', e)
    toast.error('Error de conexión')
  } finally {
    stopLoading(key)
  }
}

function openDeleteBodega(b) {
  deletingBodega.value = b;
  deleteBodegaError.value = '';
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
      method: 'DELETE', headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
        toast.error(data.message ?? 'Error al eliminar')
        deleteBodegaError.value = data.message ?? 'Error al eliminar'
        return
    }
    
    // ✅ SOLUCIÓN: Eliminar localmente
    bodegas.value = bodegas.value.filter(b => b.id !== deletingBodega.value.id)
    fetchHistorial()
    showDeleteBodegaModal.value = false
    toast.success('Bodega eliminada exitosamente')
  } catch(e) { 
    console.error(e); 
    deleteBodegaError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingDeleteBodega.value = false }
}

/**

 * Cierra el modal activo y limpia los errores.

 */

const showUserModal = ref(false)
function closeUserModal() {
  showUserModal.value = false
}

/**

 * Cierra el modal activo y limpia los errores.

 */

function closeBodegaModal() {
  showBodegaModal.value = false
  bodegaError.value = ''
}

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
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newBodega.value)
    })
    const data = await res.json()
    if (!res.ok) return bodegaError.value = data.message ?? 'Error al crear'
    await fetchBodegas() // Fetch necesario para el nuevo ID
    fetchHistorial()
    closeBodegaModal()
    toast.success('Bodega agregada exitosamente')
  } catch(e) { 
    bodegaError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingBodega.value = false }
}

// ── Componentes ───────────────────────────────────────────
const componentes       = ref([])
const loadingComponentes = ref(false)
const filterComponente = ref('')
const showEditCompModal = ref(false)
const showDeleteCompModal = ref(false)
const deletingComp = ref(null)
const savingDeleteComp = ref(false)
const editingComp = ref({})
const editCompError = ref('')
const savingEditComp = ref(false)
const stockQtyAdmin = ref({})

// Variables para Add Component
const showAddCompModal = ref(false)
const newComp = ref({ producto_id: '', nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', imagen: null })
const addCompError = ref('')
const savingAddComp = ref(false)
const productoSearch = ref('')
const showProductoDropdown = ref(false)
const productosCatalogo = ref([])

function handleCompImageChange(e) {
  if (e.target.files[0]) newComp.value.imagen = e.target.files[0];
}

function handleEditCompImageChange(e) {
  if (e.target.files[0]) editingComp.value.imagen = e.target.files[0];
}

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
 * Activa un producto del catálogo base como componente maestro.
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
      body: JSON.stringify({ producto_id: c.producto_id })
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
 * Propiedad computada que filtra dinámicamente los registros de componentes por término de búsqueda.
 */
const filteredComponentes = computed(() => {
  const list = componentes.value.filter(c => !c.bodega_id)
  if (!filterComponente.value.trim()) return list
  const q = filterComponente.value.toLowerCase()
  return list.filter(c =>
    c.nombre?.toLowerCase().includes(q) ||
    c.categoria?.toLowerCase().includes(q) ||
    c.especificacion?.toLowerCase().includes(q)
  )
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/componentes/admin?solo_maestros=true`, { headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.componentes
  } catch(e) { console.error(e) } finally { loadingComponentes.value = false }
}


async function quickAdjustAdmin(comp, operacion, cantidad) {
  comp._adjusting = true
  try {
    const res = await fetch(`${API}/componentes/stock`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: comp.id, cantidad, operacion })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al ajustar stock')
      return
    }
    comp.stock = data.nuevo_stock
    toast.success(`Stock ${operacion === 'incrementar' ? 'aumentado' : 'reducido'} (${operacion === 'incrementar' ? '+' : '-'}${cantidad})`)
  } catch (e) {
    toast.error('Error de conexión')
  } finally {
    comp._adjusting = false
  }
}

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editCompError.value = ''
  showEditCompModal.value = true
}

// Añadir Producto Base Variables
const showAddProductoModal = ref(false)
const savingProducto = ref(false)
const addProductoError = ref('')
const newProducto = ref({ nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: '', imagen_url: '' })
const catalogoList = ref([])
const filterCatalogo = ref('')
const loadingCatalogo = ref(false)

// Modal Asignar Catalogo Variables
const showCatalogoModal = ref(false)
const catalogoSearch = ref('')
const selectedCatalogoIds = ref([])
const selectedProveedor = ref(null)
const catalogoFiltrado = computed(() => {
  if (!catalogoSearch.value.trim()) return catalogoList.value
  const q = catalogoSearch.value.toLowerCase()
  return catalogoList.value.filter(p => p.nombre.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
})
function toggleProductoCatalogo(id) {
  if (selectedCatalogoIds.value.includes(id)) {
    selectedCatalogoIds.value = selectedCatalogoIds.value.filter(x => x !== id)
  } else {
    selectedCatalogoIds.value.push(id)
  }
}
const savingCatalogoProveedor = ref(false)
async function saveCatalogoProveedor() {
  // Placeholder para la lógica de guardar
  showCatalogoModal.value = false
}

/**
 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.
 */

const filteredCatalogoList = computed(() => {
  if (!filterCatalogo.value.trim()) return catalogoList.value
  const q = filterCatalogo.value.toLowerCase()
  return catalogoList.value.filter(p => p.nombre.toLowerCase().includes(q) || p.categoria.toLowerCase().includes(q))
})

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchCatalogo() {
  loadingCatalogo.value = true
  try {
    const res = await fetch(`${API}/productos-catalogo/`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) catalogoList.value = data.productos
  } catch(e) {
    console.error('Error cargando catalogo', e)
  } finally {
    loadingCatalogo.value = false
  }
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveNewProducto() {
  addProductoError.value = ''
  if (!newProducto.value.nombre || !newProducto.value.categoria) {
    return addProductoError.value = 'Nombre y categoría son obligatorios.'
  }
  savingProducto.value = true
  try {
    const res = await fetch(`${API}/productos-catalogo`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newProducto.value)
    })
    const data = await res.json()
    if (!res.ok) {
      const errorMsg = data.errors ? Object.values(data.errors).flat().join(' | ') : (data.message ?? 'Error al crear producto')
      toast.error(errorMsg)
      return addProductoError.value = errorMsg
    }
    toast.success('Producto base creado exitosamente')
    showAddProductoModal.value = false
    newProducto.value = { nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: '', imagen_url: '' }
    await fetchCatalogo()
  } catch(e) {
    addProductoError.value = 'Error de conexión'
  } finally {
    savingProducto.value = false
  }
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditComp() {
  editCompError.value = ''
  savingEditComp.value = true
  
  const formData = new FormData()
  formData.append('_method', 'PUT')
  formData.append('id', editingComp.value.id)
  if (editingComp.value.especificacion) formData.append('especificacion', editingComp.value.especificacion)
  if (editingComp.value.nucleos) formData.append('nucleos', editingComp.value.nucleos)
  if (editingComp.value.hilos) formData.append('hilos', editingComp.value.hilos)
  if (editingComp.value.frecuencia_hz) formData.append('frecuencia_hz', editingComp.value.frecuencia_hz)
  if (editingComp.value.enfoque_uso) formData.append('enfoque_uso', editingComp.value.enfoque_uso)
  if (editingComp.value.gama) formData.append('gama', editingComp.value.gama)
  if (editingComp.value.precio !== undefined) formData.append('precio', editingComp.value.precio)
  if (editingComp.value.stock !== undefined) formData.append('stock', editingComp.value.stock)
  if (editingComp.value.imagen) formData.append('imagen', editingComp.value.imagen)

  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST', // Usamos POST con _method: PUT para que Laravel lea FormData
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: formData
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

    fetchHistorial()
    showEditCompModal.value = false
    toast.success('Componente actualizado exitosamente')
  } catch (e) {
    editCompError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally {
    savingEditComp.value = false
  }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleComponente(c) {
  const activo = c.activo == 1 ? 0 : 1
  const key = `comp_${c.id}_toggle`
  startLoading(key)
  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({ id: c.id, activo })
    })
    if (res.ok) {
      const index = componentes.value.findIndex(comp => comp.id === c.id)
      if (index !== -1) componentes.value[index].activo = activo
      toast.success(activo === 1 ? 'Componente activado' : 'Componente desactivado')
      fetchHistorial()
    } else {
      toast.error('Error al cambiar estado del componente')
    }
  } catch (e) {
    toast.error('Error de conexión')
  } finally {
    stopLoading(key)
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
    const res = await fetch(`${API}/componentes?id=${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message || 'Error al eliminar componente')
      showDeleteCompModal.value = false
      return
    }
    await fetchComponentes()
    showDeleteCompModal.value = false
    toast.success('Componente eliminado exitosamente')
  } catch(e) {
    console.error(e)
    toast.error('Error al eliminar componente')
  } finally { savingDeleteComp.value = false }
}

// ── Cotizaciones ──────────────────────────────────────────
const cotizaciones       = ref([])
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
    if (res.ok) cotizaciones.value = data.cotizaciones.data || data.cotizaciones
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

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredUsuarios = computed(() => {
  if (!filterUsuario.value.trim()) return usuarios.value
  const q = filterUsuario.value.toLowerCase()
  return usuarios.value.filter(u => u.nombre.toLowerCase().includes(q) || u.correo.toLowerCase().includes(q) || u.apellido?.toLowerCase().includes(q))
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
  createUserError.value = ''; createUserSuccess.value = ''
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveNewUser() {
  createUserError.value = ''; createUserSuccess.value = ''
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
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(newUser.value)
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al crear')
      return createUserError.value = data.message ?? 'Error al crear'
    }
    createUserSuccess.value = 'Usuario creado correctamente'
    toast.success('Usuario creado correctamente')
    resetNewUser()
    await fetchUsuarios() // Fetch para obtener el nuevo usuario con ID
    fetchHistorial()
  } catch(e) { createUserError.value = 'Error de conexión' } finally { savingUser.value = false }
}

function openEditUsuario(u) { 
  editingUsuario.value = { ...u }; 
  if (editingUsuario.value.telefono) {
    let t = editingUsuario.value.telefono.replace('+57', '').replace(/\D/g, '');
    editingUsuario.value.telefonoLocal = formatTelefonoLocal(t);
  } else {
    editingUsuario.value.telefonoLocal = '';
  }
  editUsuarioError.value = ''; 
  showEditUsuarioModal.value = true 
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditUsuario() {
  editUsuarioError.value = ''
  if (editingUsuario.value.telefonoLocal) {
    const digitos = editingUsuario.value.telefonoLocal.replace(/\s/g, '')
    if (digitos.length !== 10 || !digitos.startsWith('3')) {
      return editUsuarioError.value = 'El número debe tener 10 dígitos y empezar por 3 (ej: 300 123 4567)'
    }
    editingUsuario.value.telefono = '+57' + digitos
  } else {
    editingUsuario.value.telefono = null
  }
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

    fetchHistorial()
    showEditUsuarioModal.value = false
    toast.success('Usuario actualizado exitosamente')
  } catch(e) { 
    editUsuarioError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingEditUsuario.value = false }
}

function openDeleteUsuario(u) { deletingUsuario.value = u; showDeleteUsuarioModal.value = true }

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

async function confirmDeleteUsuario() {
  savingDeleteUsuario.value = true
  try {
    const res = await fetch(`${API}/usuarios?id=${deletingUsuario.value.id}`, {
      method: 'DELETE', headers: { Authorization: `Bearer ${getToken()}` }
    })
    
    if (res.ok) {
      usuarios.value = usuarios.value.filter(u => u.id !== deletingUsuario.value.id)
      toast.success('Usuario eliminado exitosamente')
      fetchHistorial()
    } else {
      toast.error('Error al eliminar usuario')
    }
    showDeleteUsuarioModal.value = false
  } catch(e) {
    console.error(e)
    toast.error('Error de conexión')
  } finally { savingDeleteUsuario.value = false }
}

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

async function toggleActivoUsuario(u) {
  const activoNuevo = u.activo == 1 ? 0 : 1
  const key = `user_${u.id}_toggle`
  startLoading(key)
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
      const index = usuarios.value.findIndex(usr => usr.id === u.id)
      if (index !== -1) usuarios.value[index].activo = activoNuevo
      toast.success(activoNuevo === 1 ? 'Usuario activado' : 'Usuario desactivado')
      fetchHistorial()
    } else {
      toast.error('Error al cambiar estado del usuario')
    }
  } catch(e) { 
    console.error('Error al cambiar de estado', e)
    toast.error('Error de conexión')
  } finally {
    stopLoading(key)
  }
}

// ── Historial ─────────────────────────────────────────────
const historial = ref([])
const loadingHistorial = ref(false)

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchHistorial() {
  loadingHistorial.value = true
  try {
    const res = await fetch(`${API}/historial`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) historial.value = data.historial.data || data.historial
  } catch(e) { console.error(e) } finally { loadingHistorial.value = false }
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
  if (!Array.isArray(editingPerfil.value.permisos)) {
    editingPerfil.value.permisos = []
  }
  const idx = editingPerfil.value.permisos.indexOf(code)
  if (idx === -1) editingPerfil.value.permisos.push(code)
  else editingPerfil.value.permisos.splice(idx, 1)
}

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
  const url = `${API}/perfiles`
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

// ── Carga y Lifecycle de Módulos ──────────────────────────
const loadedSections = ref(new Set())

function loadSectionData(section) {
  if (loadedSections.value.has(section)) return
  loadedSections.value.add(section)

  switch (section) {
    case 'proveedores':
      fetchProveedores()
      break
    case 'bodegas':
      fetchBodegas()
      fetchProveedores()
      break
    case 'catalogo':
      fetchCatalogo()
      break
    case 'componentes':
      fetchComponentes()
      break
    case 'cotizaciones':
      fetchCotizaciones()
      break
    case 'crear-usuario':
      fetchPerfiles()
      break
    case 'gestionar-usuarios':
      fetchUsuarios()
      fetchPerfiles()
      break
    case 'perfiles':
      fetchPerfiles()
      fetchPermisosDisponibles()
      break
    case 'historial':
      fetchHistorial()
      break
  }
}

watch(activeSection, (section) => {
  loadSectionData(section)
}, { immediate: true })

onMounted(async () => {
  // Carga simultánea de todos los módulos en paralelo al entrar al sistema
  await Promise.allSettled([
    fetchProveedores(),
    fetchBodegas(),
    fetchCatalogo(),
    fetchComponentes(),
    fetchCotizaciones(),
    fetchUsuarios(),
    fetchPerfiles(),
    fetchPermisosDisponibles(),
    fetchHistorial()
  ])
  ;['proveedores', 'bodegas', 'catalogo', 'componentes', 'cotizaciones', 'crear-usuario', 'gestionar-usuarios', 'perfiles', 'historial'].forEach(s => loadedSections.value.add(s))
})
</script>
