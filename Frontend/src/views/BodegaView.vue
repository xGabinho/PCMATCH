<template>
  <div class="flex h-screen overflow-hidden theme-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r theme-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b theme-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-yellow-500 flex items-center justify-center text-white font-bold text-xs"><Store class="w-4 h-4" /></div>
        <div>
          <p class="theme-text font-semibold text-sm leading-none">{{ bodegaNombre }}</p>
          <p class="theme-text-muted text-xs mt-0.5">Gestor de bodega</p>
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
          <span v-if="section.count !== null" class="ml-auto text-xs font-mono opacity-60">{{ myComponents.length }}</span>
        </button>
      </nav>

      <div class="p-3 border-t theme-border space-y-1">
        <div class="px-3 py-2.5 rounded-lg theme-card border theme-border">
          <p class="text-xs theme-text-muted">Sesión activa</p>
          <p class="text-sm font-medium theme-text mt-0.5">{{ bodegaCorreo }}</p>
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

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">

      <!-- Topbar -->
      <div class="h-16 border-b theme-border px-8 flex items-center justify-between sticky top-0 bg-light-bg/90 dark:bg-dark-bg/90 backdrop-blur z-10">
        <div>
          <h1 class="font-semibold theme-text">{{ currentSection.label }}</h1>
          <p class="text-xs theme-text-muted mt-0.5">{{ currentSection.description }}</p>
        </div>
        <button v-if="activeSection === 'componentes'" @click="openAddModal" class="btn-primary text-sm">
          + Añadir componente
        </button>
      </div>

      <div class="p-8">

        <!-- ===== DASHBOARD ===== -->
        <template v-if="activeSection === 'dashboard'">
          <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Componentes activos</p>
              <p class="text-3xl font-bold font-mono theme-text">{{ myComponents.length }}</p>
              <p class="text-xs theme-text-muted mt-1">En catálogo de cotizaciones</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Stock total</p>
              <p class="text-3xl font-bold font-mono text-accent">{{ totalStock }}</p>
              <p class="text-xs theme-text-muted mt-1">Unidades disponibles</p>
            </div>
            <div class="card-dark rounded-xl p-5">
              <p class="theme-text-muted text-xs uppercase tracking-wider mb-2">Alertas de stock</p>
              <p class="text-3xl font-bold font-mono text-yellow-400">{{ stockAlerts.length }}</p>
              <p class="text-xs theme-text-muted mt-1">Requieren atención</p>
            </div>
          </div>

          <!-- Stock alerts -->
          <div class="card-dark rounded-xl overflow-hidden mb-6">
            <div class="px-6 py-4 border-b theme-border flex items-center gap-3">
              <AlertTriangle class="w-5 h-5 text-yellow-400 inline-block" />
              <h2 class="font-semibold theme-text">Alertas de stock bajo</h2>
            </div>
            <div v-if="stockAlerts.length === 0" class="px-6 py-8 text-center theme-text-muted text-sm">
              Sin alertas de stock
            </div>
            <div v-else class="divide-y divide-dark-border">
              <div v-for="alert in stockAlerts" :key="alert.id" class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ alert.categoria }}</span>
                  <span class="text-sm theme-text">{{ alert.nombre }}</span>
                </div>
                <div class="flex items-center gap-6">
                  <div class="text-right">
                    <p class="text-xs theme-text-muted">Stock actual</p>
                    <p class="text-sm font-mono font-semibold text-yellow-400">{{ alert.stock }} unid.</p>
                  </div>
                  <button @click="openEditComp(alert)" class="btn-secondary text-xs px-3 py-1.5">
                    Actualizar stock
                  </button>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ===== COMPONENTES ===== -->
        <template v-if="activeSection === 'componentes'">

          <!-- Filters -->
          <div class="flex flex-col gap-3 mb-6">
            <div class="flex items-center gap-3">
              <input
                v-model="filterSearch"
                type="text"
                placeholder="Buscar componente..."
                class="theme-card border theme-border rounded-lg px-4 py-2.5 text-sm theme-text placeholder-text-muted focus:outline-none focus:border-accent transition-colors flex-1 max-w-xs"
              />
              <select
                v-model="filterCategory"
                class="theme-card border theme-border rounded-lg px-4 py-2.5 text-sm theme-text focus:outline-none focus:border-accent transition-colors"
              >
                <option value="" class="theme-bg">Todas las categorías</option>
                <option v-for="cat in categories" :key="cat" :value="cat" class="theme-bg">{{ cat }}</option>
              </select>
              <button @click="showAdvancedFilters = !showAdvancedFilters" class="btn-secondary text-sm px-4 py-2.5 flex items-center gap-2">
                <span><Settings class="w-4 h-4 inline-block mr-1" /></span> Filtros avanzados
              </button>
            </div>
            
            <!-- Advanced Filters Panel -->
            <div v-if="showAdvancedFilters" class="p-4 theme-card border theme-border rounded-xl grid grid-cols-2 md:grid-cols-5 gap-4 animate-fade-in">
              <div>
                <label class="block text-xs font-medium theme-text-muted mb-1.5">Gama</label>
                <select v-model="filterGama" class="w-full theme-bg border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                  <option value="">Todas</option>
                  <option value="alta">Alta</option>
                  <option value="media">Media</option>
                  <option value="baja">Baja</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium theme-text-muted mb-1.5">Enfoque</label>
                <select v-model="filterEnfoque" class="w-full theme-bg border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                  <option value="">Todos</option>
                  <option value="gaming">Gaming</option>
                  <option value="diseño">Diseño</option>
                  <option value="estudio">Estudio</option>
                  <option value="oficina">Oficina</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium theme-text-muted mb-1.5">Núcleos</label>
                <input v-model="filterNucleos" type="number" min="1" placeholder="Ej: 6" class="w-full theme-bg border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-medium theme-text-muted mb-1.5">Hilos</label>
                <input v-model="filterHilos" type="number" min="1" placeholder="Ej: 12" class="w-full theme-bg border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
              <div>
                <label class="block text-xs font-medium theme-text-muted mb-1.5">Frec. mínima (GHz)</label>
                <input v-model="filterFrecuenciaMin" type="number" step="0.1" min="0" placeholder="Ej: 3.5" class="w-full theme-bg border theme-border rounded-lg px-3 py-2 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
              </div>
            </div>
          </div>

          <!-- Loading -->
          <div v-if="loadingComponents" class="text-center py-16 theme-text-muted text-sm">
            Cargando componentes...
          </div>

          <!-- Table -->
          <div v-else class="card-dark rounded-xl overflow-hidden overflow-x-auto">
            <table class="w-full min-w-[640px]">
              <thead class="border-b theme-border">
                <tr>
                  <th v-for="h in ['Componente', 'Categoría', 'Especificación', 'Gama', 'Precio', 'Stock', 'Estado', 'Acciones']"
                    :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">
                    {{ h }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="filteredComponents.length === 0">
                  <td colspan="8" class="px-6 py-12 text-center theme-text-muted text-sm">Sin componentes</td>
                </tr>
                <tr v-for="comp in filteredComponents" :key="comp.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4 text-sm font-medium theme-text">{{ comp.nombre }}</td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ comp.categoria }}</span>
                  </td>
                  <td class="px-6 py-4 text-sm theme-text-muted max-w-48 truncate">{{ comp.especificacion }}</td>
                  <td class="px-6 py-4">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[comp.gama]">
                      {{ comp.gama }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm font-mono">
                    <div class="flex flex-col">
                      <div v-if="comp.descuento_activo && comp.descuento_porcentaje > 0" class="flex items-center gap-1.5 mb-0.5">
                        <span class="line-through text-xs theme-text-muted opacity-70">${{ Number(comp.precio).toLocaleString() }}</span>
                        <span class="text-[10px] bg-red-500/20 text-red-400 px-1.5 py-0.5 rounded font-bold">-{{ comp.descuento_porcentaje }}%</span>
                      </div>
                      <span class="text-accent font-semibold">${{ Number(comp.precio_final || comp.precio).toLocaleString() }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-1.5">
                      <button @click="quickAdjust(comp, 'decrementar', stockQty[comp.id] ?? 1)" :disabled="comp.stock < (stockQty[comp.id] ?? 1) || comp._adjusting" class="w-7 h-7 rounded-lg border theme-border theme-bg theme-text-muted hover:text-red-400 hover:border-red-500/40 transition-colors flex items-center justify-center text-sm font-bold disabled:opacity-30 disabled:cursor-not-allowed">−</button>
                      <input
                        type="number"
                        :value="stockQty[comp.id] ?? 1"
                        @input="stockQty[comp.id] = Math.max(1, parseInt($event.target.value) || 1)"
                        min="1"
                        class="w-12 h-7 theme-bg border theme-border rounded-lg text-center text-xs font-mono theme-text focus:outline-none focus:border-accent transition-colors [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                      />
                      <button @click="quickAdjust(comp, 'incrementar', stockQty[comp.id] ?? 1)" :disabled="comp._adjusting" class="w-7 h-7 rounded-lg border theme-border theme-bg theme-text-muted hover:text-green-400 hover:border-green-500/40 transition-colors flex items-center justify-center text-sm font-bold disabled:opacity-30 disabled:cursor-not-allowed">+</button>
                      <span class="text-sm font-mono font-semibold ml-1.5" :class="comp.stock <= 3 ? 'text-yellow-400' : 'text-accent'">{{ comp.stock }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <span class="badge text-xs px-2.5 py-1"
                      :class="comp.stock === 0
                        ? 'bg-red-500/10 text-red-400 border border-red-500/20'
                        : comp.stock <= 3
                          ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20'
                          : 'bg-green-500/10 text-green-400 border border-green-500/20'">
                      {{ comp.stock === 0 ? 'Sin stock' : comp.stock <= 3 ? 'Stock bajo' : 'Disponible' }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <button @click="openEditComp(comp)" class="text-xs theme-text-muted hover:text-yellow-400 px-2 py-1 rounded hover:bg-yellow-400/10 transition-colors">Editar</button>
                      <button @click="openDeleteComp(comp)" class="text-xs theme-text-muted hover:text-red-400 px-2 py-1 rounded hover:bg-red-400/10 transition-colors">Eliminar</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

        <!-- ===== PROVEEDORES ===== -->
        <template v-if="activeSection === 'proveedores'">
          <div v-if="!selectedProveedor" class="animate-fade-in">
            <h2 class="text-lg font-bold theme-text mb-4">Directorio de Proveedores</h2>
            <div v-if="loadingProveedores" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando proveedores...</div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <div v-if="proveedores.length === 0" class="col-span-full px-6 py-12 text-center theme-text-muted text-sm border theme-border rounded-xl">
                No hay proveedores activos en este momento.
              </div>
              <div v-for="prov in proveedores" :key="prov.id" class="card-dark rounded-2xl p-6 border theme-border flex flex-col hover:border-accent/40 transition-all group">
                <div class="flex items-start justify-between mb-4">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent font-bold text-lg">
                      {{ prov.nombre.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <h3 class="font-bold theme-text">{{ prov.nombre }}</h3>
                      <p class="text-xs theme-text-muted">{{ prov.correo }}</p>
                    </div>
                  </div>
                </div>
                <p class="text-xs theme-text-muted mb-6 flex-1">{{ prov.razon_social }} ({{ prov.identificacion_legal }})</p>
                <button @click="viewProveedorCatalogo(prov)" class="btn-primary w-full text-sm">
                  Ver catálogo
                </button>
              </div>
            </div>
          </div>

          <div v-else class="animate-fade-in">
            <button @click="selectedProveedor = null" class="mb-4 text-sm theme-text-muted hover:theme-text flex items-center gap-2">
              <span>←</span> Volver a proveedores
            </button>
            <div class="flex items-center justify-between mb-6">
              <div>
                <h2 class="text-lg font-bold theme-text">Catálogo de: {{ selectedProveedor.nombre }}</h2>
                <p class="text-xs theme-text-muted mt-0.5">Selecciona los productos que deseas añadir a tu inventario</p>
              </div>
            </div>
            
            <div v-if="loadingCatalogo" class="px-6 py-12 text-center theme-text-muted text-sm">Cargando catálogo...</div>
            <table v-else class="w-full min-w-[640px] bg-dark-bg/30 rounded-xl overflow-hidden">
              <thead class="border-b theme-border">
                <tr><th v-for="h in ['Producto','Categoría','Gama','Precio Mayorista','Acción']" :key="h" class="px-6 py-3 text-left text-xs theme-text-muted uppercase tracking-wider font-medium">{{ h }}</th></tr>
              </thead>
              <tbody class="divide-y divide-dark-border">
                <tr v-if="proveedorCatalogo.length === 0"><td colspan="5" class="px-6 py-12 text-center theme-text-muted text-sm">Este proveedor no tiene productos en su catálogo</td></tr>
                <tr v-for="p in proveedorCatalogo" :key="p.id" class="hover:bg-gray-100 dark:bg-dark-bg/50 transition-colors">
                  <td class="px-6 py-4">
                    <p class="text-sm font-medium theme-text">{{ p.nombre }}</p>
                    <p class="text-xs theme-text-muted">{{ p.descripcion_comercial || p.especificacion }}</p>
                  </td>
                  <td class="px-6 py-4"><span class="badge text-xs bg-accent/10 text-accent border border-accent/20">{{ p.categoria }}</span></td>
                  <td class="px-6 py-4"><span class="text-xs px-2 py-0.5 rounded-full font-medium border" :class="tierStyles[p.gama]">{{ p.gama }}</span></td>
                  <td class="px-6 py-4 text-sm font-mono text-accent font-medium">${{ Number(p.precio_mayorista).toLocaleString() }}</td>
                  <td class="px-6 py-4">
                    <button @click="openImportModal(p)" class="btn-secondary text-xs px-3 py-1.5 flex items-center gap-1.5">
                      <Plus class="w-4 h-4 inline-block" /> Añadir
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>

      </div>
    </main>

    <!-- ==== MODAL AÑADIR COMPONENTE ==== -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">{{ isImporting ? 'Importar Componente' : 'Añadir Componente' }}</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ isImporting ? 'Define tu precio y stock inicial' : 'Registra un nuevo producto en tu inventario' }}</p>
          </div>
          <button @click="closeAddModal" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5 max-h-[60vh] overflow-y-auto pr-2">
          
          <div v-if="isImporting" class="p-3 rounded bg-accent/10 border border-accent/20 mb-4">
            <p class="text-sm font-bold text-accent">{{ newComp.nombre }}</p>
            <p class="text-xs theme-text-muted">Precio mayorista: ${{ Number(newComp.precio_mayorista).toLocaleString() }}</p>
          </div>

          <div v-if="!isImporting" class="mb-4 relative">
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

          <!-- Precio y Stock -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Precio Retail ($) <span class="text-red-400">*</span></label>
              <input v-model="newComp.precio" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Stock inicial <span class="text-red-400">*</span></label>
              <input v-model="newComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium theme-text mb-1">Imagen del Componente (Opcional)</label>
            <input @change="onFileChange($event, 'add')" type="file" accept=".jpeg,.png,.jpg,.webp" class="w-full text-sm theme-text-muted file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:theme-bg file:text-accent hover:file:bg-accent/10 transition-colors" />
          </div>
        </div>

        <p v-if="addError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ addError }}</p>

        <div class="flex gap-3 mt-8">
          <button @click="saveNewComp" :disabled="savingAdd" class="btn-primary flex-1 text-sm">
            {{ savingAdd ? 'Guardando...' : (isImporting ? 'Importar componente' : 'Crear componente') }}
          </button>
          <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-xl my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
        </div>

        <div class="space-y-5">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Precio Retail ($)</label>
              <input v-model="editingComp.precio" type="number" min="0" step="1" @keydown="blockInvalidChars($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Stock</label>
              <input v-model="editingComp.stock" type="number" min="0" step="1" @keydown="blockInvalidCharsStock($event)" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Estado</label>
              <select v-model="editingComp.activo" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors">
                <option :value="true">Activo</option>
                <option :value="false">Inactivo</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium theme-text mb-2">Descuento (%)</label>
              <input v-model="editingComp.descuento_porcentaje" type="number" min="0" max="100" step="1" class="w-full theme-bg border theme-border rounded-lg px-4 py-3 text-sm theme-text focus:outline-none focus:border-accent transition-colors" />
            </div>
          </div>

          <div class="flex items-center mt-2">
            <label class="flex items-center gap-3 cursor-pointer">
              <input type="checkbox" v-model="editingComp.descuento_activo" class="sr-only peer" />
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-accent relative"></div>
              <span class="text-sm font-medium theme-text">Activar descuento en tienda</span>
            </label>
          </div>
          
        </div>

        <p v-if="editError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ editError }}</p>

        <div class="flex gap-3 mt-8">
          <button @click="saveEditComp" :disabled="savingEdit" class="btn-primary flex-1 text-sm">
            {{ savingEdit ? 'Guardando...' : 'Guardar cambios' }}
          </button>
          <button @click="showEditModal = false" class="btn-secondary text-sm px-5">Cancelar</button>
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
        <p class="text-xs text-text-muted mb-6 px-4">Este componente dejará de aparecer en el catálogo de cotizaciones.</p>
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
import { Store, AlertTriangle, Plus, BarChart3, Check, Trash2, Sun, Moon, Wrench, Settings } from 'lucide-vue-next';



import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { ref, markRaw, computed, onMounted, onBeforeUnmount } from 'vue'

import { API } from '@/config/api'
const toast = useToast()

const router = useRouter()
const { logout, getToken, user } = useAuth()

function handleLogout() {
  logout()
  router.push('/login')
}

// Datos de sesión — luego vendrán de useAuth
const bodegaNombre = user.value?.nombre ?? 'Bodega'
const bodegaCorreo = user.value?.correo ?? ''

// Secciones
const activeSection = ref('dashboard')
const sections = [
  { id: 'dashboard',   icon: BarChart3, label: 'Dashboard',       description: 'Resumen de tu bodega',          count: null },
  { id: 'componentes', icon: markRaw(Wrench), label: 'Mis componentes', description: 'Gestiona tu catálogo y stock',  count: true },
  { id: 'proveedores', icon: markRaw(Store), label: 'Proveedores',     description: 'Explora catálogos mayoristas',  count: null },
]
const currentSection = computed(() => sections.find(s => s.id === activeSection.value))

const categories = ['CPU', 'GPU', 'RAM', 'Storage', 'PSU', 'Motherboard', 'Cooler', 'Case']

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

// ── Proveedores Flow ─────────────────────────────────────
const proveedores = ref([])
const loadingProveedores = ref(false)
const selectedProveedor = ref(null)
const proveedorCatalogo = ref([])
const loadingCatalogo = ref(false)
const isImporting = ref(false)

async function fetchProveedores() {
  loadingProveedores.value = true
  try {
    const res = await fetch(`${API}/proveedores`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      proveedores.value = data.proveedores.data.filter(p => p.activo === true || p.activo === 1 || p.activo === 'true')
    }
  } catch(e) { console.error(e) } finally { loadingProveedores.value = false }
}

async function viewProveedorCatalogo(prov) {
  selectedProveedor.value = prov
  loadingCatalogo.value = true
  try {
    const res = await fetch(`${API}/proveedores/${prov.id}/productos`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) {
      proveedorCatalogo.value = data.productos
    }
  } catch(e) { console.error(e) } finally { loadingCatalogo.value = false }
}

function openImportModal(prod) {
  isImporting.value = true
  newComp.value = {
    master_component_id: prod.id,
    producto_id: prod.id,
    nombre: prod.nombre,
    precio_mayorista: prod.precio_mayorista,
    categoria: prod.categoria,
    especificacion: prod.especificacion,
    gama: prod.gama,
    nucleos: prod.nucleos,
    hilos: prod.hilos,
    frecuencia_hz: prod.frecuencia_hz,
    enfoque_uso: prod.enfoque_uso,
    precio: '',
    stock: '',
    descuento_porcentaje: 0,
    descuento_activo: 0
  }
  addError.value = ''
  showAddModal.value = true
}

// ── Componentes ──────────────────────────────────────────
const myComponents = ref([])
const loadingComponents = ref(false)
const filterSearch = ref('')
const filterCategory = ref('')

const showAdvancedFilters = ref(false)
const filterGama = ref('')
const filterEnfoque = ref('')
const filterNucleos = ref('')
const filterHilos = ref('')
const filterFrecuenciaMin = ref('')

/**

 * Propiedad computada que filtra dinámicamente los registros basándose en los criterios de búsqueda.

 */

const filteredComponents = computed(() => {
  let result = [...myComponents.value]
  if (filterCategory.value) result = result.filter(c => c.categoria === filterCategory.value)
  if (filterSearch.value.trim()) {
    const q = filterSearch.value.toLowerCase()
    result = result.filter(c => c.nombre.toLowerCase().includes(q) || c.especificacion?.toLowerCase().includes(q))
  }
  if (filterGama.value) result = result.filter(c => c.gama === filterGama.value)
  if (filterEnfoque.value) result = result.filter(c => c.enfoque_uso === filterEnfoque.value)
  if (filterNucleos.value) result = result.filter(c => c.nucleos == filterNucleos.value)
  if (filterHilos.value) result = result.filter(c => c.hilos == filterHilos.value)
  if (filterFrecuenciaMin.value) result = result.filter(c => c.frecuencia_hz >= Number(filterFrecuenciaMin.value))
  return result
})

const totalStock = computed(() => myComponents.value.reduce((sum, c) => sum + Number(c.stock), 0))
const stockAlerts = computed(() => myComponents.value.filter(c => c.stock <= 3))

// ── Ajuste rápido de stock ────────────────────────────────
const stockQty = ref({})

async function quickAdjust(comp, operacion, cantidad) {
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

/**

 * Obtiene datos desde el backend mediante API.

 * Mantiene sincronizada la vista con la base de datos.

 */

async function fetchComponents() {
  loadingComponents.value = true
  try {
    const res = await fetch(`${API}/componentes`, {
      headers: { Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (res.ok) myComponents.value = data.componentes
  } catch (e) {
    console.error(e)
  } finally {
    loadingComponents.value = false
  }
}

// ── Catálogo para el select buscable ─────────────────────
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
  productoSearch.value = `${prod.nombre} - ${prod.especificacion}`
  showProductoDropdown.value = false
}

function handleClickOutside(e) {
  if (!e.target.closest('.relative')) showProductoDropdown.value = false
}

const showAddModal = ref(false)
const addError = ref('')
const savingAdd = ref(false)
const newComp = ref({ master_component_id: '', producto_id: '', nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '', descuento_porcentaje: 0, descuento_activo: 0 })
const addImageFile = ref(null)
const addImagePreview = ref(null)
const addFileName = ref('')

/**
 * Obtiene datos desde el backend mediante API.
 * Mantiene sincronizada la vista con la base de datos.
 */
async function fetchCategoriasBase() {
  try {
    const res = await fetch(`${API}/componentes/maestros`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) categoriasBase.value = data.componentes || []
  } catch(e) { console.error(e) }
}

/**

 * Abre el modal correspondiente e inicializa los datos necesarios.

 */

function openAddModal() {
  isImporting.value = false
  newComp.value = { master_component_id: '', producto_id: '', nombre: '', categoria: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '', descuento_porcentaje: 0, descuento_activo: 0 }
  addImageFile.value = null
  addImagePreview.value = null
  addFileName.value = ''
  addError.value = ''
  if (categoriasBase.value.length === 0) fetchCategoriasBase()
  showAddModal.value = true
}

/**

 * Cierra el modal activo y limpia los errores.

 */

function closeAddModal() {
  showAddModal.value = false
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

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveNewComp() {
  addError.value = ''
  if (!newComp.value.master_component_id && (!newComp.value.producto_id || !newComp.value.especificacion || !newComp.value.gama)) {
    return addError.value = 'Debes seleccionar un maestro o proporcionar el producto, especificación y gama'
  }
  if (!newComp.value.precio || newComp.value.stock === '') {
    return addError.value = 'El precio y stock son requeridos'
  }

  savingAdd.value = true
  const formData = new FormData()
  Object.entries(newComp.value).forEach(([k,v]) => { if(v !== '' && v !== null) formData.append(k, v) })
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
      return addError.value = data.message ?? 'Error al guardar el componente'
    }
    await fetchComponents()
    closeAddModal()
    toast.success('Componente creado exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    addError.value = 'Error de conexión con el servidor'
  } finally {
    savingAdd.value = false
  }
}

const showEditModal = ref(false)
const editingComp = ref({})
const editError = ref('')
const savingEdit = ref(false)
const editImageFile = ref(null)
const editImagePreview = ref(null)
const editFileName = ref('')

function openEditComp(comp) {
  editingComp.value = { ...comp }
  editImageFile.value = null
  editImagePreview.value = null
  editFileName.value = ''
  editError.value = ''
  showEditModal.value = true
}

/**

 * Valida y envía los datos del formulario al backend (POST/PUT).

 * Maneja la lógica de guardado y muestra feedback al usuario.

 */

async function saveEditComp() {
  editError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(String(editingComp.value.precio).replace(',', '.')) <= 0) {
    return editError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && editingComp.value.stock !== '' && !Number.isInteger(Number(String(editingComp.value.stock).replace(',', '.')))) {
    return editError.value = 'El stock debe ser un número entero sin decimales'
  }
  if (editingComp.value.stock !== undefined && Number(String(editingComp.value.stock).replace(',', '.')) < 0) {
    return editError.value = 'El stock no puede ser negativo'
  }

  savingEdit.value = true
  const formData = new FormData()
  formData.append('id', editingComp.value.id)
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
      method: 'POST',
      headers: { Authorization: `Bearer ${getToken()}`, Accept: 'application/json' },
      body: formData
    })
    const data = await res.json()
    if (!res.ok) {
      const msg = data.message ?? 'Error al guardar los cambios'
      toast.error(msg)
      return editError.value = msg
    }
    await fetchComponents()
    showEditModal.value = false
    toast.success('Componente actualizado exitosamente')
  } catch (e) {
    toast.error('Error de conexión con el servidor')
    editError.value = 'Error de conexión con el servidor'
  } finally {
    savingEdit.value = false
  }
}

// ── Modal Eliminar ────────────────────────────────────────
const showDeleteModal = ref(false)
const deletingComp = ref(null)
const savingDelete = ref(false)
const deleteError = ref('')

function openDeleteComp(comp) {
  deletingComp.value = comp
  deleteError.value = ''
  showDeleteModal.value = true
}

/**

 * Confirma y procesa la eliminación de un registro mediante la API.

 */

async function confirmDelete() {
  deleteError.value = ''
  savingDelete.value = true
  try {
    const res = await fetch(`${API}/componentes?id=${deletingComp.value.id}`, {
      method: 'DELETE',
      headers: { Accept: 'application/json', Authorization: `Bearer ${getToken()}` }
    })
    const data = await res.json()
    if (!res.ok) {
      deleteError.value = data.message ?? 'Error al eliminar el componente'
      return
    }
    await fetchComponents()
    showDeleteModal.value = false
    toast.success('Componente eliminado exitosamente')
  } catch (e) {
    deleteError.value = 'Error de conexión'
    toast.error('Error de conexión')
  } finally {
    savingDelete.value = false
  }
}

onMounted(() => {
  fetchComponents()
  fetchProveedores()
  fetchCategoriasBase()
  document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Bloquear caracteres no numéricos en inputs de precio (permite punto decimal)
function blockInvalidChars(e) {
  if (['e', 'E', '+', '-'].includes(e.key)) {
    e.preventDefault()
  }
}

// Bloquear caracteres no numéricos en inputs de stock (solo enteros)
function blockInvalidCharsStock(e) {
  if (['e', 'E', '+', '-', '.', ','].includes(e.key)) {
    e.preventDefault()
  }
}
</script>
