<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionController extends Controller
{
    /**
     * Resuelve el rol string basado en el modelo autenticado
     */
    private function getRole(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        
        if ($clase === \App\Models\Proveedor::class) return 'proveedor';
        if ($clase === \App\Models\Bodega::class) return 'bodega';
        
        return $user->rol; // 'admin', 'superadmin', 'cliente'
    }

    /**
     * Equivalente a GET api/cotizaciones/index.php
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        // Validación principal
        if (!in_array($rol, ['cliente', 'admin', 'superadmin', 'proveedor'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' || $rol === 'superadmin') {
            $query = DB::table('cotizaciones as c')
                ->join('usuarios as u', 'c.usuario_id', '=', 'u.id')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    'u.nombre', 'u.apellido', 'u.correo',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at', 'u.nombre', 'u.apellido', 'u.correo');
        } 
        elseif ($rol === 'proveedor') {
            // Proveedor ve cotizaciones que incluyen componentes de sus bodegas
            $query = DB::table('cotizaciones as c')
                ->join('usuarios as u', 'c.usuario_id', '=', 'u.id')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->leftJoin('componentes as comp', 'ci.componente_id', '=', 'comp.id')
                ->leftJoin('bodegas as b', 'comp.bodega_id', '=', 'b.id')
                ->where('b.proveedor_id', $user->id)
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    'u.nombre', 'u.apellido', 'u.correo',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at', 'u.nombre', 'u.apellido', 'u.correo');
        } 
        else {
            // Cliente solo ve sus propias cotizaciones
            $query = DB::table('cotizaciones as c')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->where('c.usuario_id', $user->id)
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at');
        }

        $cotizaciones = $query->orderBy('c.created_at', 'DESC')->get();

        return response()->json([
            'cotizaciones' => $cotizaciones
        ]);
    }

    /**
     * Equivalente a POST api/cotizaciones/index.php
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        if ($rol !== 'cliente') {
            return response()->json(['success' => false, 'message' => 'Solo los clientes pueden crear cotizaciones'], 403);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Debes seleccionar al menos un componente'], 400);
        }

        $total = $request->input('total', 0);
        $perfil = $request->input('perfil', '');

        // Usamos una trasacción de base de datos para asegurar integridad
        $cotizacionId = null;
        DB::transaction(function () use ($user, $perfil, $total, $items, &$cotizacionId) {
            // 1. Insertar la cotización madre
            $cotizacionId = DB::table('cotizaciones')->insertGetId([
                'usuario_id' => $user->id,
                'perfil' => $perfil,
                'total' => $total,
                // created_at se setea automáticamente usualmente, pero si en tu DB legacy usabas NOW():
                'created_at' => now(),
            ]);

            // 2. Insertar cada uno de los items
            $itemsData = [];
            foreach ($items as $item) {
                $itemsData[] = [
                    'cotizacion_id' => $cotizacionId,
                    'componente_id' => $item['componente_id'],
                    'cantidad' => $item['cantidad'] ?? 1,
                    'precio_unitario' => $item['precio']
                ];
            }
            DB::table('cotizacion_items')->insert($itemsData);
        });

        return response()->json(['message' => 'Cotización guardada', 'id' => $cotizacionId], 201);
    }

    /**
     * Equivalente a DELETE api/cotizaciones/index.php
     */
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        if (!in_array($rol, ['cliente', 'admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $cotizacion = DB::table('cotizaciones')->where('id', $id)->first();
        if (!$cotizacion) {
            return response()->json(['success' => false, 'message' => 'Cotización no encontrada'], 404);
        }

        // Si es cliente, solo puede borrar su propia cotización
        if ($rol === 'cliente' && $cotizacion->usuario_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para eliminar esta cotización'], 403);
        }

        // Eliminar en cascada
        DB::transaction(function () use ($id) {
            DB::table('cotizacion_items')->where('cotizacion_id', $id)->delete();
            DB::table('cotizaciones')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Cotización eliminada']);
    }
}
