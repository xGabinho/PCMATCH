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

        // ── Proporciones ideales de presupuesto por categoría ──
        // Varían según el tipo de uso
        $proporciones = $this->getProporcionesPorUso($uso);

        // Categorías requeridas para un PC completo
        $categoriasRequeridas = ['CPU', 'GPU', 'RAM', 'Storage', 'Motherboard', 'PSU', 'Cooler', 'Case'];

        // Para oficina, la GPU es opcional (puede usar gráficos integrados)
        $categoriasOpcionales = [];
        if ($uso === 'oficina') {
            $categoriasOpcionales[] = 'GPU';
        }

        $build = [];
        $totalGastado = 0;
        $presupuestoRestante = $presupuestoMax;

        // ── Fase 1: Intentar asignar un componente por categoría ──
        foreach ($categoriasRequeridas as $categoria) {
            $subPresupuesto = $presupuestoMax * ($proporciones[$categoria] ?? 0.10);

            // Buscar el mejor componente dentro del sub-presupuesto
            $componente = $this->buscarMejorComponente($categoria, $uso, $gama, $subPresupuesto);

            // Si no se encuentra en la gama solicitada, probar con gama inferior
            if (!$componente && $gama !== 'baja') {
                $gamaFallback = $gama === 'alta' ? 'media' : 'baja';
                $componente = $this->buscarMejorComponente($categoria, $uso, $gamaFallback, $subPresupuesto);
            }

            // Último intento: sin filtro de enfoque, solo gama
            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, $gama, $subPresupuesto);
            }

            // Último intento: el más barato de la categoría
            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, null, $presupuestoRestante);
            }

            if ($componente) {
                $build[$categoria] = $componente;
                $totalGastado += (float) $componente->precio_final;
                $presupuestoRestante = $presupuestoMax - $totalGastado;
            }
        }

        // ── Validar que se armó un PC mínimo viable ──
        $categoriasObtenidas = array_keys($build);
        $faltantes = array_diff(
            array_diff($categoriasRequeridas, $categoriasOpcionales),
            $categoriasObtenidas
        );

        if (count($faltantes) > 0) {
            // Verificar el costo mínimo posible
            $costoMinimo = $this->calcularCostoMinimo(
                array_diff($categoriasRequeridas, $categoriasOpcionales)
            );

            return response()->json([
                'success'       => false,
                'message'       => 'El presupuesto indicado es insuficiente para armar un PC completo con las características seleccionadas.',
                'detalle'       => 'No pudimos encontrar componentes en las categorías: ' . implode(', ', $faltantes) . '.',
                'presupuesto_minimo_estimado' => $costoMinimo,
                'sugerencia'    => 'Intenta aumentar tu presupuesto a al menos $' . number_format($costoMinimo, 2) . ' o selecciona un nivel de desempeño más bajo.',
            ], 422);
        }

        // ── Fase 2: Optimizar — si sobra presupuesto, mejorar componentes clave ──
        if ($presupuestoRestante > 0) {
            $prioridadMejora = $this->getPrioridadMejora($uso);

            foreach ($prioridadMejora as $catMejora) {
                if (!isset($build[$catMejora])) continue;

                $precioActual = (float) $build[$catMejora]->precio;
                $limiteMejora = $precioActual + $presupuestoRestante;

                $mejorOpcion = $this->buscarMejorComponente($catMejora, $uso, 'alta', $limiteMejora);

                if ($mejorOpcion && (float) $mejorOpcion->precio > $precioActual) {
                    $diferencia = (float) $mejorOpcion->precio - $precioActual;
                    $build[$catMejora] = $mejorOpcion;
                    $totalGastado += $diferencia;
                    $presupuestoRestante -= $diferencia;
                }

                if ($presupuestoRestante <= 0) break;
            }
        }

        // ── Formatear respuesta ──
        $componentes = [];
        $mapCategoriaStep = [
            'CPU' => 'cpu', 'GPU' => 'gpu', 'RAM' => 'ram', 'Storage' => 'storage',
            'Motherboard' => 'motherboard', 'PSU' => 'psu', 'Cooler' => 'cooler', 'Case' => 'case',
        ];

        foreach ($build as $categoria => $comp) {
            $componentes[] = [
                'step_id'        => $mapCategoriaStep[$categoria] ?? strtolower($categoria),
                'id'             => $comp->id,
                'nombre'         => $comp->nombre,
                'categoria'      => $comp->categoria,
                'especificacion' => $comp->especificacion,
                'gama'           => $comp->gama,
                'enfoque_uso'    => $comp->enfoque_uso,
                'precio'         => $comp->precio,
                'precio_final'   => $comp->precio_final,
                'descuento_porcentaje' => $comp->descuento_porcentaje,
                'descuento_activo' => $comp->descuento_activo,
                'stock'          => $comp->stock,
                'imagen_url'     => $comp->imagen_url,
                'bodega'         => $comp->bodega,
            ];
        }

        return response()->json([
            'success'              => true,
            'build'                => $componentes,
            'total'                => round($totalGastado, 2),
            'presupuesto_max'      => $presupuestoMax,
            'ahorro'               => round($presupuestoMax - $totalGastado, 2),
            'uso'                  => $uso,
            'desempeno'            => $gama,
        ]);
    }

    // ──────────────────────────────────────────────
    // Métodos privados auxiliares
    // ──────────────────────────────────────────────

    /**
     * Busca el mejor componente de una categoría dado un presupuesto máximo.
     * Prioriza: enfoque_uso → gama → precio más alto dentro del presupuesto (best bang for buck).
     */
    private function buscarMejorComponente($categoria, $uso = null, $gama = null, $precioMax = null)
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('pc.categoria', $categoria)
            ->where('c.activo', 'true')
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
            );

        if ($uso) {
            $query->where('c.enfoque_uso', $uso);
        }

        if ($gama) {
            $query->where('c.gama', $gama);
        }

        if ($precioMax !== null) {
            $query->where(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $precioMax);
        }

        // El más caro que quepa en el presupuesto (mejor calidad), usando el precio con descuento
        return $query->orderBy(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'DESC')->first();
    }

    /**
     * Proporciones de distribución del presupuesto según uso.
     */
    private function getProporcionesPorUso(string $uso): array
    {
        $proporciones = [
            'gaming' => [
                'GPU' => 0.30, 'CPU' => 0.22, 'Motherboard' => 0.10,
                'RAM' => 0.10, 'Storage' => 0.08, 'PSU' => 0.08,
                'Cooler' => 0.05, 'Case' => 0.07,
            ],
            'diseño' => [
                'CPU' => 0.28, 'GPU' => 0.25, 'RAM' => 0.12,
                'Motherboard' => 0.10, 'Storage' => 0.10, 'PSU' => 0.06,
                'Cooler' => 0.04, 'Case' => 0.05,
            ],
            'estudio' => [
                'CPU' => 0.25, 'GPU' => 0.15, 'RAM' => 0.15,
                'Motherboard' => 0.12, 'Storage' => 0.12, 'PSU' => 0.08,
                'Cooler' => 0.05, 'Case' => 0.08,
            ],
            'oficina' => [
                'CPU' => 0.25, 'GPU' => 0.05, 'RAM' => 0.15,
                'Motherboard' => 0.15, 'Storage' => 0.15, 'PSU' => 0.10,
                'Cooler' => 0.05, 'Case' => 0.10,
            ],
        ];

        return $proporciones[$uso] ?? $proporciones['gaming'];
    }

    /**
     * Prioridad de mejora según uso (qué categoría beneficia más al usuario).
     */
    private function getPrioridadMejora(string $uso): array
    {
        return match ($uso) {
            'gaming'  => ['GPU', 'CPU', 'RAM', 'Storage'],
            'diseño'  => ['CPU', 'GPU', 'RAM', 'Storage'],
            'estudio' => ['CPU', 'RAM', 'Storage', 'GPU'],
            'oficina' => ['Storage', 'RAM', 'CPU'],
            default   => ['CPU', 'GPU', 'RAM'],
        };
    }

    /**
     * Calcula el costo mínimo posible para un PC con las categorías dadas.
     */
    private function calcularCostoMinimo(array $categorias): float
    {
        $costoMinimo = 0;
        foreach ($categorias as $cat) {
            $comp = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->where('pc.categoria', $cat)
                ->where('c.activo', 'true')
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->select(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'))
                ->orderBy(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'ASC')
                ->first();

            if ($comp) {
                $costoMinimo += (float) $comp->precio_final;
            }
        }
        return round($costoMinimo, 2);
    }
}
