<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLog;

class ComponenteController extends Controller
{
    /**
     * Equivalente a GET api/componentes/admin/index.php
     */
    public function indexAdmin(Request $request)
    {
        // El legacy solo permitía 'admin'
        $user = $request->user();
        if (get_class($user) !== \App\Models\Usuario::class || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $componentes = DB::table('componentes as c')
            ->join('productos_catalogo as p', 'c.producto_id', '=', 'p.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->select(
                'c.id', 'p.nombre', 'p.categoria', 'c.especificacion', 'c.gama',
                'c.precio', 'c.stock', 'b.nombre AS bodega_nombre'
            )
            ->orderBy('p.categoria', 'ASC')
            ->orderBy('p.nombre', 'ASC')
            ->get();

        return response()->json([
            'componentes' => $componentes
        ]);
    }

    /**
     * GET /api/componentes - Lista los componentes de la bodega autenticada
     */
    public function indexBodega(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        $rol = 'cliente';
        if ($clase === \App\Models\Usuario::class) $rol = $user->rol;
        if ($clase === \App\Models\Proveedor::class) $rol = 'proveedor';
        if ($clase === \App\Models\Bodega::class) $rol = 'bodega';

        $query = DB::table('componentes as c')
            ->join('productos_catalogo as p', 'c.producto_id', '=', 'p.id')
            ->select('c.id', 'p.nombre', 'p.categoria', 'c.especificacion', 'c.gama', 'c.precio', 'c.stock', 'c.bodega_id');

        if ($rol === 'bodega') {
            $query->where('c.bodega_id', $user->id);
        } elseif ($rol === 'proveedor') {
            $query->whereIn('c.bodega_id', function($sub) use ($user) {
                $sub->select('id')->from('bodegas')->where('proveedor_id', $user->id);
            });
        }

        $componentes = $query->orderBy('p.categoria')->get();

        return response()->json(['componentes' => $componentes]);
    }

    /**
     * Equivalente a GET api/componentes/publico/index.php
     */
    public function indexPublic(Request $request)
    {
        // No requiere autorización (Público)
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as p', 'c.producto_id', '=', 'p.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('b.activa', 1)
            ->where('c.stock', '>', 0)
            ->select(
                'c.id', 'p.nombre', 'p.categoria', 'c.especificacion',
                'c.gama', 'c.precio', 'c.stock', 'b.nombre AS bodega'
            );

        if ($request->has('categoria')) {
            $query->where('p.categoria', $request->query('categoria'))
                  ->orderBy('p.nombre', 'ASC');
        } else {
            $query->orderBy('p.categoria', 'ASC')
                  ->orderBy('p.nombre', 'ASC');
        }

        $componentes = $query->get();

        return response()->json([
            'componentes' => $componentes
        ]);
    }

    /**
     * POST para crear un componente nuevo
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        
        $rol = 'cliente';
        if ($clase === \App\Models\Usuario::class) $rol = $user->rol;
        if ($clase === \App\Models\Proveedor::class) $rol = 'proveedor';
        if ($clase === \App\Models\Bodega::class) $rol = 'bodega';

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado para crear componentes'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'bodega_id'     => ($rol === 'bodega') ? 'nullable|integer' : 'required|integer',
            'producto_id'   => 'required|integer',
            'especificacion'=> 'required|string|max:255',
            'gama'          => 'required|in:alta,media,baja',
            'precio'        => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0'
        ], [
            'gama.in' => 'La gama debe ser alta, media o baja'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $bodega_id = $request->input('bodega_id');

        // Si es proveedor, asegurarse estrictamente que la bodega le pertenezca a él.
        if ($rol === 'proveedor') {
            $bodega = DB::table('bodegas')->where('id', $bodega_id)->where('proveedor_id', $user->id)->first();
            if (!$bodega) {
                return response()->json(['success' => false, 'message' => 'Esta bodega no te pertenece o no existe'], 403);
            }
        }

        // Si es bodega, se auto-asigna su propio ID
        if ($rol === 'bodega') {
            $bodega_id = $user->id;
        }

        $componenteId = DB::table('componentes')->insertGetId([
            'bodega_id' => $bodega_id,
            'producto_id' => $request->input('producto_id'),
            'especificacion' => $request->input('especificacion'),
            'gama' => $request->input('gama'),
            'precio' => $request->input('precio'),
            'stock' => $request->input('stock'),
            'created_at' => now(),
        ]);

        $producto = DB::table('productos_catalogo')->where('id', $request->input('producto_id'))->first();
        $nombreProducto = $producto ? $producto->nombre : "ID {$request->input('producto_id')}";
        $bodegaNombre = DB::table('bodegas')->where('id', $bodega_id)->value('nombre') ?? "ID {$bodega_id}";

        AuditLog::log($request, "Agregó el componente: {$nombreProducto} ({$request->input('especificacion')}, {$request->input('gama')}) a la bodega {$bodegaNombre}", 'Componentes');

        return response()->json(['message' => 'Componente registrado en Bodega correctamente', 'id' => $componenteId], 201);
    }

    /**
     * PUT /api/componentes - Editar componente (bodega propia, admin, superadmin, proveedor)
     * 
     * RF-17 – Gestión de Componentes – Modificar componente
     * RN01: Solo admin, superadmin, bodega o proveedor pueden modificar
     * RN02: Validación estricta (precio > 0, stock >= 0, gama válida)
     * RN03: Registro detallado de cambios en historial de auditoría
     */
    public function update(Request $request)
    {
        // ── RN01: Verificar permiso de modificación ──────────────────
        $user = $request->user();
        $clase = get_class($user);
        $rol = 'cliente';
        if ($clase === \App\Models\Usuario::class) $rol = $user->rol;
        if ($clase === \App\Models\Proveedor::class) $rol = 'proveedor';
        if ($clase === \App\Models\Bodega::class) $rol = 'bodega';

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Solo administradores, proveedores y bodegas pueden modificar componentes.'
            ], 403);
        }

        $id = $request->input('id');
        if (!$id) return response()->json(['success' => false, 'message' => 'id es requerido'], 400);

        $comp = DB::table('componentes')->where('id', $id)->first();
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

        // ── RN02: Validación estricta de datos actualizados ──────────
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'especificacion' => 'nullable|string|max:255',
            'gama'           => 'nullable|in:alta,media,baja',
            'precio'         => 'nullable|numeric|gt:0',
            'stock'          => 'nullable|integer|min:0',
        ], [
            'gama.in'      => 'La gama debe ser alta, media o baja',
            'precio.gt'    => 'El precio debe ser mayor a cero',
            'precio.numeric' => 'El precio debe ser un valor numérico',
            'stock.min'    => 'La cantidad en stock no puede ser negativa',
            'stock.integer' => 'La cantidad en stock debe ser un número entero',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        // ── RN03: Registrar cambios con detalle (auditoría) ──────────
        $data = [];
        $cambios = [];
        $labels = [
            'especificacion' => 'Especificación',
            'gama'           => 'Gama',
            'precio'         => 'Precio',
            'stock'          => 'Stock',
        ];

        foreach (['especificacion', 'gama', 'precio', 'stock'] as $campo) {
            if ($request->has($campo)) {
                $nuevo = $request->input($campo);
                $viejo = $comp->$campo;
                $data[$campo] = $nuevo;

                if ((string) $viejo !== (string) $nuevo) {
                    $label = $labels[$campo];
                    $cambios[] = "{$label}: '{$viejo}' → '{$nuevo}'";
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

        $detalles = empty($cambios) ? 'Sin cambios detectados' : implode(', ', $cambios);
        AuditLog::log($request, "Modificó el componente: {$nombreProducto} (ID {$id}). Cambios: {$detalles}", 'Componentes');

        return response()->json(['message' => 'Componente actualizado correctamente']);
    }

    /**
     * DELETE /api/componentes - Eliminar componente (bodega propia, admin, superadmin, proveedor)
     */
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $clase = get_class($user);
        $rol = 'cliente';
        if ($clase === \App\Models\Usuario::class) $rol = $user->rol;
        if ($clase === \App\Models\Proveedor::class) $rol = 'proveedor';
        if ($clase === \App\Models\Bodega::class) $rol = 'bodega';

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor', 'bodega'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) return response()->json(['success' => false, 'message' => 'id es requerido'], 400);

        $comp = DB::table('componentes')->where('id', $id)->first();
        if (!$comp) return response()->json(['success' => false, 'message' => 'Componente no encontrado'], 404);

        if ($rol === 'bodega' && $comp->bodega_id != $user->id) {
            return response()->json(['success' => false, 'message' => 'No puedes eliminar componentes de otra bodega'], 403);
        }

        DB::table('componentes')->where('id', $id)->delete();
        AuditLog::log($request, "Eliminó el componente ID {$id}", 'Componentes');

        return response()->json(['message' => 'Componente eliminado']);
    }
}
