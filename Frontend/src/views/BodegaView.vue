<template>
  <div class="flex h-screen overflow-hidden theme-bg">

    <!-- Sidebar -->
    <aside class="w-60 border-r theme-border flex-shrink-0 flex flex-col h-screen overflow-y-auto sticky top-0">
      <div class="h-16 px-5 flex items-center border-b theme-border gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">🏪</div>
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
              <span class="text-yellow-400">⚠️</span>
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
                  <td class="px-6 py-4 text-sm text-accent font-mono font-semibold">${{ Number(comp.precio).toLocaleString() }}</td>
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

      </div>
    </main>

    <!-- ==== MODAL AÑADIR COMPONENTE ==== -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="closeAddModal"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-lg my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Añadir componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">Registra un nuevo producto en tu inventario</p>
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
              <span><Check class="w-4 h-4 inline-block mr-1" /></span> Categoría: {{ newComp.categoria }}
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

        <p v-if="addError" class="mt-5 text-xs text-red-400 bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-2.5 text-center">{{ addError }}</p>

        <div class="flex gap-3 mt-8">
          <button @click="saveNewComp" :disabled="savingAdd" class="btn-primary flex-1 text-sm">
            {{ savingAdd ? 'Guardando...' : 'Crear componente' }}
          </button>
          <button @click="closeAddModal" class="btn-secondary text-sm px-5">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- ===== MODAL EDITAR COMPONENTE ===== -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-6 px-4">
      <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showEditModal = false"></div>
      <div class="relative card-dark rounded-2xl p-6 w-full max-w-3xl my-auto shadow-2xl">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h2 class="text-lg font-bold theme-text">Editar componente</h2>
            <p class="text-xs theme-text-muted mt-0.5">{{ editingComp.nombre }}</p>
          </div>
          <button @click="showEditModal = false" class="theme-text-muted hover:theme-text transition-colors text-2xl leading-none w-8 h-8 flex items-center justify-center rounded-lg hover:theme-bg">×</button>
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
import { Check, Trash2, Sun, Moon, Wrench, Store, Settings } from '@lucide/vue'
import { useTheme } from '../composables/useTheme'
const { isDark, toggleTheme } = useTheme()
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { useToast } from '../composables/useToast'
import { ref, markRaw, computed, onMounted, onBeforeUnmount } from 'vue'

import { API } from '@/config/api'

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
  { id: 'dashboard',   icon: '📊', label: 'Dashboard',       description: 'Resumen de tu bodega',          count: null },
  { id: 'componentes', icon: markRaw(Wrench), label: 'Mis componentes', description: 'Gestiona tu catálogo y stock',  count: true },
]
const currentSection = computed(() => sections.find(s => s.id === activeSection.value))

const categories = ['CPU', 'GPU', 'RAM', 'Storage', 'PSU', 'Motherboard', 'Cooler', 'Case']

const tierStyles = {
  alta:  'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
  media: 'bg-blue-500/10 text-blue-400 border-blue-500/20',
  baja:  'bg-zinc-500/10 text-zinc-400 border-zinc-500/20',
}

const tiers = [
  { id: 'alta',  icon: '🚀', label: 'Alta gama',  desc: 'Top rendimiento',       activeBorder: 'border-purple-500/60', activeBg: 'bg-purple-500/10', activeText: 'text-purple-400' },
  { id: 'media', icon: '⚡', label: 'Gama media', desc: 'Relación precio-valor',  activeBorder: 'border-accent/60',    activeBg: 'bg-accent/10',    activeText: 'text-accent'     },
  { id: 'baja',  icon: '💡', label: 'Gama baja',  desc: 'Económico y funcional', activeBorder: 'border-green-500/60', activeBg: 'bg-green-500/10', activeText: 'text-green-400'  },
]

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
const catalogo = ref([])
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

// Cerrar dropdown al hacer click fuera
function handleClickOutside(e) {
  if (!e.target.closest('.relative')) showProductoDropdown.value = false
}

const showAddModal = ref(false)
const addError = ref('')
const savingAdd = ref(false)
const newComp = ref({ producto_id: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '' })
const addImageFile = ref(null)
const addImagePreview = ref(null)
const addFileName = ref('')

const categoriasBase = ref([])

async function fetchCategoriasBase() {
  try {
    const res = await fetch(`${API}/catalogo`, { headers: { Authorization: `Bearer ${getToken()}` } })
    const data = await res.json()
    if (res.ok) categoriasBase.value = data.productos
  } catch(e) { console.error(e) }
}

function openAddModal() {
  newComp.value = { producto_id: '', especificacion: '', nucleos: '', hilos: '', frecuencia_hz: '', enfoque_uso: '', gama: 'media', precio: '', stock: '' }
  addImageFile.value = null
  addImagePreview.value = null
  addFileName.value = ''
  addError.value = ''
  if (categoriasBase.value.length === 0) fetchCategoriasBase()
  showAddModal.value = true
}

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

async function saveNewComp() {
  addError.value = ''
  if (!newComp.value.producto_id || !newComp.value.especificacion || !newComp.value.gama || !newComp.value.precio || newComp.value.stock === '') {
    return addError.value = 'El producto, especificación, gama, precio y stock son requeridos'
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

async function saveEditComp() {
  editError.value = ''
  
  if (editingComp.value.precio !== undefined && Number(editingComp.value.precio) <= 0) {
    return editError.value = 'El precio debe ser mayor a 0'
  }
  if (editingComp.value.stock !== undefined && editingComp.value.stock !== '' && !Number.isInteger(Number(editingComp.value.stock))) {
    return editError.value = 'El stock debe ser un número entero sin decimales'
  }
  if (editingComp.value.stock !== undefined && Number(editingComp.value.stock) < 0) {
    return editError.value = 'El stock no puede ser negativo'
  }

  savingEdit.value = true
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

// ── Lifecycle ─────────────────────────────────────────────
onMounted(() => {
  fetchComponents()
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
