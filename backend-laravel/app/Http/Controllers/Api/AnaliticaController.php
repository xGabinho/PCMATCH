<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnaliticaController extends Controller
{
    // ──────────────────────────────────────────────────────────────
    // Helper para resolver el rol del usuario autenticado
    // (mismo patrón que ComponenteController::resolverRol)
    // ──────────────────────────────────────────────────────────────

    private function resolverRol($user): string
    {
        if (!$user) return 'guest';
        $clase = get_class($user);
        if ($clase === \App\Models\Usuario::class)   return $user->rol ?? 'cliente';
        if ($clase === \App\Models\Proveedor::class)  return 'proveedor';
        if ($clase === \App\Models\Bodega::class)     return 'bodega';
        return 'guest';
    }

    /**
     * Retorna la lista de bodegas y proveedores activos para llenar los selectores.
     */
    public function selectores(Request $request)
    {
        $user = $request->user();

        if (!isset($user->rol) || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        $bodegas = DB::table('bodegas')
            ->whereRaw('activa IS TRUE')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $proveedores = DB::table('proveedores')
            ->whereRaw('activo IS TRUE')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'bodegas' => $bodegas,
            'proveedores' => $proveedores,
        ]);
    }

    /**
     * Módulo 1: Rotación de componentes por bodega.
     * Retorna los componentes con mayor cantidad vendida/cotizada
     * dentro de una bodega y un rango de fechas.
     */
    public function rotacionBodega(Request $request)
    {
        $user = $request->user();

        if (!isset($user->rol) || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        $bodegaId = $request->query('bodega_id');
        if (!$bodegaId) {
            return response()->json(['success' => false, 'message' => 'bodega_id es requerido'], 400);
        }

        $rango = $request->query('rango_fecha', 'historico');
        $limit = min((int) $request->query('limit', 10), 50);

        // Calcular fecha de inicio según el rango
        $fechaInicio = null;
        if ($rango === '1_mes') {
            $fechaInicio = now()->subMonth();
        } elseif ($rango === '3_meses') {
            $fechaInicio = now()->subMonths(3);
        }
        // 'historico' => sin filtro de fecha

        $query = DB::table('cotizacion_items as ci')
            ->join('cotizaciones as cot', 'ci.cotizacion_id', '=', 'cot.id')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->where('c.bodega_id', $bodegaId)
            ->whereNull('c.deleted_at');

        if ($fechaInicio) {
            $query->where('cot.created_at', '>=', $fechaInicio);
        }

        $data = $query
            ->select(
                'pc.id as producto_id',
                'pc.nombre as producto_nombre',
                'pc.categoria',
                'c.especificacion',
                DB::raw('SUM(ci.cantidad) as total_salida')
            )
            ->groupBy('pc.id', 'pc.nombre', 'pc.categoria', 'c.especificacion')
            ->orderByDesc('total_salida')
            ->limit($limit)
            ->get();

        // Obtener nombre de la bodega
        $bodega = DB::table('bodegas')->where('id', $bodegaId)->select('nombre')->first();

        return response()->json([
            'bodega_nombre' => $bodega->nombre ?? 'Desconocida',
            'rango' => $rango,
            'data' => $data,
        ]);
    }

    /**
     * Módulo 2: Consumo de stock por proveedor.
     * Muestra cómo se distribuye el consumo de cotizaciones del proveedor
     * en sus diferentes bodegas.
     */
    public function consumoProveedor(Request $request)
    {
        $user = $request->user();

        if (!isset($user->rol) || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        $proveedorId = $request->query('proveedor_id');
        if (!$proveedorId) {
            return response()->json(['success' => false, 'message' => 'proveedor_id es requerido'], 400);
        }

        $data = DB::table('cotizacion_items as ci')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('c.proveedor_id', $proveedorId)
            ->whereNull('c.deleted_at')
            ->select(
                'b.id as bodega_id',
                'b.nombre as bodega_nombre',
                DB::raw('SUM(ci.cantidad) as total_consumido')
            )
            ->groupBy('b.id', 'b.nombre')
            ->orderByDesc('total_consumido')
            ->get();

        // Calcular la suma total para porcentajes
        $sumaTotal = $data->sum('total_consumido');

        $dataConPorcentaje = $data->map(function ($row) use ($sumaTotal) {
            return [
                'bodega_id' => $row->bodega_id,
                'bodega_nombre' => $row->bodega_nombre,
                'total_consumido' => (int) $row->total_consumido,
                'porcentaje' => $sumaTotal > 0 ? round(($row->total_consumido / $sumaTotal) * 100, 1) : 0,
            ];
        });

        // Obtener nombre del proveedor
        $proveedor = DB::table('proveedores')->where('id', $proveedorId)->select('nombre')->first();

        return response()->json([
            'proveedor_nombre' => $proveedor->nombre ?? 'Desconocido',
            'total_general' => (int) $sumaTotal,
            'data' => $dataConPorcentaje,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // NUEVOS ENDPOINTS — Flujo de componentes para Bodegas y Proveedores
    // ══════════════════════════════════════════════════════════════

    /**
     * Flujo de componentes dentro de una bodega.
     *
     * Accesible por:
     *   - bodega: ve solo sus propios datos (no necesita pasar bodega_id).
     *   - admin/superadmin: pasa bodega_id como query param.
     *
     * Retorna dos listas:
     *   - mayor_flujo: Top N componentes con más unidades vendidas.
     *   - menor_flujo: Top N componentes con menos ventas (incluye activos sin ventas).
     */
    public function flujoBodega(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        // Control de acceso
        if (!in_array($rol, ['bodega', 'admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        // Determinar bodega_id según el rol
        if ($rol === 'bodega') {
            $bodegaId = $user->id;
        } else {
            $bodegaId = $request->query('bodega_id');
            if (!$bodegaId) {
                return response()->json(['success' => false, 'message' => 'bodega_id es requerido'], 400);
            }
        }

        $rango = $request->query('rango_fecha', 'historico');
        $limit = min((int) $request->query('limit', 10), 50);

        // Fecha de inicio según rango
        $fechaInicio = null;
        if ($rango === '1_mes') {
            $fechaInicio = now()->subMonth();
        } elseif ($rango === '3_meses') {
            $fechaInicio = now()->subMonths(3);
        }

        // ── Mayor flujo: componentes con más ventas ──────────────
        $queryMayor = DB::table('cotizacion_items as ci')
            ->join('cotizaciones as cot', 'ci.cotizacion_id', '=', 'cot.id')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->where('c.bodega_id', $bodegaId)
            ->whereNull('c.deleted_at');

        if ($fechaInicio) {
            $queryMayor->where('cot.created_at', '>=', $fechaInicio);
        }

        $mayorFlujo = $queryMayor
            ->select(
                'c.id as componente_id',
                'pc.nombre as producto_nombre',
                'pc.categoria',
                'c.especificacion',
                'c.stock',
                DB::raw('SUM(ci.cantidad) as total_salida')
            )
            ->groupBy('c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.stock')
            ->orderByDesc('total_salida')
            ->limit($limit)
            ->get();

        // ── Menor flujo: componentes activos con menos o cero ventas ─
        // Subquery: total vendido por componente de esta bodega
        $subVentas = DB::table('cotizacion_items as ci2')
            ->join('cotizaciones as cot2', 'ci2.cotizacion_id', '=', 'cot2.id')
            ->join('componentes as c2', 'ci2.componente_id', '=', 'c2.id')
            ->where('c2.bodega_id', $bodegaId)
            ->whereNull('c2.deleted_at');

        if ($fechaInicio) {
            $subVentas->where('cot2.created_at', '>=', $fechaInicio);
        }

        $subVentas = $subVentas
            ->select('c2.id as comp_id', DB::raw('SUM(ci2.cantidad) as total_vendido'))
            ->groupBy('c2.id');

        $menorFlujo = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoinSub($subVentas, 'ventas', function ($join) {
                $join->on('c.id', '=', 'ventas.comp_id');
            })
            ->where('c.bodega_id', $bodegaId)
            ->whereNull('c.deleted_at')
            ->whereRaw('c.activo IS TRUE')
            ->select(
                'c.id as componente_id',
                'pc.nombre as producto_nombre',
                'pc.categoria',
                'c.especificacion',
                'c.stock',
                DB::raw('COALESCE(ventas.total_vendido, 0) as total_salida')
            )
            ->orderBy('total_salida', 'asc')
            ->orderByDesc('c.stock')
            ->limit($limit)
            ->get();

        // Nombre de la bodega
        $bodega = DB::table('bodegas')->where('id', $bodegaId)->select('nombre')->first();

        return response()->json([
            'bodega_nombre' => $bodega->nombre ?? 'Desconocida',
            'rango' => $rango,
            'mayor_flujo' => $mayorFlujo,
            'menor_flujo' => $menorFlujo,
        ]);
    }

    /**
     * Flujo de componentes suministrados por un proveedor.
     *
     * Accesible por:
     *   - proveedor: ve solo sus propios datos (no necesita pasar proveedor_id).
     *   - admin/superadmin: pasa proveedor_id como query param.
     *
     * Retorna mayor_flujo y menor_flujo de componentes del proveedor.
     */
    public function flujoProveedor(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['proveedor', 'admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        // Determinar proveedor_id según el rol
        if ($rol === 'proveedor') {
            $proveedorId = $user->id;
        } else {
            $proveedorId = $request->query('proveedor_id');
            if (!$proveedorId) {
                return response()->json(['success' => false, 'message' => 'proveedor_id es requerido'], 400);
            }
        }

        $rango = $request->query('rango_fecha', 'historico');
        $limit = min((int) $request->query('limit', 10), 50);

        $fechaInicio = null;
        if ($rango === '1_mes') {
            $fechaInicio = now()->subMonth();
        } elseif ($rango === '3_meses') {
            $fechaInicio = now()->subMonths(3);
        }

        // ── Mayor flujo ──────────────────────────────────────────
        $queryMayor = DB::table('cotizacion_items as ci')
            ->join('cotizaciones as cot', 'ci.cotizacion_id', '=', 'cot.id')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('c.proveedor_id', $proveedorId)
            ->whereNull('c.deleted_at');

        if ($fechaInicio) {
            $queryMayor->where('cot.created_at', '>=', $fechaInicio);
        }

        $mayorFlujo = $queryMayor
            ->select(
                'c.id as componente_id',
                'pc.nombre as producto_nombre',
                'pc.categoria',
                'c.especificacion',
                'b.nombre as bodega_nombre',
                'c.stock',
                DB::raw('SUM(ci.cantidad) as total_salida')
            )
            ->groupBy('c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion', 'b.nombre', 'c.stock')
            ->orderByDesc('total_salida')
            ->limit($limit)
            ->get();

        // ── Menor flujo ──────────────────────────────────────────
        $subVentas = DB::table('cotizacion_items as ci2')
            ->join('cotizaciones as cot2', 'ci2.cotizacion_id', '=', 'cot2.id')
            ->join('componentes as c2', 'ci2.componente_id', '=', 'c2.id')
            ->where('c2.proveedor_id', $proveedorId)
            ->whereNull('c2.deleted_at');

        if ($fechaInicio) {
            $subVentas->where('cot2.created_at', '>=', $fechaInicio);
        }

        $subVentas = $subVentas
            ->select('c2.id as comp_id', DB::raw('SUM(ci2.cantidad) as total_vendido'))
            ->groupBy('c2.id');

        $menorFlujo = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->leftJoinSub($subVentas, 'ventas', function ($join) {
                $join->on('c.id', '=', 'ventas.comp_id');
            })
            ->where('c.proveedor_id', $proveedorId)
            ->whereNull('c.deleted_at')
            ->whereRaw('c.activo IS TRUE')
            ->select(
                'c.id as componente_id',
                'pc.nombre as producto_nombre',
                'pc.categoria',
                'c.especificacion',
                'b.nombre as bodega_nombre',
                'c.stock',
                DB::raw('COALESCE(ventas.total_vendido, 0) as total_salida')
            )
            ->orderBy('total_salida', 'asc')
            ->orderByDesc('c.stock')
            ->limit($limit)
            ->get();

        $proveedor = DB::table('proveedores')->where('id', $proveedorId)->select('nombre')->first();

        return response()->json([
            'proveedor_nombre' => $proveedor->nombre ?? 'Desconocido',
            'rango' => $rango,
            'mayor_flujo' => $mayorFlujo,
            'menor_flujo' => $menorFlujo,
        ]);
    }

    /**
     * Rendimiento comparativo de bodegas asociadas a un proveedor.
     *
     * Accesible por:
     *   - proveedor: ve solo las bodegas propias.
     *   - admin/superadmin: pasa proveedor_id como query param.
     *
     * Retorna ranking de bodegas por unidades vendidas de componentes del proveedor,
     * con porcentaje de contribución de cada bodega.
     */
    public function rendimientoBodegasProveedor(Request $request)
    {
        $user = $request->user();
        $rol = $this->resolverRol($user);

        if (!in_array($rol, ['proveedor', 'admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('reportes.ver')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: reportes.ver'], 403);
        }

        if ($rol === 'proveedor') {
            $proveedorId = $user->id;
        } else {
            $proveedorId = $request->query('proveedor_id');
            if (!$proveedorId) {
                return response()->json(['success' => false, 'message' => 'proveedor_id es requerido'], 400);
            }
        }

        $rango = $request->query('rango_fecha', 'historico');

        $fechaInicio = null;
        if ($rango === '1_mes') {
            $fechaInicio = now()->subMonth();
        } elseif ($rango === '3_meses') {
            $fechaInicio = now()->subMonths(3);
        }

        // Ventas agrupadas por bodega para componentes de este proveedor
        $queryVentas = DB::table('cotizacion_items as ci')
            ->join('cotizaciones as cot', 'ci.cotizacion_id', '=', 'cot.id')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('c.proveedor_id', $proveedorId)
            ->whereNull('c.deleted_at');

        if ($fechaInicio) {
            $queryVentas->where('cot.created_at', '>=', $fechaInicio);
        }

        $ventasPorBodega = $queryVentas
            ->select(
                'b.id as bodega_id',
                'b.nombre as bodega_nombre',
                DB::raw('SUM(ci.cantidad) as total_vendido'),
                DB::raw('COUNT(DISTINCT ci.cotizacion_id) as total_cotizaciones')
            )
            ->groupBy('b.id', 'b.nombre')
            ->orderByDesc('total_vendido')
            ->get();

        // Incluir bodegas asociadas sin ventas (para mostrar 0)
        $bodegasAsociadas = DB::table('bodegas as b')
            ->where(function ($q) use ($proveedorId) {
                $q->where('b.proveedor_id', $proveedorId)
                  ->orWhereExists(function ($sub) use ($proveedorId) {
                      $sub->select(DB::raw(1))
                          ->from('componentes as c')
                          ->whereColumn('c.bodega_id', 'b.id')
                          ->where('c.proveedor_id', $proveedorId)
                          ->whereNull('c.deleted_at');
                  });
            })
            ->whereRaw('b.activa IS TRUE')
            ->select('b.id', 'b.nombre')
            ->get();

        $ventasMap = $ventasPorBodega->keyBy('bodega_id');
        $sumaTotal = $ventasPorBodega->sum('total_vendido');

        $resultado = $bodegasAsociadas->map(function ($b) use ($ventasMap, $sumaTotal) {
            $venta = $ventasMap->get($b->id);
            $totalVendido = $venta ? (int) $venta->total_vendido : 0;
            return [
                'bodega_id' => $b->id,
                'bodega_nombre' => $b->nombre,
                'total_vendido' => $totalVendido,
                'total_cotizaciones' => $venta ? (int) $venta->total_cotizaciones : 0,
                'porcentaje' => $sumaTotal > 0 ? round(($totalVendido / $sumaTotal) * 100, 1) : 0,
            ];
        })->sortByDesc('total_vendido')->values();

        $proveedor = DB::table('proveedores')->where('id', $proveedorId)->select('nombre')->first();

        return response()->json([
            'proveedor_nombre' => $proveedor->nombre ?? 'Desconocido',
            'rango' => $rango,
            'total_general' => (int) $sumaTotal,
            'data' => $resultado,
        ]);
    }
}
