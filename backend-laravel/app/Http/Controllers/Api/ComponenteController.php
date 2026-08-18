<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Helpers\AuditLog;
use App\Models\Componente;

class ComponenteController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // Helpers internos para determinar rol del usuario autenticado
    // ──────────────────────────────────────────────────────────────

    private function resolverRol($user): string
    {
        if (!$user) return 'cliente';
        $clase = get_class($user);
        if ($clase === \App\Models\Usuario::class)   return $user->rol ?? 'cliente';
        if ($clase === \App\Models\Proveedor::class)  return 'proveedor';
        if ($clase === \App\Models\Bodega::class)     return 'bodega';
        return 'cliente';
    }

    // ══════════════════════════════════════════════════════════════
    // RF-16 – Consultar componentes
    // ══════════════════════════════════════════════════════════════

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
     */

    
    public function indexAdmin(Request $request)
    {
        // ── RN01: Control de acceso ──────────────────────────────
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Solo administradores pueden consultar componentes.'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('componentes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.ver'], 403);
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
                'descuento_porcentaje' => $c->descuento_porcentaje,
                'descuento_activo' => $c->descuento_activo,
                'precio_final'   => $c->precio_final,
                'stock'          => $c->stock,
                'bodega_nombre'  => $c->bodega->nombre ?? '—',
                'bodega_id'      => $c->bodega_id,
                'activo'         => $c->activo,
                'imagen_url'     => $c->imagen_url,
                'created_at'     => $c->created_at,
            ];
        });

        return response()->json([
            'componentes' => $resultado
        ]);
    }

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
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
                'imagen_url'     => $c->imagen_url,
            ];
        });

        return response()->json(['componentes' => $resultado]);
    }

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
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
                'descuento_porcentaje' => $c->descuento_porcentaje,
                'descuento_activo' => $c->descuento_activo,
                'precio_final'   => $c->precio_final,
                'stock'          => $c->stock,
                'bodega_id'      => $c->bodega_id,
                'activo'         => $c->activo,
                'imagen_url'     => $c->imagen_url,
            ];
        });

        return response()->json(['componentes' => $resultado]);
    }

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
     */

    
    public function indexPublic(Request $request)
    {
        $cacheKey = 'comp_pub_' . md5(json_encode($request->all()));

        $resultado = Cache::remember($cacheKey, 30, function () use ($request) {
            $query = Componente::with(['producto:id,nombre,categoria', 'bodega:id,nombre'])
                ->activo()
                ->conStock()
                ->where(function ($q) {
                    $q->whereNull('bodega_id')
                      ->orWhereHas('bodega', function ($sub) {
                          $sub->whereRaw("activa IS TRUE");
                      });
                });

            if ($request->filled('categoria')) {
                $query->porCategoria($request->query('categoria'));
            }

            if ($request->filled('buscar')) {
                $query->buscar($request->query('buscar'));
            }

            $componentes = $query->orderBy('id', 'ASC')->get();

            return $componentes->map(function ($c) {
                return [
                    'id'             => $c->id,
                    'sku'            => $c->sku,
                    'nombre'         => $c->producto->nombre ?? '—',
                    'categoria'      => $c->producto->categoria ?? '—',
                    'especificacion' => $c->especificacion,
                    'gama'           => $c->gama,
                    'precio'         => $c->precio,
                    'descuento_porcentaje' => $c->descuento_porcentaje,
                    'descuento_activo' => $c->descuento_activo,
                    'precio_final'   => $c->precio_final,
                    'stock'          => $c->stock,
                    'bodega'         => $c->bodega->nombre ?? '—',
                    'imagen_url'     => $c->imagen_url,
                ];
            });
        });

        return response()->json(['componentes' => $resultado]);
    }

    // ══════════════════════════════════════════════════════════════
    // RF-15 – Registrar componente
    // ══════════════════════════════════════════════════════════════

    
    /**

    
     * Almacena un nuevo registro en la base de datos.

    
     * Valida la información recibida en la petición HTTP y crea el nuevo recurso.

    
     */

    
    public function store(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        // Fix decimal commas for numeric fields
        foreach (['frecuencia_hz', 'precio'] as $numField) {
            if ($request->has($numField) && is_string($request->input($numField))) {
                $request->merge([$numField => str_replace(',', '.', $request->input($numField))]);
            }
        }

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado para crear componentes'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('componentes.crear')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.crear'], 403);
        }

        $isFromMaster = $request->has('master_component_id') && $request->input('master_component_id') !== null && $request->input('master_component_id') !== '';

        if ($isFromMaster) {
            // Bodega/Proveedor asocia un componente maestro a su inventario
            $validator = Validator::make($request->all(), [
                'master_component_id' => 'required|integer|exists:productos_catalogo,id',
                'bodega_id'           => 'nullable|integer|exists:bodegas,id',
                'proveedor_id'        => 'nullable|integer|exists:proveedores,id',
                'precio'              => 'required|numeric|min:0',
                'descuento_porcentaje'=> 'nullable|numeric|min:0|max:100',
                'descuento_activo'    => 'nullable|boolean',
                'stock'               => 'required|integer|min:0',
                'imagen'              => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $master = DB::table('productos_catalogo')->where('id', $request->input('master_component_id'))->first();
            if (!$master) {
                return response()->json(['success' => false, 'message' => 'El producto del catálogo no existe'], 404);
            }

            $bodega_id = $request->input('bodega_id');
            if ($rol === 'bodega') {
                $bodega_id = $user->id;
            }

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

            // Descontar stock del proveedor si aplica
            $cantidadAImportar = (int) $request->input('stock');
            if ($bodega->proveedor_id) {
                $proveedorCatalogoItem = DB::table('proveedor_producto_catalogo')
                    ->where('proveedor_id', $bodega->proveedor_id)
                    ->where('producto_catalogo_id', $master->id)
                    ->first();
                
                if ($proveedorCatalogoItem) {
                    if ($proveedorCatalogoItem->stock < $cantidadAImportar) {
                        return response()->json(['success' => false, 'message' => "El proveedor no tiene suficiente stock. Stock disponible: {$proveedorCatalogoItem->stock}"], 400);
                    }
                    
                    DB::table('proveedor_producto_catalogo')
                        ->where('id', $proveedorCatalogoItem->id)
                        ->decrement('stock', $cantidadAImportar);
                }
            }

            $duplicado = Componente::withTrashed()
                ->where('bodega_id', $bodega_id)
                ->where('producto_id', $master->id)
                ->first();

            if ($duplicado) {
                return response()->json(['success' => false, 'message' => 'Este componente ya existe en tu inventario.'], 409);
            }

            $imagenUrl = null;
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('componentes', 'supabase');
                $imagenUrl = Storage::disk('supabase')->url($path);
            } elseif ($master->imagen_url) {
                $imagenUrl = $master->imagen_url;
            }

            $sku = Componente::generarSku($master->id, $bodega_id);

            $componente = Componente::create([
                'sku'            => $sku,
                'bodega_id'      => $bodega_id,
                'proveedor_id'   => $request->input('proveedor_id'),
                'producto_id'    => $master->id,
                'especificacion' => $master->especificacion,
                'nucleos'        => $master->nucleos,
                'hilos'          => $master->hilos,
                'frecuencia_hz'  => $master->frecuencia_hz,
                'enfoque_uso'    => $master->enfoque_uso,
                'gama'           => $master->gama,
                'precio'         => $request->input('precio'),
                'descuento_porcentaje' => $request->input('descuento_porcentaje', 0),
                'descuento_activo' => filter_var($request->input('descuento_activo', false), FILTER_VALIDATE_BOOLEAN) ? DB::raw('true') : DB::raw('false'),
                'stock'          => $request->input('stock'),
                'activo'         => DB::raw('true'),
                'imagen_url'     => $imagenUrl,
            ]);

            $nombreProducto = $master ? $master->nombre : "ID {$master->id}";
            $bodegaNombre = $bodega->nombre ?? "ID {$bodega_id}";

            AuditLog::log($request, "Agregó el componente «{$nombreProducto}» (Gama: {$master->gama}, SKU: {$sku}) a la bodega «{$bodegaNombre}»", 'Componentes');

            return response()->json([
                'message' => 'Componente agregado al inventario correctamente',
                'id'      => $componente->id,
                'sku'     => $sku,
            ], 201);
        } else {
            // Crear componente desde cero (para admin o bodega/proveedor que crea uno custom)
            $rules = [
                'producto_id'    => 'required|integer|exists:productos_catalogo,id',
                'especificacion' => 'required|string|max:1000',
                'nucleos'        => 'nullable|integer|min:1',
                'hilos'          => 'nullable|integer|min:1',
                'frecuencia_hz'  => 'nullable|numeric|min:0',
                'enfoque_uso'    => 'nullable|in:estudio,oficina,gaming,diseño',
                'gama'           => 'required|in:alta,media,baja',
                'imagen'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ];

            if (in_array($rol, ['bodega', 'proveedor'])) {
                $rules['precio'] = 'required|numeric|min:0';
                $rules['stock']  = 'required|integer|min:0';
                if ($rol === 'proveedor') {
                    $rules['bodega_id'] = 'required|integer|exists:bodegas,id';
                }
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
            }

            $bodega_id = null;
            $precio = 0;
            $stock = 0;

            if (in_array($rol, ['bodega', 'proveedor'])) {
                $bodega_id = $rol === 'bodega' ? $user->id : $request->input('bodega_id');
                $precio = $request->input('precio');
                $stock = $request->input('stock');

                $bodega = DB::table('bodegas')->where('id', $bodega_id)->first();
                if (!$bodega || !$bodega->activa) {
                    return response()->json(['success' => false, 'message' => 'La bodega está inactiva o no existe.'], 403);
                }
                if ($rol === 'proveedor' && $bodega->proveedor_id !== $user->id) {
                    return response()->json(['success' => false, 'message' => 'Esta bodega no te pertenece'], 403);
                }

                $duplicado = Componente::withTrashed()
                    ->where('bodega_id', $bodega_id)
                    ->where('producto_id', $request->input('producto_id'))
                    ->where('especificacion', trim($request->input('especificacion')))
                    ->first();

                if ($duplicado) {
                    return response()->json(['success' => false, 'message' => 'Este componente ya existe en tu inventario.'], 409);
                }
            }

            $sku = Componente::generarSku($request->input('producto_id'), $bodega_id ?? 0);

            $imagenUrl = null;
            if ($request->hasFile('imagen')) {
                $path = $request->file('imagen')->store('componentes', 'supabase');
                $imagenUrl = Storage::disk('supabase')->url($path);
            }

            $componente = Componente::create([
                'sku'            => $sku,
                'bodega_id'      => $bodega_id,
                'producto_id'    => $request->input('producto_id'),
                'especificacion' => trim($request->input('especificacion')),
                'nucleos'        => $request->input('nucleos'),
                'hilos'          => $request->input('hilos'),
                'frecuencia_hz'  => $request->input('frecuencia_hz'),
                'enfoque_uso'    => $request->input('enfoque_uso'),
                'gama'           => $request->input('gama'),
                'precio'         => $precio,
                'stock'          => $stock,
                'activo'         => DB::raw('true'),
                'imagen_url'     => $imagenUrl,
            ]);

            $producto = DB::table('productos_catalogo')->where('id', $request->input('producto_id'))->first();
            $nombreProducto = $producto ? $producto->nombre : "ID {$request->input('producto_id')}";

            if ($bodega_id) {
                AuditLog::log($request, "Agregó el componente personalizado «{$nombreProducto}» (Gama: {$request->input('gama')}, SKU: {$sku}) a la bodega ID {$bodega_id}", 'Componentes');
            } else {
                AuditLog::log($request, "Agregó el componente maestro «{$nombreProducto}» (Gama: {$request->input('gama')}, SKU: {$sku})", 'Componentes');
            }

            return response()->json([
                'message' => $bodega_id ? 'Componente personalizado registrado correctamente' : 'Componente maestro registrado correctamente',
                'id'      => $componente->id,
                'sku'     => $sku,
            ], 201);
        }

    }

    // ══════════════════════════════════════════════════════════════
    // RF-17 – Modificar componente (ya existente, se mantiene)
    // ══════════════════════════════════════════════════════════════

    
    /**

    
     * Actualiza un recurso existente en la base de datos.

    
     * Valida los nuevos datos y sobrescribe los valores del registro correspondiente.

    
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

        if ($rol === 'admin' && !$user->hasPermission('componentes.editar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.editar'], 403);
        }

        // Fix decimal commas for numeric fields
        foreach (['frecuencia_hz', 'precio'] as $numField) {
            if ($request->has($numField) && is_string($request->input($numField))) {
                $request->merge([$numField => str_replace(',', '.', $request->input($numField))]);
            }
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
            'descuento_porcentaje' => 'nullable|numeric|min:0|max:100',
            'descuento_activo' => 'nullable|boolean',
            'stock'          => 'nullable|integer|min:0',
            'activo'         => 'nullable|boolean',
            'imagen'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
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

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('componentes', 'supabase');
            $nuevoUrl = Storage::disk('supabase')->url($path);
            $data['imagen_url'] = $nuevoUrl;
            $cambios[] = "Imagen actualizada";
            
            if ($comp->imagen_url) {
                $oldPath = 'componentes/' . basename($comp->imagen_url);
                Storage::disk('supabase')->delete($oldPath);
            }
        }

        $labels = [
            'especificacion' => 'Especificación',
            'nucleos'        => 'Núcleos',
            'hilos'          => 'Hilos',
            'frecuencia_hz'  => 'Frecuencia (GHz)',
            'enfoque_uso'    => 'Enfoque de uso',
            'gama'           => 'Gama',
            'precio'         => 'Precio',
            'descuento_porcentaje' => 'Descuento (%)',
            'descuento_activo' => 'Descuento Activo',
            'stock'          => 'Stock',
            'activo'         => 'Estado',
        ];

        foreach (['especificacion', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama', 'precio', 'descuento_porcentaje', 'descuento_activo', 'stock', 'activo'] as $campo) {
            if ($request->has($campo)) {
                $nuevo = $request->input($campo);
                $viejo = $comp->$campo;
                $data[$campo] = $nuevo;

                if ((string) $viejo !== (string) $nuevo) {
                    $label = $labels[$campo];
                    if ($campo === 'activo') {
                        $nuevoBool = filter_var($nuevo, FILTER_VALIDATE_BOOLEAN);
                        $viejoLabel = $viejo ? 'Activo' : 'Inactivo';
                        $nuevoLabel = $nuevoBool ? 'Activo' : 'Inactivo';
                        $cambios[] = "{$label}: {$viejoLabel} → {$nuevoLabel}";
                        $data[$campo] = $nuevoBool ? \Illuminate\Support\Facades\DB::raw('true') : \Illuminate\Support\Facades\DB::raw('false');
                    } elseif ($campo === 'precio') {
                        $cambios[] = "{$label}: \$" . number_format((float)$viejo, 0, ',', '.') . " → \$" . number_format((float)$nuevo, 0, ',', '.');
                    } elseif ($campo === 'gama') {
                        $cambios[] = "{$label}: " . ucfirst($viejo) . " → " . ucfirst($nuevo);
                    } else {
                        $cambios[] = "{$label}: '{$viejo}' → '{$nuevo}'";
                    }
                } else if (in_array($campo, ['activo', 'descuento_activo'])) {
                    $nuevoBool = filter_var($nuevo, FILTER_VALIDATE_BOOLEAN);
                    $data[$campo] = $nuevoBool ? \Illuminate\Support\Facades\DB::raw('true') : \Illuminate\Support\Facades\DB::raw('false');
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

    
     * Elimina un registro de la base de datos.

    
     * Dependiendo de la lógica, puede ser una eliminación física o lógica (soft delete).

    
     */

    
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        // ── RN03: Solo admin o superadmin puede eliminar ──────────────────────
        if (!in_array($rol, ['admin', 'superadmin', 'bodega', 'proveedor'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Solo el rol Admin, SuperAdmin, Bodega o Proveedor puede eliminar componentes.'
            ], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('componentes.eliminar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.eliminar'], 403);
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

        // Verificar propiedad para bodega/proveedor
        if ($rol === 'bodega' && $componente->bodega_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar componentes de otra bodega'], 403);
        }

        if ($rol === 'proveedor') {
            $bodega = DB::table('bodegas')->where('id', $componente->bodega_id)->where('proveedor_id', $user->id)->first();
            if (!$bodega) {
                return response()->json(['success' => false, 'message' => 'No puedes eliminar componentes de bodegas que no te pertenecen'], 403);
            }
        }

        // ── RN02: Protección de integridad ───────────────────────
        // Bloquear si stock > 0 (solo aplica para inventario de bodegas, no maestros)
        if ($componente->bodega_id !== null && $componente->stock > 0) {
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

        if ($componente->imagen_url) {
            $oldPath = 'componentes/' . basename($componente->imagen_url);
            Storage::disk('supabase')->delete($oldPath);
        }

        $componente->delete(); // SoftDeletes: llena deleted_at en vez de borrar

        AuditLog::log($request, "Eliminó el componente «{$nombreProducto}» (SKU: {$componente->sku}, ID: {$id})", 'Componentes');

        return response()->json(['message' => 'Componente eliminado correctamente (borrado lógico)']);
    }

    // ══════════════════════════════════════════════════════════════
    // RF-19 – Ajustar stock por cantidad específica
    // ══════════════════════════════════════════════════════════════

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
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
