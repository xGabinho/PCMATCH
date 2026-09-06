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
          <component :is="section.icon" class="w-5 h-5 inline-block" />
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
                  <div class="w-9 h-9 rounded-lg bg-accent/10 border border-accent/20 flex items-center justify-center text-accent text-sm"><Store class="w-5 h-5" /></div>
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

          <!-- Flujo de componentes del proveedor (Chart.js) -->
          <div class="mt-8">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <BarChart3 class="w-5 h-5 text-accent" />
                <h2 class="font-semibold theme-text">Flujo de componentes</h2>
              </div>
              <select
                v-model="provFlujoRango"
                @change="fetchFlujoProveedor(); fetchRendimientoBodegas()"
                class="theme-card border theme-border rounded-lg px-3 py-1.5 text-xs theme-text focus:outline-none focus:border-accent transition-colors"
              >
                <option value="historico" class="theme-bg">Histórico</option>
                <option value="3_meses" class="theme-bg">Últimos 3 meses</option>
                <option value="1_mes" class="theme-bg">Último mes</option>
              </select>
            </div>

            <!-- Bloque A: Mayor y menor flujo de componentes -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
              <div class="card-dark rounded-xl p-5">
                <FlowBarChart
                  title="Mayor flujo (más vendidos)"
                  :items="provFlujoMayorItems"
                  orientation="y"
                  variant="accent"
                  unit-label="unidades vendidas"
                  :is-dark="isDark"
                  :loading="loadingProvFlujo"
                  empty-text="Sin ventas registradas en este período"
                />
              </div>
              <div class="card-dark rounded-xl p-5">
                <FlowBarChart
                  title="Menor flujo (baja rotación)"
                  :items="provFlujoMenorItems"
                  orientation="y"
                  variant="warning"
                  unit-label="unidades vendidas"
                  :is-dark="isDark"
                  :loading="loadingProvFlujo"
                  empty-text="Todos los componentes tienen movimiento"
                />
              </div>
            </div>

            <!-- Bloque B: Comparativa de bodegas asociadas -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div class="card-dark rounded-xl p-5">
                <FlowBarChart
                  title="Ranking de bodegas (unidades vendidas)"
                  :items="rendimientoBodegasItems"
                  orientation="y"
                  variant="accent"
                  unit-label="unidades vendidas"
                  :is-dark="isDark"
                  :loading="loadingRendimiento"
                  empty-text="Sin ventas en bodegas asociadas"
                />
              </div>
              <div class="card-dark rounded-xl p-5">
                <DistributionDoughnutChart
                  title="Distribución de ventas por bodega"
                  :labels="rendimientoLabels"
                  :values="rendimientoValues"
                  :percentages="rendimientoPercentages"
                  :is-dark="isDark"
                  :loading="loadingRendimiento"
                  :total-label="rendimientoTotalLabel"
                  empty-text="Sin datos de distribución"
                />
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
                <tr><th v-for="h in ['Nombre','Teléfono','Correo','Componentes','Estado']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
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
                <tr><th v-for="h in ['Producto','Categoría','Gama','Precio Mayorista','Stock','Acciones']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponentes.length === 0"><td colspan="6" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes</td></tr>
                <tr v-for="c in filteredComponentes" :key="c.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ c.nombre }}</td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ c.categoria }}</span></td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[c.gama]">{{ c.gama }}</span></td>
                  <td class="px-6 py-4 text-sm font-mono">
                    <span class="text-accent font-medium">${{ Number(c.precio_mayorista).toLocaleString() }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm font-mono theme-text">{{ c.stock ?? 0 }}</td>
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
                <p class="text-xs theme-text-muted mt-0.5">Agrega un producto base a tu catálogo mayorista</p>
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
                    @input="showProductoDropdown = true; newComp.master_component_id = ''; newComp.producto_id = ''; newComp.categoria = ''"
                    @focus="showProductoDropdown = true"
                    type="text"
                    placeholder="Buscar componente maestro..."
                    class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors"
                    :class="{ 'border-accent': newComp.master_component_id || newComp.producto_id }"
                    autocomplete="off"
                  />
                  <div v-if="showProductoDropdown && productosFiltrados.length > 0" class="absolute top-full left-0 right-0 mt-1 theme-card border theme-border rounded-lg shadow-xl z-20 max-h-52 overflow-y-auto">
                    <button v-for="prod in productosFiltrados" :key="prod.id" @click="selectProducto(prod)" class="w-full flex items-center justify-between px-4 py-2.5 text-sm hover:theme-bg transition-colors text-left">
                      <span class="theme-text">{{ prod.nombre }} <span v-if="prod.especificacion" class="text-xs opacity-70 ml-1">- {{ prod.especificacion }}</span></span>
                      <span class="text-xs theme-text-muted ml-3 flex-shrink-0">{{ prod.categoria }}</span>
                    </button>
                  </div>
                </div>
                <p v-if="newComp.categoria" class="text-xs text-accent mt-1.5 flex items-center gap-1">
                  <span><Check class="w-4 h-4 inline-block mr-1" /></span> Categoría: {{ newComp.categoria }}
                </p>
              </div>

              <!-- Especificación Técnica -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Especificación técnica</label>
                <input v-model="newComp.especificacion" type="text" placeholder="Ej: 6 núcleos / 12 hilos · 3.7GHz · AM4" class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
              </div>

              <!-- Gama -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Gama</label>
                <div class="grid grid-cols-3 gap-3">
                  <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" type="button" @click="newComp.gama = tier"
                    class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                    :class="newComp.gama === tier ? 'border-accent bg-accent/10 text-accent font-bold' : 'theme-border theme-text-muted hover:border-accent/40'">
                    {{ tier.charAt(0).toUpperCase() + tier.slice(1) }}
                  </button>
                </div>
              </div>

              <!-- Enfoque de uso -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Enfoque de uso</label>
                <select v-model="newComp.enfoque_uso" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                  <option :value="''">Ninguno</option>
                  <option value="gaming">Gaming</option>
                  <option value="diseño">Diseño</option>
                  <option value="oficina">Oficina</option>
                  <option value="estudio">Estudio</option>
                </select>
              </div>

              <!-- Especificaciones avanzadas -->
              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Núcleos</label>
                  <input v-model="newComp.nucleos" type="number" min="1" placeholder="Ej: 8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
                </div>
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Hilos</label>
                  <input v-model="newComp.hilos" type="number" min="1" placeholder="Ej: 16" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
                </div>
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Frecuencia (GHz)</label>
                  <input v-model="newComp.frecuencia_hz" type="number" step="0.1" min="0" placeholder="Ej: 3.8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
                </div>
              </div>

              <!-- Descripción Comercial -->
              <div>
                <label class="block text-sm font-medium theme-text mb-2">Descripción Comercial propia (Opcional)</label>
                <textarea v-model="newComp.descripcion_comercial" rows="2" placeholder="Ej: Precio especial por lotes de 10+. Garantía directa con fabricante." class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors resize-none"></textarea>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Precio Mayorista ($) <span class="text-red-400">*</span></label>
                  <input v-model="newComp.precio" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
                </div>
                <div>
                  <label class="block text-sm font-medium theme-text mb-2">Stock Disponible <span class="text-red-400">*</span></label>
                  <input v-model="newComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
                </div>
              </div>
            </div>

            <p v-if="addCompError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ addCompError }}</p>

            <div class="flex gap-3 mt-8">
              <button @click="saveNewComp" :disabled="savingAddComp" class="btn-primary flex-1 text-sm">
                {{ savingAddComp ? 'Guardando...' : 'Crear componente' }}
              </button>
              <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
            </div>
          </div>
        </div>

      </div>
    </main>



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
                :class="editingBodega.activa == 1 ? 'border-green-500/40 bg-green-500/10 text-green-400' : 'theme-border theme-text-muted hover:border-green-500/30'"><Check class="w-4 h-4 inline-block mr-1" /> Activa</button>
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
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
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
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-2xl my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditCompModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-2">
          <!-- Especificación Técnica -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Especificación técnica</label>
            <input v-model="editingComp.especificacion" type="text" placeholder="Ej: 6 núcleos / 12 hilos · 3.7GHz · AM4" class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors" />
          </div>

          <!-- Gama -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Gama</label>
            <div class="grid grid-cols-3 gap-3">
              <button v-for="tier in ['alta', 'media', 'baja']" :key="tier" type="button" @click="editingComp.gama = tier"
                class="py-2.5 rounded-lg border text-sm font-medium transition-all"
                :class="editingComp.gama === tier ? 'border-accent bg-accent/10 text-accent font-bold' : 'theme-border theme-text-muted hover:border-accent/40'">
                {{ tier.charAt(0).toUpperCase() + tier.slice(1) }}
              </button>
            </div>
          </div>

          <!-- Enfoque de uso -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Enfoque de uso</label>
            <select v-model="editingComp.enfoque_uso" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
              <option :value="''">Ninguno</option>
              <option value="gaming">Gaming</option>
              <option value="diseño">Diseño</option>
              <option value="oficina">Oficina</option>
              <option value="estudio">Estudio</option>
            </select>
          </div>

          <!-- Especificaciones avanzadas -->
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Núcleos</label>
              <input v-model="editingComp.nucleos" type="number" min="1" placeholder="Ej: 8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Hilos</label>
              <input v-model="editingComp.hilos" type="number" min="1" placeholder="Ej: 16" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Frecuencia (GHz)</label>
              <input v-model="editingComp.frecuencia_hz" type="number" step="0.1" min="0" placeholder="Ej: 3.8" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
            </div>
          </div>

          <!-- Descripción Comercial -->
          <div>
            <label class="block text-sm font-medium theme-text mb-2">Descripción Comercial propia (Opcional)</label>
            <textarea v-model="editingComp.descripcion_comercial" rows="2" placeholder="Ej: Precio especial por lotes. Garantía con fabricante." class="allow-special w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors resize-none"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Precio Mayorista ($)</label>
              <input v-model="editingComp.precio_mayorista" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Stock Disponible</label>
              <input v-model="editingComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors font-mono" />
            </div>
          </div>
          <p v-if="editCompError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ editCompError }}</p>
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
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showDeleteModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-sm my-auto shadow-2xl text-center">
        <div class="w-14 h-14 rounded-full bg-red-500/10 border border-red-500/20 flex items-center justify-center mx-auto mb-4 text-2xl"><Trash2 class="w-4 h-4 inline-block" /></div>
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
import { Store, BarChart3, Check, Trash2, Sun, Moon, Wrench, FileText, Briefcase, Gamepad2, Palette, BookOpen, Settings } from 'lucide-vue-next';
import FlowBarChart from '../components/charts/FlowBarChart.vue'
import DistributionDoughnutChart from '../components/charts/DistributionDoughnutChart.vue'

import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { ref, markRaw, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'

import { API } from '@/config/api'
const { getToken, logout, user } = useAuth()
const router = useRouter()
const toast = useToast()

function handleLogout() { logout(); router.push('/login') }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('es-CL', { day: '2-digit', month: 'short', year: 'numeric' }) : '—' }
function perfilLabel(p) { return ({ office: 'Oficina', gaming: 'Gaming', design: 'Diseño', study: 'Estudio' })[p] ?? p ?? '—' }

// ── Secciones ─────────────────────────────────────────────
const activeSection = ref('dashboard')

const sections = computed(() => [
  { id: 'dashboard',    icon: BarChart3, label: 'Dashboard',    description: 'Resumen general de tus bodegas',           cta: null,            count: null                  },
  { id: 'bodegas',      icon: markRaw(Store), label: 'Bodegas asociadas',  description: `${bodegas.value.length} bodegas asociadas`, cta: null, count: bodegas.value.length  },
  { id: 'componentes',  icon: markRaw(Wrench), label: 'Componentes',  description: `Componentes de tus bodegas`,               cta: '+ Nuevo componente', count: componentes.value.length },
  { id: 'cotizaciones', icon: markRaw(FileText), label: 'Cotizaciones', description: 'Cotizaciones de tus bodegas',              cta: null,            count: cotizaciones.value.length },
])

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const currentSection = computed(() => sections.value.find(s => s.id === activeSection.value))

function handleCta() {
  if (activeSection.value === 'componentes') {
    openAddModal()
  }
}

// ── Bodegas ───────────────────────────────────────────────
const bodegas            = ref([])
const loadingBodegas     = ref(false)
const filterBodega       = ref('')
const showEditBodegaModal   = ref(false)
const showDeleteBodegaModal = ref(false)
const editingBodega      = ref({})
const deletingBodega     = ref(null)
const editBodegaError    = ref('')
const savingEditBodega   = ref(false)
const savingDeleteBodega = ref(false)
const deleteBodegaError  = ref('')
const showBodegaModal    = ref(false)
const newBodega          = ref({ nombre: '', correo: '', password: '', telefono: '', direccion: '' })
const bodegaError        = ref('')
const savingBodega       = ref(false)

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

/**
 * Valida y envía los datos del formulario al backend (POST/PUT).
 * Maneja la lógica de guardado y muestra feedback al usuario.
 */
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

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

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

/**

 * Alterna el estado (activo/inactivo) de un elemento en la base de datos.

 */

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
    result = result.filter(c => c.nombre?.toLowerCase().includes(q) || c.categoria?.toLowerCase().includes(q))
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
const newComp = ref({ producto_id: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '', descripcion_comercial: '' })
const addCompError = ref('')
const savingAddComp = ref(false)
const addImageFile = ref(null)
const addImagePreview = ref(null)
const addFileName = ref('')

const categoriasBase = ref([])

const productoSearch = ref('')
const showProductoDropdown = ref(false)

/**

 * Propiedad computada que filtra el catálogo de productos disponible en tiempo real.

 */

const productosFiltrados = computed(() => {
  if (!productoSearch.value.trim()) return categoriasBase.value.slice(0, 10)
  const q = productoSearch.value.toLowerCase()
  return categoriasBase.value.filter(p =>
    (p.nombre && p.nombre.toLowerCase().includes(q)) ||
    (p.categoria && p.categoria.toLowerCase().includes(q)) ||
    (p.especificacion && p.especificacion.toLowerCase().includes(q))
  ).slice(0, 10)
})

function selectProducto(prod) {
  newComp.value.master_component_id = prod.id
  newComp.value.producto_id = prod.id
  newComp.value.nombre = prod.nombre
  newComp.value.categoria = prod.categoria
  newComp.value.especificacion = prod.especificacion || ''
  newComp.value.gama = prod.gama || 'media'
  newComp.value.nucleos = prod.nucleos || ''
  newComp.value.hilos = prod.hilos || ''
  newComp.value.frecuencia_hz = prod.frecuencia_hz || ''
  newComp.value.enfoque_uso = prod.enfoque_uso || ''
  productoSearch.value = prod.nombre
  showProductoDropdown.value = false
}

/**
 * Obtiene datos desde el backend mediante API.
 * Mantiene sincronizada la vista con la base de datos.
 */
async function fetchCategoriasBase() {
  try {
    const res = await fetch(`${API}/componentes/maestros`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok && (data.componentes || data.productos)) {
      categoriasBase.value = data.componentes || data.productos || []
    }
  } catch(e) {
    console.error('Error al cargar componentes maestros habilitados', e)
  }
}

/**
 * Abre el modal correspondiente e inicializa los datos necesarios.
 */
function openAddModal() {
  newComp.value = {
    producto_id: '',
    especificacion: '',
    nucleos: '',
    hilos: '',
    frecuencia_hz: '',
    enfoque_uso: '',
    gama: 'media',
    precio: '',
    stock: '',
    descripcion_comercial: ''
  }
  productoSearch.value = ''
  showProductoDropdown.value = false
  addCompError.value = ''
  showAddCompModal.value = true
  fetchCategoriasBase()
}

/**

 * Cierra el modal activo y limpia los errores.

 */

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

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveNewComp() {
  if (!newComp.value.producto_id) return addCompError.value = 'Selecciona un producto base'
  if (!newComp.value.precio) return addCompError.value = 'El precio mayorista es requerido'
  if (newComp.value.stock === undefined || newComp.value.stock === '') return addCompError.value = 'El stock es requerido'
  
  if (Number(String(newComp.value.precio).replace(',', '.')) <= 0) {
    return addCompError.value = 'El precio debe ser mayor a 0'
  }

  addCompError.value = ''
  savingAddComp.value = true
  
  try {
    const payload = {
      items: [
        {
          producto_catalogo_id: newComp.value.producto_id,
          precio_mayorista: newComp.value.precio,
          stock: newComp.value.stock,
          especificacion: newComp.value.especificacion || null,
          gama: newComp.value.gama || 'media',
          enfoque_uso: newComp.value.enfoque_uso || null,
          nucleos: newComp.value.nucleos ? Number(newComp.value.nucleos) : null,
          hilos: newComp.value.hilos ? Number(newComp.value.hilos) : null,
          frecuencia_hz: newComp.value.frecuencia_hz ? Number(newComp.value.frecuencia_hz) : null,
          descripcion_comercial: newComp.value.descripcion_comercial || null
        }
      ]
    }
    
    const res = await fetch(`${API}/proveedores/me/productos`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(payload)
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

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchComponentes() {
  loadingComponentes.value = true
  try {
    const res = await fetch(`${API}/proveedores/me/productos`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) componentes.value = data.productos
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

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditComp() {
  if (!editingComp.value.precio_mayorista) return editCompError.value = 'El precio mayorista es requerido'
  if (editingComp.value.stock === undefined || editingComp.value.stock === '') return editCompError.value = 'El stock es requerido'

  editCompError.value = ''
  savingEditComp.value = true
  
  try {
    const payload = {
      producto_catalogo_id: editingComp.value.id,
      precio_mayorista: editingComp.value.precio_mayorista,
      stock: editingComp.value.stock,
      especificacion: editingComp.value.especificacion || null,
      gama: editingComp.value.gama || 'media',
      enfoque_uso: editingComp.value.enfoque_uso || null,
      nucleos: editingComp.value.nucleos ? Number(editingComp.value.nucleos) : null,
      hilos: editingComp.value.hilos ? Number(editingComp.value.hilos) : null,
      frecuencia_hz: editingComp.value.frecuencia_hz ? Number(editingComp.value.frecuencia_hz) : null,
      descripcion_comercial: editingComp.value.descripcion_comercial || null
    }

    const res = await fetch(`${API}/proveedores/catalogo/item`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${getToken()}` },
      body: JSON.stringify(payload)
    })
    const data = await res.json()
    if (!res.ok) {
      toast.error(data.message ?? 'Error al actualizar')
      return editCompError.value = data.message ?? 'Error al actualizar'
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

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

async function confirmDelete() {
  if (!deletingComp.value) return
  savingDelete.value = true
  deleteError.value = ''
  try {
    const res = await fetch(`${API}/proveedores/catalogo/item?producto_catalogo_id=${deletingComp.value.id}`, {
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

// ── Analítica: Flujo de componentes del proveedor ────────
const provFlujoRango = ref('historico')
const loadingProvFlujo = ref(false)
const provFlujoMayorData = ref([])
const provFlujoMenorData = ref([])

const provFlujoMayorItems = computed(() =>
  provFlujoMayorData.value.map(d => ({
    label: d.producto_nombre + (d.especificacion ? ` (${d.especificacion})` : ''),
    value: Number(d.total_salida),
    sublabel: `${d.categoria} · ${d.bodega_nombre} · stock: ${d.stock}`,
  }))
)

const provFlujoMenorItems = computed(() =>
  provFlujoMenorData.value.map(d => ({
    label: d.producto_nombre + (d.especificacion ? ` (${d.especificacion})` : ''),
    value: Number(d.total_salida),
    sublabel: `${d.categoria} · ${d.bodega_nombre} · stock: ${d.stock}`,
  }))
)

async function fetchFlujoProveedor() {
  loadingProvFlujo.value = true
  try {
    const res = await fetch(
      `${API}/analiticas/proveedor/flujo-componentes?rango_fecha=${provFlujoRango.value}&limit=10`,
      { headers: { Authorization: `Bearer ${getToken()}` } }
    )
    const data = await res.json()
    if (res.ok) {
      provFlujoMayorData.value = data.mayor_flujo || []
      provFlujoMenorData.value = data.menor_flujo || []
    }
  } catch (e) {
    console.error('Error fetching flujo proveedor:', e)
  } finally {
    loadingProvFlujo.value = false
  }
}

// ── Analítica: Rendimiento de bodegas asociadas ────────
const loadingRendimiento = ref(false)
const rendimientoData = ref([])
const rendimientoTotalGeneral = ref(0)

const rendimientoBodegasItems = computed(() =>
  rendimientoData.value.map(d => ({
    label: d.bodega_nombre,
    value: d.total_vendido,
    sublabel: `${d.total_cotizaciones} cotizaciones · ${d.porcentaje}%`,
  }))
)

const rendimientoLabels = computed(() => rendimientoData.value.map(d => d.bodega_nombre))
const rendimientoValues = computed(() => rendimientoData.value.map(d => d.total_vendido))
const rendimientoPercentages = computed(() => rendimientoData.value.map(d => d.porcentaje))
const rendimientoTotalLabel = computed(() =>
  rendimientoTotalGeneral.value > 0 ? `${rendimientoTotalGeneral.value} unidades` : ''
)

async function fetchRendimientoBodegas() {
  loadingRendimiento.value = true
  try {
    const res = await fetch(
      `${API}/analiticas/proveedor/rendimiento-bodegas?rango_fecha=${provFlujoRango.value}`,
      { headers: { Authorization: `Bearer ${getToken()}` } }
    )
    const data = await res.json()
    if (res.ok) {
      rendimientoData.value = data.data || []
      rendimientoTotalGeneral.value = data.total_general || 0
    }
  } catch (e) {
    console.error('Error fetching rendimiento bodegas:', e)
  } finally {
    loadingRendimiento.value = false
  }
}

// ── Lifecycle ─────────────────────────────────────────
onMounted(() => {
  fetchBodegas()
  fetchCotizaciones()
  fetchComponentes()
  fetchFlujoProveedor()
  fetchRendimientoBodegas()
})
</script>
