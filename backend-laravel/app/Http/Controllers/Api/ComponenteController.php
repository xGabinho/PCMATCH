<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        if (!in_array($rol, ['admin', 'superadmin', 'proveedor'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado para crear componentes'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'bodega_id' => 'required|integer',
            'producto_id' => 'required|integer',
            'especificacion' => 'required|string|max:255',
            'gama' => 'required|in:alta,media,baja',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0'
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

        $componenteId = DB::table('componentes')->insertGetId([
            'bodega_id' => $bodega_id,
            'producto_id' => $request->input('producto_id'),
            'especificacion' => $request->input('especificacion'),
            'gama' => $request->input('gama'),
            'precio' => $request->input('precio'),
            'stock' => $request->input('stock'),
            'created_at' => now(),
        ]);

        return response()->json(['message' => 'Componente registrado en Bodega correctamente', 'id' => $componenteId], 201);
    }
}
