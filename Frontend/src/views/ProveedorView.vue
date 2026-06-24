<template>
  <div class="flex h-screen overflow-hidden theme-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r theme-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b theme-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-accent flex items-center justify-center text-white font-bold text-xs">PC</div>
        <div>
          <p class="theme-text font-semibold text-sm leading-none">PCMATCH</p>
          <p class="theme-text-muted text-xs mt-0.5">Proveedor</p>
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
          <span>{{ section.icon }}</span>
          {{ section.label }}
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ section.count }}</span>
        </button>
      </nav>

      <!-- Info proveedor -->
      <div class="p-4 border-t theme-border space-y-1">
        <div class="px-3 py-2.5 mb-1">
          <p class="theme-text text-sm font-medium truncate">{{ user?.nombre }}</p>
          <p class="theme-text-muted text-xs truncate">{{ user?.correo }}</p>
        </div>
        <button @click="toggleTheme" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm theme-text-muted hover:theme-text hover:theme-card transition-all duration-150">
          <span v-if="isDark">☀️ Modo claro</span>
          <span v-else>🌙 Modo oscuro</span>
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
          <h1 class="font-semibold theme-text">{{ currentSection.label }}</h1>
          <p class="text-xs theme-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button v-if="currentSection.cta" @click="handleCta" class="btn-primary text-sm">
          {{ currentSection.cta }}
        </button>
      </div>

      <div class="p-8">

        <!-- ===== DASHBOARD ===== -->
        <template v-if="activeSection === 'dashboard'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Mis bodegas</p>
              <p class="text-3xl font-bold theme-text font-mono">{{ bodegas.length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Bodegas activas</p>
              <p class="text-3xl font-bold text-green-400 font-mono">{{ bodegas.filter(b => b.activa == 1).length }}</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Total componentes</p>
              <p class="text-3xl font-bold text-accent font-mono">{{ bodegas.reduce((s, b) => s + Number(b.total_componentes), 0) }}</p>
            </div>
          </div>

          <!-- Resumen de bodegas -->
          <div class="card-dark rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b theme-border">
              <h2 class="font-semibold theme-text">Mis bodegas</h2>
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando...</div>
            <div v-else-if="bodegas.length === 0" class="px-6 py-12 text-center theme-text-muted text-sm">
              No tienes bodegas asignadas aún
            </div>
            <div v-else class="divide-y divide-dark-border">
              <div v-for="b in bodegas" :key="b.id" class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-sm">🏪</div>
                  <div>
                    <p class="text-sm font-medium theme-text">{{ b.nombre }}</p>
                    <p class="text-xs theme-text-muted">{{ b.correo }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-4">
                  <div class="text-right">
                    <p class="text-sm font-mono theme-text">{{ b.total_componentes }}</p>
                    <p class="text-xs theme-text-muted">componentes</p>
                  </div>
                  <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                    {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== MIS BODEGAS ===== -->
        <template v-if="activeSection === 'bodegas'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border flex items-center justify-between">
              <h2 class="font-semibold theme-text">Mis bodegas</h2>
              <input v-model="filterBodega" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
            </div>
            <div v-if="loadingBodegas" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando bodegas...</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Nombre','Teléfono','Correo','Componentes','Estado','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredBodegas.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin bodegas asignadas</td></tr>
                <tr v-for="b in filteredBodegas" :key="b.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ b.nombre }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ b.telefono || '—' }}</td>
                  <td class="px-6 py-4 text-sm theme-text-muted">{{ b.correo }}</td>
                  <td class="px-6 py-4 text-sm font-mono theme-text">{{ b.total_componentes }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1" :class="b.activa == 1 ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'">
                      {{ b.activa == 1 ? 'Activa' : 'Inactiva' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex gap-2">
                        <button @click="toggleActivoBodega(b)" class="text-xs theme-text-muted hover:text-green-400 px-2 py-1 rounded hover:bg-green-400/10 transition-colors">
                           {{ b.activa == 1 ? 'Desactivar' : 'Activar' }}
                        </button>
                      <button @click="openEditBodega(b)" class="text-xs theme-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="openDeleteBodega(b)" class="text-xs theme-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
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
              <h2 class="font-semibold theme-text">Cotizaciones de mis bodegas</h2>
            </div>
            <div v-if="loadingCotizaciones" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando...</div>
            <div v-else-if="cotizaciones.length === 0" class="px-6 py-12 text-center theme-text-muted text-sm">Sin cotizaciones aún</div>
            <table v-else class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['#','Cliente','Perfil','Componentes','Total','Fecha']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-for="c in cotizaciones" :key="c.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
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

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">
          <div class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <div class="px-6 py-4 border-b theme-border">
              <div class="flex items-center justify-between">
                <h2 class="font-semibold theme-text">Componentes de mis bodegas</h2>
                <div class="flex items-center gap-3">
                  <input v-model="filterComponente" type="text" placeholder="Buscar..." class="theme-bg border theme-border rounded-lg px-4 py-2 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors w-48" />
                  <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn-secondary text-sm px-4 py-2 flex items-center gap-2">
                    <span>⚙️</span> Filtros
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
                <tr><th v-for="h in ['Componente','Categoría','Gama','Precio','Stock','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ c.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[c.gama]">{{ c.gama }}</span></td>
                  <td class="px-6 py-4 text-sm text-accent font-mono font-medium">${{ Number(c.precio).toLocaleString() }}</td>
                  <td class="px-6 py-4 text-sm font-mono" :class="c.stock <= 3 ? 'text-yellow-400' : 'theme-text'">{{ c.stock }}</td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <button @click="openEditComp(c)" class="text-xs theme-text-muted hover:text-accent px-2 py-1 rounded hover:bg-accent/10 transition-colors">Editar</button>
                      <button @click="openDeleteComp(c)" class="text-xs theme-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ==== MODAL AÑADIR COMPONENTE ==== -->
        <div v-if="showAddCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
          <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
          <div class="relative card-dark rounded-2xl p-6 w-full max-w-3xl my-auto shadow-2xl">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h2 class="text-lg font-bold theme-text">Añadir componente</h2>
                <p class="text-xs theme-text-muted mt-0.5">Agrega un nuevo producto a una de tus bodegas</p>
              </div>
              <button @click="closeAddModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
            </div>
            
            <div class="mb-5">
              <label class="block text-sm font-medium theme-text mb-2">Bodega destino <span class="text-red-400">*</span></label>
              <select v-model="newComp.bodega_id" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option value="" disabled>Selecciona una bodega...</option>
                <option v-for="b in bodegas.filter(b => b.activa == 1)" :key="b.id" :value="b.id">{{ b.nombre }}</option>
              </select>
              <p v-if="bodegas.filter(b => b.activa == 1).length === 0" class="text-xs text-red-400 mt-1">No tienes bodegas activas para asignar componentes.</p>
            </div>

            <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-2">
              
              <!-- Select buscable de producto -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Producto Base <span class="text-red-400">*</span></label>
                <div class="relative">
                  <input
                    v-model="productoSearch"
                    @input="showProductoDropdown = true; newComp.producto_id = ''; newComp.categoria = ''"
                    @focus="showProductoDropdown = true"
                    type="text"
                    placeholder="Buscar producto..."
                    class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
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
                  <span>✓</span> Categoría: {{ newComp.categoria }}
                </p>
              </div>

              <!-- Especificación -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Especificación técnica <span class="text-red-400">*</span></label>
                <input v-model="newComp.especificacion" type="text" placeholder="Ej: Core i5-12400F 2.5 GHz" class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
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

              <!-- Precio y Stock -->
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Precio ($) <span class="text-red-400">*</span></label>
                  <input v-model="newComp.precio" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
                </div>
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Stock inicial <span class="text-red-400">*</span></label>
                  <input v-model="newComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
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

              <div>
                <label class="block text-sm font-medium theme-text mb-1">Imagen del Componente (Opcional)</label>
                <input @change="onFileChange($event, 'add')" type="file" accept=".jpeg,.png,.jpg,.webp" class="w-full text-sm theme-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:theme-bg file:text-accent hover:file:bg-accent/10 transition-colors" />
              </div>
            </div>

            <p v-if="addCompError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ addCompError }}</p>

            <div class="flex gap-3 mt-8">
              <button @click="saveNewComp" :disabled="savingAddComp || bodegas.filter(b => b.activa == 1).length === 0" class="btn-primary flex-1 text-sm">
                {{ savingAddComp ? 'Guardando...' : 'Crear componente' }}
              </button>
              <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
            </div>
          </div>
        </div>

      </div>
    </main>

    <!-- ===== MODAL CREAR BODEGA ===== -->
    <div v-if="showBodegaModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showBodegaModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-md my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Agregar bodega</h2>
            <p class="text-xs theme-text-muted mt-0.5">Se asignará automáticamente a tu cuenta</p>
          </div>
          <button @click="showBodegaModal = false" class="theme-text-muted hover:theme-text text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>
        <div class="space-y-5">
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Nombre de la bodega</label>
            <input v-model="newBodega.nombre" type="text" placeholder="Ej: Bodega Norte" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Correo electrónico</label>
            <input v-model="newBodega.correo" type="email" placeholder="bodega@email.com" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Teléfono</label>
            <input v-model="newBodega.telefono" type="tel" placeholder="+57 300 123 4567" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Contraseña de acceso</label>
            <input v-model="newBodega.password" type="password" placeholder="Mínimo 8 caracteres" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>
          <p v-if="bodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5">{{ bodegaError }}</p>
        </div>
        <div class="flex gap-3 mt-8">
          <button @click="saveNewBodega" :disabled="savingBodega" class="btn-primary flex-1 text-sm">{{ savingBodega ? 'Creando...' : 'Crear bodega' }}</button>
          <button @click="showBodegaModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
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
            <label class="block text-sm font-medium theme-text mb-2">Teléfono</label>
            <input v-model="editingBodega.telefono" type="tel" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
          </div>
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Estado</label>
            <div class="grid grid-cols-2 gap-3">
              <button @click="editingBodega.activa = 1" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'theme-border theme-text-muted hover:border-green-500/30'">✓ Activa</button>
              <button @click="editingBodega.activa = 0" class="py-3 rounded-xl border text-sm font-medium transition-all"
                :class="editingBodega.activa == 0 ? 'border-red-500/40 bg-red-500/10 text-red-400' : 'theme-border theme-text-muted hover:border-red-500/30'">✕ Inactiva</button>
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
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar bodega</h2>
        <p class="text-text-muted text-sm mb-1">¿Eliminar <span class="text-text-primary font-semibold">{{ deletingBodega?.nombre }}</span>?</p>
        <p class="text-xs text-text-muted mb-6 px-4">Se eliminarán también todos sus componentes.</p>
        <p v-if="deleteBodegaError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-4">{{ deleteBodegaError }}</p>
        <div class="flex gap-3">
          <button @click="confirmDeleteBodega" :disabled="savingDeleteBodega" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDeleteBodega ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteBodegaModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditCompModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditCompModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-3xl my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditCompModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="grid grid-cols-2 gap-6">
          <div class="space-y-5">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Especificación técnica</label>
              <input v-model="editingComp.especificacion" type="text" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Enfoque de uso</label>
              <select v-model="editingComp.enfoque_uso" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option value="">Opcional / Mixto</option>
                <option value="gaming">Gaming</option>
                <option value="diseño">Diseño</option>
                <option value="estudio">Estudio</option>
                <option value="oficina">Oficina</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Gama</label>
              <select v-model="editingComp.gama" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option value="baja">Baja</option>
                <option value="media">Media</option>
                <option value="alta">Alta</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Estado</label>
              <select v-model="editingComp.activo" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
              </select>
            </div>
          </div>

          <div class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Núcleos</label>
                <input v-model="editingComp.nucleos" type="number" min="1" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Hilos</label>
                <input v-model="editingComp.hilos" type="number" min="1" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Frecuencia (GHz)</label>
              <input v-model="editingComp.frecuencia_hz" type="number" step="0.1" min="0" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Precio ($)</label>
                <input v-model="editingComp.precio" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Stock</label>
                <input v-model="editingComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Imagen referencial</label>
              <div class="relative w-full h-[3.15rem]">
                <input type="file" @change="onFileChange($event, 'edit')" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                <div class="absolute inset-0 flex items-center justify-between px-4 theme-bg border theme-border rounded-lg group-hover:border-accent transition-colors" :class="{'border-accent': editImagePreview}">
                  <span class="text-sm truncate mr-4" :class="editFileName ? 'theme-text' : 'theme-text-muted'">{{ editFileName || 'Cambiar imagen...' }}</span>
                  <div v-if="editImagePreview" class="w-7 h-7 rounded bg-cover bg-center border theme-border shadow-sm flex-shrink-0" :style="{ backgroundImage: `url(${editImagePreview})` }"></div>
                  <span v-else class="theme-text-muted">📷</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <p v-if="editCompError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ editCompError }}</p>

        <div class="flex gap-3 mt-8">
          <button @click="saveEditComp" :disabled="savingEditComp" class="btn-primary flex-1 text-sm">
            {{ savingEditComp ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditCompModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL ELIMINAR COMPONENTE ===== -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl">🗑️</div>
        <h2 class="text-lg font-bold text-text-primary mb-2">Eliminar componente</h2>
        <p class="text-text-muted text-sm mb-1">¿Estás seguro de que deseas eliminar</p>
        <p class="text-text-primary font-semibold mb-2">{{ deletingComp?.nombre }}?</p>
        <p class="text-xs text-text-muted mb-6 px-4">Este componente dejará de aparecer en el catálogo.</p>
        <p v-if="deleteError" class="text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 mb-4">{{ deleteError }}</p>
        <div class="flex gap-3">
          <button @click="confirmDelete" :disabled="savingDelete" class="flex-1 py-3 rounded-lg text-sm font-medium bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">
            {{ savingDelete ? 'Eliminando...' : 'Sí, eliminar' }}
          </button>
          <button @click="showDeleteModal = false" class="flex-1 btn-secondary text-sm">Cancelar</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

const API = '/api'
const { getToken, logout, user } = useAuth()
const router = useRouter()
const toast = useToast()

function handleLogout() { logout(); router.push('/login') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function perfilLabel(p) { return ({ office: '💼 Oficina', gaming: '🎮 Gaming', design: '🎨 Diseño', study: '📚 Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('dashboard')

const sections = computed(() => [
  { id: 'dashboard',    icon: '📊', label: 'Dashboard',    description: 'Resumen general de tus bodegas',           cta: null,            count: null                  },
  { id: 'bodegas',      icon: '🏪', label: 'Mis bodegas',  description: `${bodegas.value.length} bodegas asignadas`, cta: '+ Nueva bodega', count: bodegas.value.length  },
  { id: 'componentes',  icon: '🔧', label: 'Componentes',  description: `Componentes de tus bodegas`,               cta: '+ Nuevo componente', count: componentes.value.length },
  { id: 'cotizaciones', icon: '📄', label: 'Cotizaciones', description: 'Cotizaciones de tus bodegas',              cta: null,            count: cotizaciones.value.length },
])

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

function handleCta() {
  if (activeSection.value === 'bodegas') {
    newBodega.value = { nombre: '', correo: '', telefono: '', password: '' }
    bodegaError.value = ''
    showBodegaModal.value = true
  } else if (activeSection.value === 'componentes') {
    openAddModal()
  }
}

// ── Bodegas ───────────────────────────────────────────────
const bodegas            = ref([])
const loadingBodegas     = ref(false)
const filterBodega       = ref('')
const showBodegaModal    = ref(false)
const showEditBodegaModal   = ref(false)
const showDeleteBodegaModal = ref(false)
const newBodega          = ref({ nombre: '', correo: '', telefono: '', password: '' })
const editingBodega      = ref({})
const deletingBodega     = ref(null)
const bodegaError        = ref('')
const editBodegaError    = ref('')
const savingBodega       = ref(false)
const savingEditBodega   = ref(false)
const savingDeleteBodega = ref(false)
const deleteBodegaError  = ref('')

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
    if (!res.ok) {
      toast.error(data.message ?? 'Error al crear')
      return bodegaError.value = data.message ?? 'Error al crear'
    }
    await fetchBodegas()
    showBodegaModal.value = false
    toast.success('Bodega agregada exitosamente')
  } catch(e) { 
    bodegaError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingBodega.value = false }
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
      body: JSON.stringify({ id: editingBodega.value.id, nombre: editingBodega.value.nombre, telefono: editingBodega.value.telefono, activa: editingBodega.value.activa })
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error')
      return editBodegaError.value = data.message ?? 'Error'
    }
    await fetchBodegas()
    showEditBodegaModal.value = false
    toast.success('Bodega actualizada exitosamente')
  } catch(e) { 
    editBodegaError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally { savingEditBodega.value = false }
}

function openDeleteBodega(b) { deletingBodega.value = b; deleteBodegaError.value = ''; showDeleteBodegaModal.value = true }

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
      deleteBodegaError.value = data.message ?? 'Error al eliminar la bodega'
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

async function toggleActivoBodega(b) {
  const activaNuevo = b.activa == 1 ? 0 : 1
  try {
    const res = await fetch(`${API}/bodegas`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify({
        id: b.id,
        nombre: b.nombre,
        activa: activaNuevo
      })
    })
    if (res.ok) {
      await fetchBodegas()
      toast.success(activaNuevo === 1 ? 'Bodega activada' : 'Bodega desactivada')
    } else {
      toast.error('Error al cambiar estado')
    }
  } catch(e) { 
    console.error('Error al cambiar de estado', e)
    toast.error('Error de conexión')
  }
}

// ── Cotizaciones ──────────────────────────────────────────
const cotizaciones        = ref([])
const loadingCotizaciones = ref(false)

async function fetchCotizaciones() {
  loadingCotizaciones.value = true
  try {
    const res = await fetch(`${API}/cotizaciones`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) cotizaciones.value = data.cotizaciones.data || data.cotizaciones
  } catch(e) { console.error(e) } finally { loadingCotizaciones.value = false }
}

// ── Componentes ───────────────────────────────────────────
const componentes       = ref([])
const loadingComponentes = ref(false)
const filterComponente  = ref('')
const showEditCompModal = ref(false)
const editingComp = ref({})
const editCompError = ref('')
const savingEditComp = ref(false)

const showAdvancedFilters = ref(false)
const filterGama = ref('')
const filterEnfoque = ref('')
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')

const filteredComponentes = computed(() => {
  let result = [...componentes.value]
  if (filterComponente.value.trim()) {
    const q = filterComponente.value.toLowerCase()
    result = result.filter(c => c.nombre.toLowerCase().includes(q) || c.categoria.toLowerCase().includes(q))
  }
  if (filterGama.value) result = result.filter(c => c.gama === filterGama.value)
  if (filterEnfoque.value) result = result.filter(c => c.enfoque_uso === filterEnfoque.value)
  if (filterNucleos.value) result = result.filter(c => c.nucleos == filterNucleos.value)
  if (filterHilos.value) result = result.filter(c => c.hilos == filterHilos.value)
  if (filterFrecuenciaMin.value) result = result.filter(c => c.frecuencia_hz >= Number(filterFrecuenciaMin.value))
  return result
})

// Variables para Add Component
const showAddCompModal = ref(false)
const newComp = ref({ bodega_id: '', producto_id: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '' })
const addCompError = ref('')
const savingAddComp = ref(false)
const addImageFile = ref(null)
const addImagePreview = ref(null)
const addFileName = ref('')

const categoriasBase = ref([])

const productoSearch = ref('')
const showProductoDropdown = ref(false)

const productosFiltrados = computed(() => {
  if (!productoSearch.value.trim()) return categoriasBase.value.slice(0, 10)
  const q = productoSearch.value.toLowerCase()
  return categoriasBase.value.filter(p =>
    p.nombre.toLowerCase().includes(q) ||
    p.categoria.toLowerCase().includes(q)
  ).slice(0, 10)
})

function selectProducto(prod) {
  newComp.value.producto_id = prod.id
  newComp.value.nombre = prod.nombre
  newComp.value.categoria = prod.categoria
  productoSearch.value = prod.nombre
  showProductoDropdown.value = false
}

async function fetchCategoriasBase() {
  try {
    const res = await fetch(`${API}/catalogo`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) categoriasBase.value = data.productos
  } catch(e) { console.error(e) }
}

function openAddModal() {
  newComp.value = { bodega_id: '', producto_id: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '' }
  addImageFile.value = null
  addImagePreview.value = null
  addFileName.value = ''
  addCompError.value = ''
  productoSearch.value = ''
  showProductoDropdown.value = false
  if (categoriasBase.value.length === 0) fetchCategoriasBase()
  showAddCompModal.value = true
}

function closeAddModal() {
  showAddCompModal.value = false
}

function onFileChange(e, type) {
  const file = e.target.files[0]
  if (!file) return
  if (type === 'add') {
    addImageFile.value = file
    addFileName.value = file.name
    addImagePreview.value = URL.createObjectURL(file)
  } else {
    editImageFile.value = file
    editFileName.value = file.name
    editImagePreview.value = URL.createObjectURL(file)
  }
}

function blockInvalidChars(e) { if (['e', 'E', '+', '-'].includes(e.key)) e.preventDefault() }
function blockInvalidCharsStock(e) { if (['e', 'E', '+', '-', '.'].includes(e.key)) e.preventDefault() }

async function saveNewComp() {
  addCompError.value = ''
  const c = newComp.value
  if (!c.bodega_id || !c.producto_id || !c.especificacion || !c.gama || !c.precio || c.stock === '') {
    return addCompError.value = 'El producto, especificación, gama, bodega destino, precio y stock son requeridos'
  }

  savingAddComp.value = true
  const formData = new FormData()
  Object.entries(c).forEach(([k,v]) => { if(v !== '' && v !== null) formData.append(k, v) })
  if (addImageFile.value) formData.append('imagen', addImageFile.value)

  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST',
      headers: { Authorization: `Bearer ${getToken()}`, Accept: 'application/json' },
      body: formData
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al guardar el componente')
      return addCompError.value = data.message ?? 'Error al guardar el componente'
    }
    await fetchComponentes()
    await fetchBodegas() // Para actualizar el total_componentes en las bodegas
    closeAddModal()
    toast.success('Componente creado exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    addCompError.value = 'Error de conexión con el servidor'
  } finally {
    savingAddComp.value = false
  }
}

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/componentes`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.componentes
  } catch(e) { console.error(e) } finally { loadingComponentes.value = false }
}

const editImageFile = ref(null)
const editImagePreview = ref(null)
const editFileName = ref('')

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editImageFile.value = null
  editImagePreview.value = null
  editFileName.value = ''
  editCompError.value = ''
  showEditCompModal.value = true
}

async function saveEditComp() {
  editCompError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(editingComp.value.precio) <= 0) {
    return editCompError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && editingComp.value.stock !== '' && !Number.isInteger(Number(editingComp.value.stock))) {
    return editCompError.value = 'El stock debe ser un número entero sin decimales'
  }
  if (editingComp.value.stock !== undefined && Number(editingComp.value.stock) < 0) {
    return editCompError.value = 'El stock no puede ser negativo'
  }

  savingEditComp.value = true
  const formData = new FormData()
  formData.append('id', editingComp.value.id)
  // Trick for Laravel PUT via FormData
  formData.append('_method', 'PUT')
  
  const fields = ['especificacion', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama', 'precio', 'stock', 'activo']
  fields.forEach(f => {
    if (editingComp.value[f] !== undefined && editingComp.value[f] !== null) {
      formData.append(f, editingComp.value[f])
    }
  })
  
  if (editImageFile.value) formData.append('imagen', editImageFile.value)

  try {
    const res = await fetch(`${API}/componentes`, {
      method: 'POST', // POST for FormData spoofing
      headers: { Authorization: `Bearer ${getToken()}`, Accept: 'application/json' },
      body: formData
    })
    const data = await res.json()
    if (!res.ok) {
      const msg = data.message ?? 'Error al guardar los cambios'
      toast.error(msg)
      return editCompError.value = msg
    }
    await fetchComponentes()
    showEditCompModal.value = false
    toast.success('Componente actualizado exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    editCompError.value = 'Error de conexión con el servidor'
  } finally {
    savingEditComp.value = false
  }
}

const showDeleteModal = ref(false)
const deletingComp = ref(null)
const deleteError = ref('')
const savingDelete = ref(false)

function openDeleteComp(comp) {
  deletingComp.value = comp
  deleteError.value = ''
  showDeleteModal.value = true
}

async function confirmDelete() {
  if (!deletingComp.value) return
  savingDelete.value = true
  deleteError.value = ''
  try {
    const res = await fetch(`${API}/componentes/${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      deleteError.value = data.message ?? 'Error al eliminar'
      toast.error(deleteError.value)
      return
    }
    await fetchComponentes()
    showDeleteModal.value = false
    toast.success('Componente eliminado exitosamente')
  } catch (e) {
    deleteError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally {
    savingDelete.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchBodegas()
  fetchCotizaciones()
  fetchComponentes()
})
</script>
