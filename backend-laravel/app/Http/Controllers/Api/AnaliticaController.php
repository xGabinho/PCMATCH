<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnaliticaController extends Controller
{
    /**
     * Retorna la lista de bodegas y proveedores activos para llenar los selectores.
     */
    public function selectores(Request $request)
    {
        $user = $request->user();

        if (!isset($user->rol) || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $bodegas = DB::table('bodegas')
            ->whereRaw('activa = true')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $proveedores = DB::table('proveedores')
            ->whereRaw('activo = true')
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
}
