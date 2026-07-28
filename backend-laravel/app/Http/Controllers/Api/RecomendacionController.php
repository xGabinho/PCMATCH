<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Componente;

class RecomendacionController extends Controller
{
    // ══════════════════════════════════════════════════════════════
    // GET /api/recomendaciones/mas-vendidos
    // Devuelve los componentes más cotizados por enfoque de uso
    // ══════════════════════════════════════════════════════════════

    public function getMasVendidos(Request $request)
    {
        $enfoques = ['gaming', 'estudio', 'oficina', 'diseño'];
        $limit = min((int) ($request->query('limit', 6)), 20);

        $resultado = [];

        foreach ($enfoques as $enfoque) {
            // Top componentes más incluidos en cotizaciones con ese enfoque
            $topComponentes = DB::table('cotizacion_items as ci')
                ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                ->where('c.enfoque_uso', $enfoque)
                ->where('c.activo', 'true')
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->select(
                    'c.id',
                    'pc.nombre',
                    'pc.categoria',
                    'c.especificacion',
                    'c.gama',
                    'c.enfoque_uso',
                    'c.precio',
                    'c.stock',
                    'c.imagen_url',
                    'b.nombre as bodega',
                    DB::raw('COUNT(ci.id) as veces_cotizado')
                )
                ->groupBy(
                    'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                    'c.gama', 'c.enfoque_uso', 'c.precio', 'c.stock',
                    'c.imagen_url', 'b.nombre'
                )
                ->orderByDesc('veces_cotizado')
                ->limit($limit)
                ->get();

            // Si no hay suficientes datos de cotizaciones, llenar con los mejor valorados por gama
            if ($topComponentes->count() < $limit) {
                $idsExcluir = $topComponentes->pluck('id')->toArray();
                $faltantes = $limit - $topComponentes->count();

                $complemento = DB::table('componentes as c')
                    ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                    ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                    ->where('c.enfoque_uso', $enfoque)
                    ->where('c.activo', 'true')
                    ->where('c.stock', '>', 0)
                    ->whereNull('c.deleted_at')
                    ->whereNotIn('c.id', $idsExcluir)
                    ->select(
                        'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                        'c.gama', 'c.enfoque_uso', 'c.precio', 'c.stock',
                        'c.imagen_url', 'b.nombre as bodega',
                        DB::raw('0 as veces_cotizado')
                    )
                    ->orderByRaw("CASE c.gama WHEN 'alta' THEN 1 WHEN 'media' THEN 2 ELSE 3 END")
                    ->orderBy('c.precio', 'ASC')
                    ->limit($faltantes)
                    ->get();

                $topComponentes = $topComponentes->merge($complemento);
            }

            $resultado[$enfoque] = $topComponentes;
        }

        return response()->json([
            'success' => true,
            'mas_vendidos' => $resultado
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // POST /api/recomendaciones/pc-ideal
    // Algoritmo greedy para armar el PC ideal según uso,
    // desempeño y presupuesto máximo
    // ══════════════════════════════════════════════════════════════

    public function buildPcIdeal(Request $request)
    {
        $request->validate([
            'uso'              => 'required|string|in:gaming,estudio,oficina,diseño',
            'desempeno'        => 'required|string|in:alta,media,baja',
            'presupuesto_max'  => 'required|numeric|min:1',
        ]);

        $uso              = $request->input('uso');
        $gama             = $request->input('desempeno');
        $presupuestoMax   = (float) $request->input('presupuesto_max');

        try {
            $service = new \App\Services\RecomendacionService();
            $result = $service->buildPcIdeal($uso, $gama, $presupuestoMax);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(json_decode($e->getMessage(), true), 422);
        }
    }
}
