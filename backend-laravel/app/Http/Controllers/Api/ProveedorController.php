<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Proveedor;
use App\Models\Bodega;
use App\Helpers\AuditLog;

class ProveedorController extends Controller
{
    
    /**
     * Verifica que el usuario sea admin o superadmin
     */
    private function checkSuperAdmin(Request $request, ?string $permission = null): bool
    {
        $user = $request->user();
        if (!$user) return false;
        $clase = get_class($user);
        if ($clase === \App\Models\Usuario::class && in_array($user->rol, ['admin', 'superadmin'])) {
            if ($user->rol === 'admin' && $permission) {
                return $user->hasPermission($permission);
            }
            return true;
        }
        return false;
    }

    private function checkBodega(Request $request): bool
    {
        $user = $request->user();
        if (!$user) return false;
        return get_class($user) === \App\Models\Bodega::class || (isset($user->rol) && $user->rol === 'bodega');
    }

    /**
     * Equivalente a GET api/proveedores/index.php
     * Obtiene una lista de registros o recursos.
     * Ejecuta la consulta a la base de datos (con posibles filtros/paginación) y retorna los datos en formato JSON.
     */
    
    public function index(Request $request)
    {
        if (!$this->checkSuperAdmin($request, 'proveedores.ver') && !$this->checkBodega($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $query = DB::table('proveedores as p')
            ->leftJoin('bodegas as b', 'b.proveedor_id', '=', 'p.id')
            ->groupBy('p.id', 'p.nombre', 'p.correo', 'p.activo', 'p.created_at', 'p.identificacion_legal', 'p.razon_social', 'p.estado_aprobacion', 'p.documento_soporte')
            ->select('p.id', 'p.nombre', 'p.correo', 'p.activo', 'p.created_at', 'p.identificacion_legal', 'p.razon_social', 'p.estado_aprobacion', 'p.documento_soporte', DB::raw('COUNT(b.id) AS total_bodegas'))
            ->orderBy('p.created_at', 'DESC');

        if ($request->has('page') || $request->boolean('paginate')) {
            $proveedores = $query->paginate(15);
            foreach ($proveedores->items() as $prov) {
                $prov->documento_soporte_url = (isset($prov->documento_soporte) && $prov->documento_soporte) ? url('storage/' . $prov->documento_soporte) : null;
            }
        } else {
            $proveedores = $query->get();
            foreach ($proveedores as $prov) {
                $prov->documento_soporte_url = (isset($prov->documento_soporte) && $prov->documento_soporte) ? url('storage/' . $prov->documento_soporte) : null;
            }
        }

        return response()->json([
            'proveedores' => $proveedores
        ]);
    }

    
    /**

    
     * Almacena un nuevo registro en la base de datos.

    
     * Valida la información recibida en la petición HTTP y crea el nuevo recurso.

    
     */

    
    public function store(Request $request)
    {
        if (!$this->checkSuperAdmin($request, 'proveedores.crear')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:proveedores,correo',
            'password' => 'required|string|min:8',
            'identificacion_legal' => 'required|string|max:255|unique:proveedores,identificacion_legal',
            'razon_social' => 'required|string|max:255',
            'documento_soporte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'estado_aprobacion' => 'nullable|string|in:pendiente,aprobado,rechazado'
        ], [
            'nombre.required' => 'El nombre es requerido',
            'correo.required' => 'El correo es requerido',
            'password.required' => 'La contraseña debe tener al menos 8 caracteres',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'identificacion_legal.required' => 'La identificación legal es requerida',
            'identificacion_legal.unique' => 'La identificación legal ya está en uso',
            'razon_social.required' => 'La razón social es requerida',
            'documento_soporte.file' => 'El documento de soporte debe ser un archivo',
            'documento_soporte.mimes' => 'El documento de soporte debe ser PDF, JPG, JPEG o PNG'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $proveedor = new Proveedor();
        $proveedor->nombre = $request->input('nombre');
        $proveedor->correo = $request->input('correo');
        $proveedor->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        
        $proveedor->identificacion_legal = $request->input('identificacion_legal');
        $proveedor->razon_social = $request->input('razon_social');
        if ($request->has('estado_aprobacion')) {
            $proveedor->estado_aprobacion = $request->input('estado_aprobacion');
        }

        if ($request->hasFile('documento_soporte')) {
            $path = $request->file('documento_soporte')->store('documentos_proveedores', 'public');
            $proveedor->documento_soporte = $path;
        }

        $proveedor->save();

        AuditLog::log($request, "Registró el proveedor «{$proveedor->nombre}» ({$proveedor->razon_social})", 'Proveedores');

        return response()->json(['message' => 'Proveedor creado', 'id' => $proveedor->id], 201);
    }

    
    /**

    
     * Actualiza un recurso existente en la base de datos.

    
     * Valida los nuevos datos y sobrescribe los valores del registro correspondiente.

    
     */

    
    public function update(Request $request)
    {
        if (!$this->checkSuperAdmin($request, 'proveedores.editar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|integer',
            'estado_aprobacion' => 'nullable|string|in:pendiente,aprobado,rechazado',
            'identificacion_legal' => 'nullable|string|max:255',
            'razon_social' => 'nullable|string|max:255'
        ], [
            'nombre.required' => 'El nombre es requerido'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $proveedor->nombre = $request->input('nombre');
        
        if ($request->has('identificacion_legal')) {
            $proveedor->identificacion_legal = $request->input('identificacion_legal');
        }
        if ($request->has('razon_social')) {
            $proveedor->razon_social = $request->input('razon_social');
        }
        if ($request->has('activo')) {
            $proveedor->activo = filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN) ? DB::raw('true') : DB::raw('false');
        }
        if ($request->has('estado_aprobacion')) {
            $user = $request->user();
            if ($user->rol !== 'superadmin' && !$user->hasPermission('proveedores.aprobar')) {
                return response()->json(['success' => false, 'message' => 'Solo el Super Administrador o usuarios autorizados pueden aprobar o rechazar proveedores.'], 403);
            }
            $proveedor->estado_aprobacion = $request->input('estado_aprobacion');
        }

        $dirty = $proveedor->getDirty();
        $detalles = AuditLog::formatChanges($dirty, $proveedor);
        $proveedor->save();

        AuditLog::log($request, "Editó el proveedor «{$proveedor->nombre}» — {$detalles}", 'Proveedores');

        return response()->json(['message' => 'Proveedor actualizado']);
    }

    
    /**

    
     * Elimina un registro de la base de datos.

    
     * Dependiendo de la lógica, puede ser una eliminación física o lógica (soft delete).

    
     */

    
    public function destroy(Request $request, $id = null)
    {
        if (!$this->checkSuperAdmin($request, 'proveedores.eliminar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        // Desasociar bodegas antes de eliminar
        Bodega::where('proveedor_id', $id)->update(['proveedor_id' => null]);

        $proveedor->delete();

        AuditLog::log($request, "Eliminó el proveedor «{$proveedor->nombre}»", 'Proveedores');

        return response()->json(['message' => 'Proveedor eliminado']);
    }

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
     */

    
    public function productos(Request $request, $id)
    {
        $user = $request->user();
        $clase = get_class($user);
        
        if ($id === 'me') {
            if ($clase !== \App\Models\Proveedor::class) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
            $id = $user->id;
        } else {
            if (!$this->checkSuperAdmin($request, 'proveedores.ver') && !$this->checkBodega($request)) {
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }
        }

        $proveedor = Proveedor::with('productosCatalogo')->find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        return response()->json([
            'productos' => $proveedor->productosCatalogo->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'categoria' => $p->categoria,
                    'especificacion' => $p->especificacion,
                    'imagen_url' => $p->imagen_url,
                    'nucleos' => $p->nucleos,
                    'hilos' => $p->hilos,
                    'frecuencia_hz' => $p->frecuencia_hz,
                    'enfoque_uso' => $p->enfoque_uso,
                    'gama' => $p->gama,
                    'precio_mayorista' => $p->pivot->precio_mayorista,
                    'stock' => $p->pivot->stock,
                    'descripcion_comercial' => $p->pivot->descripcion_comercial,
                ];
            })
        ]);
    }

    
    /**
     * POST /api/proveedores/{id}/productos — Agregar producto al catálogo del proveedor con precio mayorista
     * Endpoint lógico de la API.
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.
     */

    
    public function syncProductos(Request $request, $id)
    {
        $user = $request->user();
        $clase = get_class($user);

        // Proveedores pueden gestionar su propio catálogo
        if ($clase === \App\Models\Proveedor::class) {
            $id = $user->id;
        } elseif (!$this->checkSuperAdmin($request, 'proveedores.editar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        // Accept array of {producto_catalogo_id, precio_mayorista, descripcion_comercial}
        if ($request->has('items')) {
            $request->validate([
                'items' => 'required|array',
                'items.*.producto_catalogo_id' => 'required|integer|exists:productos_catalogo,id',
                'items.*.precio_mayorista' => 'required|numeric|min:0',
                'items.*.stock' => 'required|integer|min:0',
                'items.*.descripcion_comercial' => 'nullable|string|max:500',
            ]);

            $syncData = [];
            foreach ($request->input('items') as $item) {
                $syncData[$item['producto_catalogo_id']] = [
                    'precio_mayorista' => $item['precio_mayorista'],
                    'stock' => $item['stock'],
                    'descripcion_comercial' => $item['descripcion_comercial'] ?? null,
                ];
            }
            $proveedor->productosCatalogo()->syncWithoutDetaching($syncData);

            AuditLog::log($request, "Sincronizó " . count($syncData) . " productos en el catálogo del proveedor: {$proveedor->nombre}", 'Proveedores');

            return response()->json(['success' => true, 'message' => 'Catálogo sincronizado correctamente']);
        }

        // Legacy: Accept flat array of product IDs (backward compatible)
        $request->validate([
            'productos' => 'array',
            'productos.*' => 'integer|exists:productos_catalogo,id'
        ]);

        $productos = $request->input('productos', []);
        $proveedor->productosCatalogo()->sync($productos);

        AuditLog::log($request, "Asignó " . count($productos) . " productos del catálogo al proveedor: {$proveedor->nombre}", 'Proveedores');

        return response()->json(['success' => true, 'message' => 'Catálogo asignado correctamente']);
    }

    /**
     * PUT /api/proveedores/catalogo/item — Actualizar precio mayorista de un producto en el catálogo del proveedor
     */
    public function updateCatalogoItem(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);

        if ($clase !== \App\Models\Proveedor::class) {
            return response()->json(['success' => false, 'message' => 'Solo proveedores pueden actualizar su catálogo'], 403);
        }

        $request->validate([
            'producto_catalogo_id' => 'required|integer',
            'precio_mayorista' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'descripcion_comercial' => 'nullable|string|max:500',
        ]);

        $exists = DB::table('proveedor_producto_catalogo')
            ->where('proveedor_id', $user->id)
            ->where('producto_catalogo_id', $request->input('producto_catalogo_id'))
            ->exists();

        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Este producto no está en tu catálogo'], 404);
        }

        DB::table('proveedor_producto_catalogo')
            ->where('proveedor_id', $user->id)
            ->where('producto_catalogo_id', $request->input('producto_catalogo_id'))
            ->update([
                'precio_mayorista' => $request->input('precio_mayorista'),
                'stock' => $request->input('stock'),
                'descripcion_comercial' => $request->input('descripcion_comercial'),
            ]);

        return response()->json(['success' => true, 'message' => 'Precio actualizado']);
    }

    /**
     * DELETE /api/proveedores/catalogo/item — Quitar un producto del catálogo del proveedor
     */
    public function removeCatalogoItem(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);

        if ($clase !== \App\Models\Proveedor::class) {
            return response()->json(['success' => false, 'message' => 'Solo proveedores'], 403);
        }

        $productoId = $request->input('producto_catalogo_id') ?? $request->query('producto_catalogo_id');
        if (!$productoId) {
            return response()->json(['success' => false, 'message' => 'producto_catalogo_id es requerido'], 400);
        }

        DB::table('proveedor_producto_catalogo')
            ->where('proveedor_id', $user->id)
            ->where('producto_catalogo_id', $productoId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Producto removido del catálogo']);
    }
}
