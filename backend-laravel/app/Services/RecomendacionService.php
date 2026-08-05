<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Exception;

class RecomendacionService
{
    /**
     * Algoritmo greedy para armar opciones de PC ideales según uso,
     * desempeño y presupuesto máximo.
     */
    public function buildPcIdeal(string $uso, string $gama, float $presupuestoMax): array
    {
        if ($presupuestoMax <= 0) {
            $presupuestoMax = match ($gama) {
                'alta' => 10000000.0,
                'media' => 5000000.0,
                'baja' => 2500000.0,
                default => 5000000.0,
            };
        }

        $opciones = [];

        // 1. Opción Equilibrada (Proporciones estándar por uso)
        $buildEquilibrada = $this->generarBuild($uso, $gama, $presupuestoMax, 'equilibrada');
        if ($buildEquilibrada['success']) {
            $opciones[] = [
                'id'          => 'equilibrada',
                'nombre'      => 'Opción Equilibrada',
                'tag'         => 'Recomendada',
                'descripcion' => 'Excelente balance entre precio, calidad y componentes optimizados.',
                'build'       => $buildEquilibrada['build'],
                'total'       => $buildEquilibrada['total'],
                'ahorro'      => $buildEquilibrada['ahorro'],
            ];
        }

        // 2. Opción Rendimiento (Prioriza el componente clave del enfoque de uso)
        $buildRendimiento = $this->generarBuild($uso, $gama, $presupuestoMax, 'rendimiento');
        if ($buildRendimiento['success'] && !empty($buildRendimiento['build'])) {
            // Verificar que no sea exactamente idéntica a la equilibrada
            $idsEquilibrada = array_column($buildEquilibrada['build'] ?? [], 'id');
            $idsRendimiento = array_column($buildRendimiento['build'], 'id');
            sort($idsEquilibrada);
            sort($idsRendimiento);

            if ($idsEquilibrada !== $idsRendimiento) {
                $opciones[] = [
                    'id'          => 'rendimiento',
                    'nombre'      => 'Opción Máximo Rendimiento',
                    'tag'         => 'Potencia Extra',
                    'descripcion' => 'Maximiza el rendimiento del componente clave (ej. Tarjeta de Video / Procesador).',
                    'build'       => $buildRendimiento['build'],
                    'total'       => $buildRendimiento['total'],
                    'ahorro'      => $buildRendimiento['ahorro'],
                ];
            }
        }

        // 3. Opción Económica / Ahorro (Presupuesto ajustado al 80-85% del máximo)
        $presupuestoAhorro = $presupuestoMax * 0.85;
        $buildAhorro = $this->generarBuild($uso, $gama === 'alta' ? 'media' : 'baja', $presupuestoAhorro, 'ahorro');
        if ($buildAhorro['success'] && !empty($buildAhorro['build'])) {
            $idsEquilibrada = array_column($buildEquilibrada['build'] ?? [], 'id');
            $idsAhorro = array_column($buildAhorro['build'], 'id');
            sort($idsEquilibrada);
            sort($idsAhorro);

            if ($idsEquilibrada !== $idsAhorro) {
                $opciones[] = [
                    'id'          => 'ahorro',
                    'nombre'      => 'Opción Ahorro Inteligente',
                    'tag'         => 'Económica',
                    'descripcion' => 'Optimiza tu inversión manteniendo un gran rendimiento por un costo menor.',
                    'build'       => $buildAhorro['build'],
                    'total'       => $buildAhorro['total'],
                    'ahorro'      => $buildAhorro['ahorro'],
                ];
            }
        }

        // Si ninguna build pudo armarse por proporciones estrictas, intentar opción económica base con el mínimo real
        if (empty($opciones)) {
            $categoriasRequeridas = ['CPU', 'GPU', 'RAM', 'Storage', 'Motherboard', 'PSU', 'Cooler', 'Case'];
            if ($uso === 'oficina') {
                $categoriasRequeridas = array_values(array_diff($categoriasRequeridas, ['GPU']));
            }
            $costoMinimo = $this->calcularCostoMinimo($categoriasRequeridas);
            $buildMinimaData = $this->obtenerBuildMinima($categoriasRequeridas);

            if ($presupuestoMax >= $costoMinimo && !empty($buildMinimaData['build'])) {
                $opciones[] = [
                    'id'          => 'economica_minima',
                    'nombre'      => 'Opción Económica Base',
                    'tag'         => 'Mínimo Requerido',
                    'descripcion' => 'Configuración de entrada optimizada al costo mínimo disponible.',
                    'build'       => $buildMinimaData['build'],
                    'total'       => $buildMinimaData['total'],
                    'ahorro'      => round($presupuestoMax - $buildMinimaData['total'], 2),
                ];
            } else {
                $diferencia = max(0, $costoMinimo - $presupuestoMax);
                throw new Exception(json_encode([
                    'success'       => false,
                    'message'       => 'El presupuesto de $' . number_format($presupuestoMax, 0, ',', '.') . ' es inferior al mínimo real de $' . number_format($costoMinimo, 0, ',', '.') . ' requerido para armar un PC de ' . $uso . '.',
                    'detalle'       => 'La opción más económica disponible requiere una diferencia de $' . number_format($diferencia, 0, ',', '.') . '.',
                    'presupuesto_minimo_estimado' => $costoMinimo,
                    'diferencia'    => $diferencia,
                    'build_economica' => $buildMinimaData['build'] ?? [],
                    'sugerencia'    => '¿Deseas aumentar tu presupuesto a $' . number_format($costoMinimo, 0, ',', '.') . ' o prefieres prescindir de algún componente?',
                ]));
            }
        }

        $opcionPrincipal = $opciones[0];

        return [
            'success'              => true,
            'opciones'             => $opciones,
            // Retrocompatibilidad con la respuesta anterior
            'build'                => $opcionPrincipal['build'],
            'total'                => $opcionPrincipal['total'],
            'presupuesto_max'      => $presupuestoMax,
            'ahorro'               => $opcionPrincipal['ahorro'],
            'uso'                  => $uso,
            'desempeno'            => $gama,
        ];
    }

    /**
     * Helper para armar una configuración individual según perfil y presupuesto.
     */
    private function generarBuild(string $uso, string $gama, float $presupuestoMax, string $tipoPerfil = 'equilibrada'): array
    {
        $proporciones = $this->getProporcionesPorUso($uso);

        if ($tipoPerfil === 'rendimiento') {
            if ($uso === 'gaming') {
                $proporciones['GPU'] = 0.40;
                $proporciones['CPU'] = 0.20;
            } elseif ($uso === 'diseño') {
                $proporciones['CPU'] = 0.35;
                $proporciones['GPU'] = 0.25;
            }
        }

        $categoriasRequeridas = ['CPU', 'GPU', 'RAM', 'Storage', 'Motherboard', 'PSU', 'Cooler', 'Case'];
        $categoriasOpcionales = [];
        if ($uso === 'oficina') {
            $categoriasOpcionales[] = 'GPU';
        }

        $categoriasConStock = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->whereIn('pc.categoria', $categoriasRequeridas)
            ->where('c.activo', true)
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->pluck('pc.categoria')
            ->unique()
            ->toArray();

        foreach ($categoriasRequeridas as $cat) {
            if (!in_array($cat, $categoriasConStock)) {
                $categoriasOpcionales[] = $cat;
            }
        }

        $build = [];
        $totalGastado = 0;
        $presupuestoRestante = $presupuestoMax;

        foreach ($categoriasRequeridas as $categoria) {
            $subPresupuesto = $presupuestoMax * ($proporciones[$categoria] ?? 0.10);

            $componente = $this->buscarMejorComponente($categoria, $uso, $gama, $subPresupuesto);

            if (!$componente && $gama !== 'baja') {
                $gamaFallback = $gama === 'alta' ? 'media' : 'baja';
                $componente = $this->buscarMejorComponente($categoria, $uso, $gamaFallback, $subPresupuesto);
            }

            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, $gama, $subPresupuesto);
            }

            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, null, $presupuestoRestante);
            }

            if ($componente) {
                $build[$categoria] = $componente;
                $totalGastado += (float) $componente->precio_final;
                $presupuestoRestante = $presupuestoMax - $totalGastado;
            }
        }

        $categoriasObtenidas = array_keys($build);
        $faltantes = array_diff(
            array_diff($categoriasRequeridas, $categoriasOpcionales),
            $categoriasObtenidas
        );

        if (count($faltantes) > 0) {
            return ['success' => false];
        }

        if ($presupuestoRestante > 0 && $tipoPerfil !== 'ahorro') {
            $prioridadMejora = $this->getPrioridadMejora($uso);

            foreach ($prioridadMejora as $catMejora) {
                if (!isset($build[$catMejora])) continue;

                $precioActual = (float) $build[$catMejora]->precio_final;
                $limiteMejora = $precioActual + $presupuestoRestante;

                $mejorOpcion = $this->buscarMejorComponente($catMejora, $uso, 'alta', $limiteMejora);

                if ($mejorOpcion && (float) $mejorOpcion->precio_final > $precioActual) {
                    $diferencia = (float) $mejorOpcion->precio_final - $precioActual;
                    $build[$catMejora] = $mejorOpcion;
                    $totalGastado += $diferencia;
                    $presupuestoRestante -= $diferencia;
                }

                if ($presupuestoRestante <= 0) break;
            }
        }

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

        return [
            'success'   => true,
            'build'     => $componentes,
            'total'     => round($totalGastado, 2),
            'ahorro'    => round($presupuestoMax - $totalGastado, 2),
        ];
    }

    private function buscarMejorComponente($categoria, $uso = null, $gama = null, $precioMax = null)
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('pc.categoria', $categoria)
            ->where('c.activo', true)
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
            );

        if ($uso) {
            $query->where('c.enfoque_uso', $uso);
        }

        if ($gama) {
            $query->where('c.gama', $gama);
        }

        if ($precioMax !== null) {
            $query->where(DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $precioMax);
        }

        return $query->orderBy(DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'DESC')->first();
    }

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

    private function calcularCostoMinimo(array $categorias): float
    {
        $preciosMinimos = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->whereIn('pc.categoria', $categorias)
            ->where('c.activo', true)
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'pc.categoria',
                DB::raw('MIN(CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END) as min_precio')
            )
            ->groupBy('pc.categoria')
            ->pluck('min_precio');

        return round((float) $preciosMinimos->sum(), 2);
    }

    public function obtenerBuildMinima(array $categoriasRequeridas): array
    {
        $build = [];
        $total = 0;
        $mapCategoriaStep = [
            'CPU' => 'cpu', 'GPU' => 'gpu', 'RAM' => 'ram', 'Storage' => 'storage',
            'Motherboard' => 'motherboard', 'PSU' => 'psu', 'Cooler' => 'cooler', 'Case' => 'case',
        ];

        foreach ($categoriasRequeridas as $cat) {
            $comp = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                ->where('pc.categoria', $cat)
                ->whereRaw("c.activo IS TRUE")
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->select(
                    'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                    'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                    'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                    DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
                )
                ->orderBy(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'ASC')
                ->first();

            if ($comp) {
                $precioFinal = (float) $comp->precio_final;
                $total += $precioFinal;
                $build[] = [
                    'step_id'        => $mapCategoriaStep[$cat] ?? strtolower($cat),
                    'id'             => $comp->id,
                    'nombre'         => $comp->nombre,
                    'categoria'      => $comp->categoria,
                    'especificacion' => $comp->especificacion,
                    'gama'           => $comp->gama,
                    'enfoque_uso'    => $comp->enfoque_uso,
                    'precio'         => $comp->precio,
                    'precio_final'   => $precioFinal,
                    'descuento_porcentaje' => $comp->descuento_porcentaje,
                    'descuento_activo' => $comp->descuento_activo,
                    'stock'          => $comp->stock,
                    'imagen_url'     => $comp->imagen_url,
                    'bodega'         => $comp->bodega,
                ];
            }
        }

        return [
            'build' => $build,
            'total' => round($total, 2)
        ];
    }
}
