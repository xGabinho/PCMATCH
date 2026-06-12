<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuditLog;
use App\Models\Componente;

class ComponenteController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // Helpers internos para determinar rol del usuario autenticado
    // ──────────────────────────────────────────────────────────────

    private function resolverRol($user): string
    {
        $clase = get_class($user);
        if ($clase === \App\Models\Usuario::class)   return $user->rol;
        if ($clase === \App\Models\Proveedor::class)  return 'proveedor';
        if ($clase === \App\Models\Bodega::class)     return 'bodega';
        return 'cliente';
    }

    // ══════════════════════════════════════════════════════════════
    // RF-16 – Consultar componentes
    // ══════════════════════════════════════════════════════════════

    /**
     * GET /api/componentes/admin
     * 
     * RF-16 RN01: Solo admin o superadmin (Inventory_Manager).
     * RF-16 RN02: Filtros dinámicos (buscar, categoria, estado, rango precio, con_stock, gama).
     * RF-16 RN03: Data table con relaciones cargadas (with()).
     */
    public function indexAdmin(Request $request)
    {
        // ── RN01: Control de acceso ──────────────────────────────
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Solo administradores pueden consultar componentes.'], 403);
        }

        // ── RN03: Query con relaciones cargadas ──────────────────
        $query = Componente::with(['bodega:id,nombre,activa', 'producto:id,nombre,categoria']);

        // ── RN02: Filtros dinámicos ──────────────────────────────
        // Búsqueda por nombre/especificación
        if ($request->filled('buscar')) {
            $query->buscar($request->query('buscar'));
        }

        // Filtro por categoría del producto
        if ($request->filled('categoria')) {
            $query->porCategoria($request->query('categoria'));
        }

        // Filtro por estado (activo/inactivo)
        if ($request->filled('estado')) {
            $estado = strtolower($request->query('estado'));
            if ($estado === 'activo') {
                $query->activo();
            } elseif ($estado === 'inactivo') {
                $query->inactivo();
            }
        }

        // Filtro por rango de precio
        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        if ($minPrice !== null || $maxPrice !== null) {
            $query->rangoPrecio($minPrice, $maxPrice);
        }

        // Scope booleano: solo con stock disponible
        if ($request->boolean('con_stock')) {
            $query->conStock();
        }

        // Filtro por gama
        if ($request->filled('gama')) {
            $query->porGama($request->query('gama'));
        }

        // Filtros avanzados
        if ($request->filled('nucleos')) $query->porNucleos($request->query('nucleos'));
        if ($request->filled('hilos')) $query->porHilos($request->query('hilos'));
        if ($request->filled('frecuencia_min') || $request->filled('frecuencia_max')) {
            $query->porFrecuencia($request->query('frecuencia_min'), $request->query('frecuencia_max'));
        }
        if ($request->filled('enfoque_uso')) $query->porEnfoque($request->query('enfoque_uso'));

        // Ordenar y obtener
        $componentes = $query->orderBy('id', 'ASC')->get();

        // ── RN03: Transformar para Data Table ────────────────────
        $resultado = $componentes->map(function ($c) {
            return [
                'id'             => $c->id,
                'sku'            => $c->sku,
                'nombre'         => $c->producto->nombre ?? '—',
                'categoria'      => $c->producto->categoria ?? '—',
                'especificacion' => $c->especificacion,
                'nucleos'        => $c->nucleos,
                'hilos'          => $c->hilos,
                'frecuencia_hz'  => $c->frecuencia_hz,
                'enfoque_uso'    => $c->enfoque_uso,
                'gama'           => $c->gama,
                'precio'         => $c->precio,
                'stock'          => $c->stock,
                'bodega_nombre'  => $c->bodega->nombre ?? '—',
                'bodega_id'      => $c->bodega_id,
                'activo'         => $c->activo,
                'created_at'     => $c->created_at,
            ];
        });

        return response()->json([
            'componentes' => $resultado
        ]);
    }

    /**
     * GET /api/componentes/maestros
     * Retorna los componentes donde bodega_id es nulo (Maestros)
     */
    public function maestros(Request $request)
    {
        $query = Componente::with(['producto:id,nombre,categoria'])
            ->whereNull('bodega_id')
            ->activo();

        if ($request->filled('categoria')) {
            $query->porCategoria($request->query('categoria'));
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->query('buscar'));
        }

        $componentes = $query->orderBy('id', 'ASC')->get();

        $resultado = $componentes->map(function ($c) {
            return [
                'id'             => $c->id,
                'nombre'         => $c->producto->nombre ?? '—',
                'categoria'      => $c->producto->categoria ?? '—',
                'especificacion' => $c->especificacion,
                'nucleos'        => $c->nucleos,
                'hilos'          => $c->hilos,
                'frecuencia_hz'  => $c->frecuencia_hz,
                'enfoque_uso'    => $c->enfoque_uso,
                'gama'           => $c->gama,
            ];
        });

        return response()->json(['componentes' => $resultado]);
    }

    /**
     * GET /api/componentes — Lista los componentes de la bodega autenticada
     */
    public function indexBodega(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        $query = Componente::with(['producto:id,nombre,categoria'])
            ->select('id', 'sku', 'producto_id', 'especificacion', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama', 'precio', 'stock', 'bodega_id', 'activo');

        if ($rol === 'bodega') {
            $query->where('bodega_id', $user->id);
        } elseif ($rol === 'proveedor') {
            $bodegaIds = DB::table('bodegas')->where('proveedor_id', $user->id)->pluck('id');
            $query->whereIn('bodega_id', $bodegaIds);
        }

        // Filtros dinámicos también para esta vista
        if ($request->filled('buscar')) {
            $query->buscar($request->query('buscar'));
        }
        if ($request->filled('categoria')) {
            $query->porCategoria($request->query('categoria'));
        }
        if ($request->boolean('con_stock')) {
            $query->conStock();
        }

        if ($request->filled('gama')) {
            $query->porGama($request->query('gama'));
        }
        
        // Filtros avanzados
        if ($request->filled('nucleos')) $query->porNucleos($request->query('nucleos'));
        if ($request->filled('hilos')) $query->porHilos($request->query('hilos'));
        if ($request->filled('frecuencia_min') || $request->filled('frecuencia_max')) {
            $query->porFrecuencia($request->query('frecuencia_min'), $request->query('frecuencia_max'));
        }
        if ($request->filled('enfoque_uso')) $query->porEnfoque($request->query('enfoque_uso'));

        $componentes = $query->orderBy('id', 'DESC')->get();

        $resultado = $componentes->map(function ($c) {
            return [
                'id'             => $c->id,
                'sku'            => $c->sku,
                'nombre'         => $c->producto->nombre ?? '—',
                'categoria'      => $c->producto->categoria ?? '—',
                'especificacion' => $c->especificacion,
                'nucleos'        => $c->nucleos,
                'hilos'          => $c->hilos,
                'frecuencia_hz'  => $c->frecuencia_hz,
                'enfoque_uso'    => $c->enfoque_uso,
                'gama'           => $c->gama,
                'precio'         => $c->precio,
                'stock'          => $c->stock,
                'bodega_id'      => $c->bodega_id,
                'activo'         => $c->activo,
            ];
        });

        return response()->json(['componentes' => $resultado]);
    }

    /**
     * GET /api/componentes/publico — Catálogo público (sin autenticación)
     */
    public function indexPublic(Request $request)
    {
        $query = Componente::with(['producto:id,nombre,categoria', 'bodega:id,nombre'])
            ->activo()
            ->conStock()
            ->whereHas('bodega', function ($q) {
                $q->where('activa', 1);
            });

        if ($request->filled('categoria')) {
            $query->porCategoria($request->query('categoria'));
        }

        if ($request->filled('buscar')) {
            $query->buscar($request->query('buscar'));
        }

        $componentes = $query->orderBy('id', 'ASC')->get();

        $resultado = $componentes->map(function ($c) {
            return [
                'id'             => $c->id,
                'sku'            => $c->sku,
                'nombre'         => $c->producto->nombre ?? '—',
                'categoria'      => $c->producto->categoria ?? '—',
                'especificacion' => $c->especificacion,
                'gama'           => $c->gama,
                'precio'         => $c->precio,
                'stock'          => $c->stock,
                'bodega'         => $c->bodega->nombre ?? '—',
            ];
        });

        return response()->json(['componentes' => $resultado]);
    }

    // ══════════════════════════════════════════════════════════════
    // RF-15 – Registrar componente
    // ══════════════════════════════════════════════════════════════

    /**
     * POST /api/componentes — Crear un componente nuevo
     * 
     * RN01: Campos obligatorios validados estrictamente.
     * RN02: Generación de SKU único + unique compuesto (bodega_id, producto_id, especificacion).
     * RN03: Solo si la bodega/proveedor está activa.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado para crear componentes'], 403);
        }

        if (in_array($rol, ['admin', 'superadmin'])) {
            // Admin crea componente maestro (no requiere bodega)
            $validator = Validator::make($request->all(), [
                'producto_id'    => 'required|integer|exists:productos_catalogo,id',
                'especificacion' => 'required|string|max:1000',
                'nucleos'        => 'nullable|integer|min:1',
                'hilos'          => 'nullable|integer|min:1',
                'frecuencia_hz'  => 'nullable|numeric|min:0',
                'enfoque_uso'    => 'nullable|in:estudio,oficina,gaming,diseño',
                'gama'           => 'required|in:alta,media,baja',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $sku = Componente::generarSku($request->input('producto_id'), 0); // 0 indica maestro

            $componente = Componente::create([
                'sku'            => $sku,
                'bodega_id'      => null, // Maestro
                'producto_id'    => $request->input('producto_id'),
                'especificacion' => trim($request->input('especificacion')),
                'nucleos'        => $request->input('nucleos'),
                'hilos'          => $request->input('hilos'),
                'frecuencia_hz'  => $request->input('frecuencia_hz'),
                'enfoque_uso'    => $request->input('enfoque_uso'),
                'gama'           => $request->input('gama'),
                'precio'         => 0,
                'stock'          => 0,
                'activo'         => 1,
            ]);

            $producto = DB::table('productos_catalogo')->where('id', $request->input('producto_id'))->first();
            $nombreProducto = $producto ? $producto->nombre : "ID {$request->input('producto_id')}";
            AuditLog::log($request, "Agregó el componente maestro «{$nombreProducto}» (Gama: {$request->input('gama')}, SKU: {$sku})", 'Componentes');

            return response()->json([
                'message' => 'Componente maestro registrado correctamente',
                'id'      => $componente->id,
                'sku'     => $sku,
            ], 201);
        } else {
            // Bodega/Proveedor asocia un componente maestro a su inventario
            $validator = Validator::make($request->all(), [
                'master_component_id' => 'required|integer|exists:componentes,id',
                'bodega_id'           => 'required|integer|exists:bodegas,id',
                'precio'              => 'required|numeric|min:0',
                'stock'               => 'required|integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $master = Componente::whereNull('bodega_id')->where('id', $request->input('master_component_id'))->first();
            if (!$master) {
                return response()->json(['success' => false, 'message' => 'El componente maestro seleccionado no existe'], 404);
            }

            $bodega_id = $request->input('bodega_id');

            // Si es bodega, se auto-asigna su propio ID
            if ($rol === 'bodega') {
                $bodega_id = $user->id;
            }

            // Si es proveedor, verificar que la bodega le pertenezca
            if ($rol === 'proveedor') {
                $bodega = DB::table('bodegas')->where('id', $bodega_id)->where('proveedor_id', $user->id)->first();
                if (!$bodega) {
                    return response()->json(['success' => false, 'message' => 'Esta bodega no te pertenece o no existe'], 403);
                }
            }

            $bodega = DB::table('bodegas')->where('id', $bodega_id)->first();
            if (!$bodega) {
                return response()->json(['success' => false, 'message' => 'La bodega especificada no existe'], 404);
            }

            if (!$bodega->activa) {
                return response()->json(['success' => false, 'message' => 'No se puede registrar el componente. La bodega está inactiva.'], 403);
            }

            if ($bodega->proveedor_id) {
                $proveedor = DB::table('proveedores')->where('id', $bodega->proveedor_id)->first();
                if ($proveedor && (!$proveedor->activo || $proveedor->estado_aprobacion !== 'aprobado')) {
                    return response()->json(['success' => false, 'message' => 'No se puede registrar el componente. El proveedor no está activo/aprobado.'], 403);
                }
            }

            // Verificar duplicado en la misma bodega
            $duplicado = Componente::withTrashed()
                ->where('bodega_id', $bodega_id)
                ->where('producto_id', $master->producto_id)
                ->where('especificacion', $master->especificacion)
                ->first();

            if ($duplicado) {
                return response()->json(['success' => false, 'message' => 'Este componente ya existe en tu inventario.'], 409);
            }

            $sku = Componente::generarSku($master->producto_id, $bodega_id);

            $componente = Componente::create([
                'sku'            => $sku,
                'bodega_id'      => $bodega_id,
                'producto_id'    => $master->producto_id,
                'especificacion' => $master->especificacion,
                'nucleos'        => $master->nucleos,
                'hilos'          => $master->hilos,
                'frecuencia_hz'  => $master->frecuencia_hz,
                'enfoque_uso'    => $master->enfoque_uso,
                'gama'           => $master->gama,
                'precio'         => $request->input('precio'),
                'stock'          => $request->input('stock'),
                'activo'         => 1,
            ]);

            $producto = DB::table('productos_catalogo')->where('id', $master->producto_id)->first();
            $nombreProducto = $producto ? $producto->nombre : "ID {$master->producto_id}";
            $bodegaNombre = $bodega->nombre ?? "ID {$bodega_id}";

            AuditLog::log($request, "Agregó el componente «{$nombreProducto}» (Gama: {$master->gama}, SKU: {$sku}) a la bodega «{$bodegaNombre}»", 'Componentes');

            return response()->json([
                'message' => 'Componente agregado al inventario correctamente',
                'id'      => $componente->id,
                'sku'     => $sku,
            ], 201);
        }

    }

    // ══════════════════════════════════════════════════════════════
    // RF-17 – Modificar componente (ya existente, se mantiene)
    // ══════════════════════════════════════════════════════════════

    /**
     * PUT /api/componentes — Editar componente
     * 
     * RF-17 – Gestión de Componentes – Modificar componente
     * RN01: Solo admin, superadmin, bodega o proveedor pueden modificar
     * RN02: Validación estricta (precio > 0, stock >= 0, gama válida)
     * RN03: Registro detallado de cambios en historial de auditoría
     */
    public function update(Request $request)
    {
        // ── RN01: Verificar permiso de modificación ──────────────
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Solo administradores, proveedores y bodegas pueden modificar componentes.'
            ], 403);
        }

        $id = $request->input('id');
        if (!$id) return response()->json(['success' => false, 'message' => 'id es requerido'], 400);

        $comp = DB::table('componentes')->where('id', $id)->whereNull('deleted_at')->first();
        if (!$comp) return response()->json(['success' => false, 'message' => 'Componente no encontrado'], 404);

        // Verificar que la bodega pertenezca al usuario (si es bodega)
        if ($rol === 'bodega' && $comp->bodega_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'No puedes editar componentes de otra bodega'], 403);
        }

        // Verificar que la bodega pertenezca al proveedor (si es proveedor)
        if ($rol === 'proveedor') {
            $bodega = DB::table('bodegas')->where('id', $comp->bodega_id)->where('proveedor_id', $user->id)->first();
            if (!$bodega) {
                return response()->json(['success' => false, 'message' => 'No puedes editar componentes de bodegas que no te pertenecen'], 403);
            }
        }

        // ── RN02: Validación estricta de datos actualizados ──────
        $validator = Validator::make($request->all(), [
            'especificacion' => 'nullable|string|max:1000',
            'nucleos'        => 'nullable|integer|min:1',
            'hilos'          => 'nullable|integer|min:1',
            'frecuencia_hz'  => 'nullable|numeric|min:0',
            'enfoque_uso'    => 'nullable|in:estudio,oficina,gaming,diseño',
            'gama'           => 'nullable|in:alta,media,baja',
            'precio'         => 'nullable|numeric|gt:0',
            'stock'          => 'nullable|integer|min:0',
            'activo'         => 'nullable|boolean',
        ], [
            'gama.in'        => 'La gama debe ser alta, media o baja',
            'precio.gt'      => 'El precio debe ser mayor a cero',
            'precio.numeric' => 'El precio debe ser un valor numérico',
            'stock.min'      => 'La cantidad en stock no puede ser negativa',
            'stock.integer'  => 'La cantidad en stock debe ser un número entero',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        // ── RN03: Registrar cambios con detalle (auditoría) ──────
        $data = [];
        $cambios = [];
        $labels = [
            'especificacion' => 'Especificación',
            'nucleos'        => 'Núcleos',
            'hilos'          => 'Hilos',
            'frecuencia_hz'  => 'Frecuencia (GHz)',
            'enfoque_uso'    => 'Enfoque de uso',
            'gama'           => 'Gama',
            'precio'         => 'Precio',
            'stock'          => 'Stock',
            'activo'         => 'Estado',
        ];

        foreach (['especificacion', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama', 'precio', 'stock', 'activo'] as $campo) {
            if ($request->has($campo)) {
                $nuevo = $request->input($campo);
                $viejo = $comp->$campo;
                $data[$campo] = $nuevo;

                if ((string) $viejo !== (string) $nuevo) {
                    $label = $labels[$campo];
                    if ($campo === 'activo') {
                        $viejoLabel = $viejo ? 'Activo' : 'Inactivo';
                        $nuevoLabel = $nuevo ? 'Activo' : 'Inactivo';
                        $cambios[] = "{$label}: {$viejoLabel} → {$nuevoLabel}";
                        $data[$campo] = (bool) $nuevo; // Explicitly cast to boolean to avoid PostgreSQL integer error
                    } elseif ($campo === 'precio') {
                        $cambios[] = "{$label}: \$" . number_format((float)$viejo, 0, ',', '.') . " → \$" . number_format((float)$nuevo, 0, ',', '.');
                    } elseif ($campo === 'gama') {
                        $cambios[] = "{$label}: " . ucfirst($viejo) . " → " . ucfirst($nuevo);
                    } else {
                        $cambios[] = "{$label}: '{$viejo}' → '{$nuevo}'";
                    }
                } else if ($campo === 'activo') {
                    $data[$campo] = (bool) $nuevo; // Make sure it's boolean even if unchanged, if included in data
                }
            }
        }

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No se enviaron campos para actualizar'], 400);
        }

        DB::table('componentes')->where('id', $id)->update($data);

        // Obtener el nombre del producto para el log de auditoría
        $producto = DB::table('productos_catalogo')
            ->join('componentes', 'componentes.producto_id', '=', 'productos_catalogo.id')
            ->where('componentes.id', $id)
            ->select('productos_catalogo.nombre')
            ->first();
        $nombreProducto = $producto ? $producto->nombre : "ID {$id}";

        $detalles = empty($cambios) ? 'Sin cambios' : implode(' · ', $cambios);
        AuditLog::log($request, "Editó el componente «{$nombreProducto}» — {$detalles}", 'Componentes');

        return response()->json(['message' => 'Componente actualizado correctamente']);
    }

    // ══════════════════════════════════════════════════════════════
    // RF-18 – Eliminar componente
    // ══════════════════════════════════════════════════════════════

    /**
     * DELETE /api/componentes — Eliminar componente (borrado lógico)
     * 
     * RN01: SoftDeletes — el registro se preserva con deleted_at.
     * RN02: Bloquear si stock > 0 o tiene cotizacion_items activos.
     * RN03: Solo el rol 'admin' puede ejecutar destroy.
     */
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        // ── RN03: Solo admin o superadmin puede eliminar ──────────────────────
        if (!in_array($rol, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Solo el rol Admin o SuperAdmin puede eliminar componentes.'
            ], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        // Buscar componente (no eliminado)
        $componente = Componente::find($id);
        if (!$componente) {
            return response()->json(['success' => false, 'message' => 'Componente no encontrado'], 404);
        }

        // ── RN02: Protección de integridad ───────────────────────
        // Bloquear si stock > 0
        if ($componente->stock > 0) {
            return response()->json([
                'success' => false,
                'message' => "No se puede eliminar el componente. Aún tiene {$componente->stock} unidades en stock. Reduzca el stock a 0 antes de eliminar."
            ], 409);
        }

        // Bloquear si tiene cotizacion_items activos vinculados
        if ($componente->tieneRelacionesActivas()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el componente. Tiene cotizaciones asociadas que dependen de él.'
            ], 409);
        }

        // ── RN01: Borrado lógico (SoftDelete) ────────────────────
        $producto = DB::table('productos_catalogo')->where('id', $componente->producto_id)->first();
        $nombreProducto = $producto ? $producto->nombre : "ID {$componente->id}";

        $componente->delete(); // SoftDeletes: llena deleted_at en vez de borrar

        AuditLog::log($request, "Eliminó el componente «{$nombreProducto}» (SKU: {$componente->sku}, ID: {$id})", 'Componentes');

        return response()->json(['message' => 'Componente eliminado correctamente (borrado lógico)']);
    }

    // ══════════════════════════════════════════════════════════════
    // RF-19 – Ajustar stock por cantidad específica
    // ══════════════════════════════════════════════════════════════

    /**
     * PATCH /api/componentes/stock — Incrementar o decrementar stock
     *
     * Recibe: { id, cantidad, operacion: 'incrementar' | 'decrementar' }
     * Valida que el stock no quede negativo en decrementos.
     */
    public function adjustStock(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado para ajustar stock.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id'        => 'required|integer',
            'cantidad'  => 'required|integer|min:1',
            'operacion' => 'required|in:incrementar,decrementar',
        ], [
            'id.required'        => 'El ID del componente es requerido',
            'cantidad.required'  => 'La cantidad es requerida',
            'cantidad.integer'   => 'La cantidad debe ser un número entero',
            'cantidad.min'       => 'La cantidad debe ser al menos 1',
            'operacion.required' => 'La operación es requerida',
            'operacion.in'       => 'La operación debe ser incrementar o decrementar',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $comp = DB::table('componentes')->where('id', $request->input('id'))->whereNull('deleted_at')->first();
        if (!$comp) {
            return response()->json(['success' => false, 'message' => 'Componente no encontrado'], 404);
        }

        // Verificar propiedad si es bodega
        if ($rol === 'bodega' && $comp->bodega_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'No puedes ajustar stock de otra bodega'], 403);
        }

        // Verificar propiedad si es proveedor
        if ($rol === 'proveedor') {
            $bodega = DB::table('bodegas')->where('id', $comp->bodega_id)->where('proveedor_id', $user->id)->first();
            if (!$bodega) {
                return response()->json(['success' => false, 'message' => 'No puedes ajustar stock de bodegas que no te pertenecen'], 403);
            }
        }

        $cantidad = (int) $request->input('cantidad');
        $operacion = $request->input('operacion');
        $stockActual = (int) $comp->stock;

        if ($operacion === 'decrementar') {
            if ($stockActual < $cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => "Stock insuficiente. Stock actual: {$stockActual}, cantidad a retirar: {$cantidad}"
                ], 422);
            }
            $nuevoStock = $stockActual - $cantidad;
        } else {
            $nuevoStock = $stockActual + $cantidad;
        }

        DB::table('componentes')->where('id', $comp->id)->update(['stock' => $nuevoStock]);

        // Auditoría
        $producto = DB::table('productos_catalogo')
            ->join('componentes', 'componentes.producto_id', '=', 'productos_catalogo.id')
            ->where('componentes.id', $comp->id)
            ->select('productos_catalogo.nombre')
            ->first();
        $nombreProducto = $producto ? $producto->nombre : "ID {$comp->id}";

        $signo = $operacion === 'incrementar' ? '+' : '-';
        AuditLog::log($request, "Ajustó stock de «{$nombreProducto}» ({$signo}{$cantidad}) → {$nuevoStock} unidades", 'Componentes');

        return response()->json([
            'success'    => true,
            'message'    => 'Stock actualizado correctamente',
            'nuevo_stock' => $nuevoStock,
        ]);
    }
}
