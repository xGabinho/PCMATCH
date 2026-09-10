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
        // Normalizar gama
        $gamaClean = mb_strtolower(trim($gama));
        if (str_contains($gamaClean, 'media-alta') || str_contains($gamaClean, 'media alta') || str_contains($gamaClean, 'media_alta')) {
            $gama = ($presupuestoMax > 0 && $presupuestoMax < 4500000) ? 'media' : 'alta';
        } elseif (str_contains($gamaClean, 'alta') || str_contains($gamaClean, 'alto')) {
            $gama = 'alta';
        } elseif (str_contains($gamaClean, 'baja') || str_contains($gamaClean, 'bajo') || str_contains($gamaClean, 'entrada')) {
            $gama = 'baja';
        } else {
            $gama = 'media';
        }

        // Normalizar uso
        $usoClean = mb_strtolower(trim($uso));
        if (str_contains($usoClean, 'game') || str_contains($usoClean, 'jueg') || str_contains($usoClean, 'jugar')) {
            $uso = 'gaming';
        } elseif (str_contains($usoClean, 'diseñ') || str_contains($usoClean, 'render') || str_contains($usoClean, 'edici')) {
            $uso = 'diseño';
        } elseif (str_contains($usoClean, 'estudi') || str_contains($usoClean, 'tarea')) {
            $uso = 'estudio';
        } elseif (str_contains($usoClean, 'oficin') || str_contains($usoClean, 'trabajo')) {
            $uso = 'oficina';
        } else {
            $uso = in_array($usoClean, ['gaming', 'estudio', 'oficina', 'diseño']) ? $usoClean : 'gaming';
        }

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
            ->whereRaw("c.activo IS TRUE")
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

        $minimosPorCategoria = $this->getCostosMinimosPorCategoria($categoriasRequeridas);

        $build = [];
        $totalGastado = 0;
        $presupuestoRestante = $presupuestoMax;

        for ($i = 0; $i < count($categoriasRequeridas); $i++) {
            $categoria = $categoriasRequeridas[$i];
            if (in_array($categoria, $categoriasOpcionales) && $presupuestoRestante < ($minimosPorCategoria[$categoria] ?? 0)) {
                continue;
            }

            // Calcular costo mínimo necesario para las categorías restantes
            $costoMinimoRestante = 0;
            for ($j = $i + 1; $j < count($categoriasRequeridas); $j++) {
                $catJ = $categoriasRequeridas[$j];
                if (!in_array($catJ, $categoriasOpcionales)) {
                    $costoMinimoRestante += ($minimosPorCategoria[$catJ] ?? 0);
                }
            }

            // Tope seguro que esta categoría puede consumir sin dejar sin presupuesto a las demás
            $topeMaximoCategoria = max(0, $presupuestoRestante - $costoMinimoRestante);
            $subPresupuesto = max($minimosPorCategoria[$categoria] ?? 0, $presupuestoMax * ($proporciones[$categoria] ?? 0.10));
            $subPresupuesto = min($subPresupuesto, $topeMaximoCategoria);

            // Búsqueda progresiva de componente
            $componente = $this->buscarMejorComponente($categoria, $uso, $gama, $subPresupuesto);

            if (!$componente && $gama !== 'baja') {
                $gamaFallback = $gama === 'alta' ? 'media' : 'baja';
                $componente = $this->buscarMejorComponente($categoria, $uso, $gamaFallback, $subPresupuesto);
            }

            if (!$componente && $gama === 'alta') {
                $componente = $this->buscarMejorComponente($categoria, $uso, 'baja', $subPresupuesto);
            }

            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, $gama, $subPresupuesto);
            }

            if (!$componente) {
                $componente = $this->buscarMejorComponente($categoria, null, null, $subPresupuesto);
            }

            if (!$componente) {
                // Si no entra en el subpresupuesto asignado, obtener el más económico viable dentro del tope seguro
                $componente = $this->buscarMejorComponente($categoria, null, null, $topeMaximoCategoria, 'ASC');
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

        // FASE 2: Aprovechar el presupuesto restante para mejorar componentes clave
        if ($presupuestoRestante > 20000 && $tipoPerfil !== 'ahorro') {
            $prioridadMejora = $this->getPrioridadMejora($uso);

            for ($loop = 0; $loop < 3 && $presupuestoRestante > 20000; $loop++) {
                $mejoraRealizada = false;
                foreach ($prioridadMejora as $catMejora) {
                    if (!isset($build[$catMejora])) continue;

                    $precioActual = (float) $build[$catMejora]->precio_final;
                    $limiteMejora = $precioActual + $presupuestoRestante;

                    // Intentar mejorar manteniendo el enfoque de uso
                    $mejorOpcion = $this->buscarMejorComponente($catMejora, $uso, null, $limiteMejora, 'DESC');
                    if (!$mejorOpcion || (float) $mejorOpcion->precio_final <= $precioActual) {
                        $mejorOpcion = $this->buscarMejorComponente($catMejora, null, null, $limiteMejora, 'DESC');
                    }

                    if ($mejorOpcion && (float) $mejorOpcion->precio_final > $precioActual) {
                        $diferencia = (float) $mejorOpcion->precio_final - $precioActual;
                        $build[$catMejora] = $mejorOpcion;
                        $totalGastado += $diferencia;
                        $presupuestoRestante -= $diferencia;
                        $mejoraRealizada = true;
                    }
                }
                if (!$mejoraRealizada) break;
            }
        }

        // FASE 3: Validar compatibilidad del build armado y sustituir si hay conflictos
        $compatService = new CompatibilidadService();
        $validacion = $compatService->validarConjunto($this->buildToCompatArray($build));
        $advertenciasCompat = $validacion['advertencias'] ?? [];

        if (!$validacion['compatible']) {
            // Intentar sustitución automática de componentes conflictivos
            $build = $this->intentarSustitucion($build, $compatService, $presupuestoMax, $totalGastado, $uso, $gama);
            // Recalcular totales tras sustitución
            $totalGastado = 0;
            foreach ($build as $comp) {
                $totalGastado += (float) $comp->precio_final;
            }
            $presupuestoRestante = $presupuestoMax - $totalGastado;
            // Re-validar
            $validacion = $compatService->validarConjunto($this->buildToCompatArray($build));
            $advertenciasCompat = array_merge($advertenciasCompat, $validacion['advertencias'] ?? []);
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
            'success'        => true,
            'build'          => $componentes,
            'total'          => round($totalGastado, 2),
            'ahorro'         => round($presupuestoMax - $totalGastado, 2),
            'compatibilidad' => [
                'compatible'   => $validacion['compatible'],
                'detalles'     => $validacion['detalles'] ?? [],
                'advertencias' => $advertenciasCompat,
            ],
        ];
    }

    private function buscarMejorComponente($categoria, $uso = null, $gama = null, $precioMax = null, string $order = 'DESC')
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('pc.categoria', $categoria)
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
            );

        if ($uso) {
            $query->where('c.enfoque_uso', $uso);
        }

        if ($gama) {
            $query->where('c.gama', $gama);
        }

        if ($precioMax !== null) {
            $query->where(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $precioMax);
        }

        return $query->orderBy(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), $order)->first();
    }

    private function getCostosMinimosPorCategoria(array $categorias): array
    {
        $preciosMinimos = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->whereIn('pc.categoria', $categorias)
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'pc.categoria',
                DB::raw('MIN(CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END) as min_precio')
            )
            ->groupBy('pc.categoria')
            ->pluck('min_precio', 'pc.categoria')
            ->toArray();

        $resultado = [];
        foreach ($categorias as $cat) {
            $resultado[$cat] = (float)($preciosMinimos[$cat] ?? 0);
        }
        return $resultado;
    }

    private function getProporcionesPorUso(string $uso): array
    {
        $proporciones = [
            'gaming' => [
                'GPU' => 0.32, 'CPU' => 0.22, 'Motherboard' => 0.12,
                'RAM' => 0.08, 'Storage' => 0.09, 'PSU' => 0.08,
                'Cooler' => 0.04, 'Case' => 0.05,
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
            'gaming'  => ['GPU', 'CPU', 'RAM', 'Storage', 'Motherboard', 'PSU', 'Cooler', 'Case'],
            'diseño'  => ['CPU', 'RAM', 'Storage', 'GPU', 'Motherboard', 'PSU', 'Cooler', 'Case'],
            'estudio' => ['CPU', 'RAM', 'Storage', 'GPU', 'Motherboard', 'PSU', 'Cooler', 'Case'],
            'oficina' => ['Storage', 'RAM', 'CPU', 'Motherboard', 'PSU', 'Case'],
            default   => ['CPU', 'GPU', 'RAM', 'Storage'],
        };
    }

    private function calcularCostoMinimo(array $categorias): float
    {
        $minimos = $this->getCostosMinimosPorCategoria($categorias);
        return round((float) array_sum($minimos), 2);
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

    /**
     * Convierte el array de build (keyed por categoría) a un array compatible con CompatibilidadService.
     * Enriquece cada componente con datos de compatibilidad de productos_catalogo.
     */
    private function buildToCompatArray(array $build): array
    {
        $result = [];
        foreach ($build as $categoria => $comp) {
            // Buscar datos de compatibilidad del producto catalogo
            $productoId = $comp->producto_id ?? null;
            $compat = null;
            if ($productoId) {
                $compat = DB::table('productos_catalogo')
                    ->where('id', $productoId)
                    ->select('socket', 'tipo_ram', 'factor_forma', 'consumo_watts', 'wattage', 'largo_mm', 'espacio_gpu_mm', 'marca')
                    ->first();
            }

            $obj = (object) [
                'id'            => $comp->id,
                'producto_id'   => $productoId,
                'nombre'        => $comp->nombre,
                'categoria'     => $categoria,
                'especificacion'=> $comp->especificacion,
                'socket'        => $compat->socket ?? null,
                'tipo_ram'      => $compat->tipo_ram ?? null,
                'factor_forma'  => $compat->factor_forma ?? null,
                'consumo_watts' => $compat->consumo_watts ?? null,
                'wattage'       => $compat->wattage ?? null,
                'largo_mm'      => $compat->largo_mm ?? null,
                'espacio_gpu_mm'=> $compat->espacio_gpu_mm ?? null,
                'marca'         => $compat->marca ?? null,
            ];
            $result[] = $obj;
        }
        return $result;
    }

    /**
     * Intenta sustituir componentes incompatibles para resolver conflictos.
     * Estrategia: si hay socket mismatch CPU/Mobo, cambia la Mobo por una compatible.
     *             si hay RAM type mismatch, cambia la RAM por una compatible.
     */
    private function intentarSustitucion(array $build, CompatibilidadService $compatService, float $presupuestoMax, float $totalGastado, string $uso, string $gama): array
    {
        // Obtener socket del CPU seleccionado
        $cpuProductoId = $build['CPU']->producto_id ?? null;
        $cpuCompat = $cpuProductoId ? DB::table('productos_catalogo')->where('id', $cpuProductoId)->first() : null;
        $socketCpu = $cpuCompat->socket ?? null;
        $tipoRamCpu = $cpuCompat->tipo_ram ?? null;

        // 1. Si hay socket mismatch, buscar Motherboard compatible
        if ($socketCpu && isset($build['Motherboard'])) {
            $moboProductoId = $build['Motherboard']->producto_id ?? null;
            $moboCompat = $moboProductoId ? DB::table('productos_catalogo')->where('id', $moboProductoId)->first() : null;
            $socketMobo = $moboCompat->socket ?? null;

            if ($socketMobo && $socketCpu !== $socketMobo) {
                $presupuestoMobo = (float) $build['Motherboard']->precio_final + ($presupuestoMax - $totalGastado);
                // Buscar motherboard con socket compatible
                $nuevaMobo = DB::table('componentes as c')
                    ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                    ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                    ->where('pc.categoria', 'Motherboard')
                    ->where('pc.socket', $socketCpu)
                    ->whereRaw("c.activo IS TRUE")
                    ->where('c.stock', '>', 0)
                    ->whereNull('c.deleted_at')
                    ->where(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $presupuestoMobo)
                    ->select(
                        'c.id', 'c.producto_id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                        'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                        'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                        DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
                    )
                    ->orderBy(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'DESC')
                    ->first();

                if ($nuevaMobo) {
                    $build['Motherboard'] = $nuevaMobo;
                }
            }
        }

        // 2. Si hay RAM type mismatch, buscar RAM compatible
        if ($tipoRamCpu && isset($build['RAM'])) {
            // Get mobo tipo_ram (use the potentially substituted one)
            $moboProductoId2 = $build['Motherboard']->producto_id ?? null;
            $moboCompat2 = $moboProductoId2 ? DB::table('productos_catalogo')->where('id', $moboProductoId2)->first() : null;
            $tipoRamMobo = $moboCompat2->tipo_ram ?? $tipoRamCpu;

            $ramProductoId = $build['RAM']->producto_id ?? null;
            $ramCompat = $ramProductoId ? DB::table('productos_catalogo')->where('id', $ramProductoId)->first() : null;
            $tipoRamActual = $ramCompat->tipo_ram ?? null;

            if ($tipoRamActual && $tipoRamActual !== $tipoRamMobo) {
                // Recalcular total after mobo change
                $totalActual = 0;
                foreach ($build as $comp) {
                    $totalActual += (float) $comp->precio_final;
                }
                $presupuestoRam = (float) $build['RAM']->precio_final + ($presupuestoMax - $totalActual);

                $nuevaRam = DB::table('componentes as c')
                    ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                    ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                    ->where('pc.categoria', 'RAM')
                    ->where('pc.tipo_ram', $tipoRamMobo)
                    ->whereRaw("c.activo IS TRUE")
                    ->where('c.stock', '>', 0)
                    ->whereNull('c.deleted_at')
                    ->where(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $presupuestoRam)
                    ->select(
                        'c.id', 'c.producto_id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                        'c.gama', 'c.enfoque_uso', 'c.precio', 'c.descuento_porcentaje',
                        'c.descuento_activo', 'c.stock', 'c.imagen_url', 'b.nombre as bodega',
                        DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
                    )
                    ->orderBy(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'DESC')
                    ->first();

                if ($nuevaRam) {
                    $build['RAM'] = $nuevaRam;
                }
            }
        }

        return $build;
    }
}
