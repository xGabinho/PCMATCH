<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CompatibilidadService
{
    /**
     * Valida compatibilidad de un conjunto de componentes.
     * Devuelve diagnóstico con errores críticos, advertencias y estado.
     *
     * @param array $componentes Array de objetos/arrays con datos de componentes
     * @return array ['compatible' => bool, 'errores' => [], 'advertencias' => [], 'detalles' => []]
     */
    public function validarConjunto(array $componentes): array
    {
        $errores = [];
        $advertencias = [];
        $detalles = [];

        // Organizar componentes por categoría
        $porCategoria = [];
        foreach ($componentes as $comp) {
            $cat = is_object($comp) ? ($comp->categoria ?? '') : ($comp['categoria'] ?? '');
            $porCategoria[$cat] = $comp;
        }

        // 1. Socket CPU ↔ Motherboard
        $socketCheck = $this->validarSocket($porCategoria);
        $errores = array_merge($errores, $socketCheck['errores']);
        $detalles[] = $socketCheck['detalle'];

        // 2. Tipo de RAM ↔ Motherboard
        $ramCheck = $this->validarTipoRam($porCategoria);
        $errores = array_merge($errores, $ramCheck['errores']);
        $detalles[] = $ramCheck['detalle'];

        // 3. Factor de Forma Motherboard ↔ Case
        $ffCheck = $this->validarFactorForma($porCategoria);
        $errores = array_merge($errores, $ffCheck['errores']);
        $detalles[] = $ffCheck['detalle'];

        // 4. GPU largo ↔ Case espacio
        $gpuCaseCheck = $this->validarGpuEnCase($porCategoria);
        $errores = array_merge($errores, $gpuCaseCheck['errores']);
        $advertencias = array_merge($advertencias, $gpuCaseCheck['advertencias']);
        $detalles[] = $gpuCaseCheck['detalle'];

        // 5. Consumo Energético ↔ PSU Wattage
        $psuCheck = $this->validarConsumoEnergetico($porCategoria);
        $errores = array_merge($errores, $psuCheck['errores']);
        $advertencias = array_merge($advertencias, $psuCheck['advertencias']);
        $detalles[] = $psuCheck['detalle'];

        // 6. Cooler ↔ Socket CPU
        $coolerCheck = $this->validarCoolerSocket($porCategoria);
        $errores = array_merge($errores, $coolerCheck['errores']);
        $detalles[] = $coolerCheck['detalle'];

        return [
            'compatible'   => empty($errores),
            'errores'      => $errores,
            'advertencias' => $advertencias,
            'detalles'     => array_filter($detalles),
        ];
    }

    /**
     * Valida compatibilidad de socket entre CPU y Motherboard.
     */
    private function validarSocket(array $porCategoria): array
    {
        $cpu = $porCategoria['CPU'] ?? null;
        $mobo = $porCategoria['Motherboard'] ?? null;

        if (!$cpu || !$mobo) {
            return ['errores' => [], 'detalle' => null];
        }

        $socketCpu = $this->getField($cpu, 'socket');
        $socketMobo = $this->getField($mobo, 'socket');

        if (!$socketCpu || !$socketMobo) {
            return ['errores' => [], 'detalle' => 'Socket: No se pudo verificar (datos insuficientes).'];
        }

        if ($socketCpu !== $socketMobo) {
            $nombreCpu = $this->getField($cpu, 'nombre');
            $nombreMobo = $this->getField($mobo, 'nombre');
            return [
                'errores' => ["INCOMPATIBLE: El procesador {$nombreCpu} (Socket {$socketCpu}) NO es compatible con la placa madre {$nombreMobo} (Socket {$socketMobo}). Necesitas una placa con socket {$socketCpu}."],
                'detalle' => "❌ Socket CPU ({$socketCpu}) ≠ Motherboard ({$socketMobo})"
            ];
        }

        return [
            'errores' => [],
            'detalle' => "✅ Socket compatible: {$socketCpu}"
        ];
    }

    /**
     * Valida compatibilidad de tipo de RAM con el Motherboard.
     */
    private function validarTipoRam(array $porCategoria): array
    {
        $ram = $porCategoria['RAM'] ?? null;
        $mobo = $porCategoria['Motherboard'] ?? null;

        if (!$ram || !$mobo) {
            return ['errores' => [], 'detalle' => null];
        }

        $tipoRam = $this->getField($ram, 'tipo_ram');
        $tipoMobo = $this->getField($mobo, 'tipo_ram');

        if (!$tipoRam || !$tipoMobo) {
            return ['errores' => [], 'detalle' => 'RAM: No se pudo verificar generación (datos insuficientes).'];
        }

        if ($tipoRam !== $tipoMobo) {
            $nombreRam = $this->getField($ram, 'nombre');
            $nombreMobo = $this->getField($mobo, 'nombre');
            return [
                'errores' => ["INCOMPATIBLE: La memoria {$nombreRam} ({$tipoRam}) NO es compatible con la placa {$nombreMobo} que soporta {$tipoMobo}. Necesitas memoria {$tipoMobo}."],
                'detalle' => "❌ RAM ({$tipoRam}) ≠ Motherboard ({$tipoMobo})"
            ];
        }

        return [
            'errores' => [],
            'detalle' => "✅ Tipo de RAM compatible: {$tipoRam}"
        ];
    }

    /**
     * Valida factor de forma Motherboard ↔ Case.
     */
    private function validarFactorForma(array $porCategoria): array
    {
        $mobo = $porCategoria['Motherboard'] ?? null;
        $case = $porCategoria['Case'] ?? null;

        if (!$mobo || !$case) {
            return ['errores' => [], 'detalle' => null];
        }

        $ffMobo = $this->getField($mobo, 'factor_forma');
        $ffCase = $this->getField($case, 'factor_forma');

        if (!$ffMobo || !$ffCase) {
            return ['errores' => [], 'detalle' => 'Factor de forma: No se pudo verificar (datos insuficientes).'];
        }

        $soportados = $this->getFactoresFormaSoportados($ffCase);

        if (!in_array($ffMobo, $soportados)) {
            $nombreMobo = $this->getField($mobo, 'nombre');
            $nombreCase = $this->getField($case, 'nombre');
            return [
                'errores' => ["INCOMPATIBLE: La placa {$nombreMobo} ({$ffMobo}) NO cabe en el gabinete {$nombreCase} ({$ffCase}). El gabinete soporta: " . implode(', ', $soportados) . "."],
                'detalle' => "❌ Factor de forma Motherboard ({$ffMobo}) no cabe en Case ({$ffCase})"
            ];
        }

        return [
            'errores' => [],
            'detalle' => "✅ Factor de forma compatible: {$ffMobo} en {$ffCase}"
        ];
    }

    /**
     * Valida que la GPU quepa físicamente en el Case.
     */
    private function validarGpuEnCase(array $porCategoria): array
    {
        $gpu = $porCategoria['GPU'] ?? null;
        $case = $porCategoria['Case'] ?? null;

        if (!$gpu || !$case) {
            return ['errores' => [], 'advertencias' => [], 'detalle' => null];
        }

        $largoGpu = (int) $this->getField($gpu, 'largo_mm');
        $espacioCase = (int) $this->getField($case, 'espacio_gpu_mm');

        if (!$largoGpu || !$espacioCase) {
            return ['errores' => [], 'advertencias' => [], 'detalle' => 'GPU/Case: No se pudo verificar dimensiones (datos insuficientes).'];
        }

        if ($largoGpu > $espacioCase) {
            $nombreGpu = $this->getField($gpu, 'nombre');
            $nombreCase = $this->getField($case, 'nombre');
            return [
                'errores' => ["INCOMPATIBLE: La tarjeta {$nombreGpu} ({$largoGpu}mm) NO cabe en el gabinete {$nombreCase} (máximo {$espacioCase}mm). Necesitas un gabinete con al menos {$largoGpu}mm de espacio para GPU."],
                'advertencias' => [],
                'detalle' => "❌ GPU ({$largoGpu}mm) > Case ({$espacioCase}mm)"
            ];
        }

        $margen = $espacioCase - $largoGpu;
        $advertencias = [];
        if ($margen < 15) {
            $advertencias[] = "⚠️ La GPU cabe pero con margen ajustado ({$margen}mm). El cableado podría ser complicado.";
        }

        return [
            'errores' => [],
            'advertencias' => $advertencias,
            'detalle' => "✅ GPU cabe en el Case: {$largoGpu}mm < {$espacioCase}mm (margen: {$margen}mm)"
        ];
    }

    /**
     * Valida consumo energético total vs PSU wattage.
     */
    private function validarConsumoEnergetico(array $porCategoria): array
    {
        $cpu = $porCategoria['CPU'] ?? null;
        $gpu = $porCategoria['GPU'] ?? null;
        $psu = $porCategoria['PSU'] ?? null;

        if (!$psu) {
            return ['errores' => [], 'advertencias' => [], 'detalle' => null];
        }

        $consumoCpu = (int) $this->getField($cpu, 'consumo_watts');
        $consumoGpu = (int) $this->getField($gpu, 'consumo_watts');
        $wattagePsu = (int) $this->getField($psu, 'wattage');

        if (!$wattagePsu) {
            return ['errores' => [], 'advertencias' => [], 'detalle' => 'PSU: No se pudo verificar wattage (datos insuficientes).'];
        }

        // Consumo base (placa, RAM, discos, ventiladores)
        $consumoBase = 75;
        $consumoTotal = $consumoCpu + $consumoGpu + $consumoBase;
        $consumoRecomendado = (int) ceil($consumoTotal * 1.25);

        $errores = [];
        $advertencias = [];

        if ($wattagePsu < $consumoTotal) {
            $nombrePsu = $this->getField($psu, 'nombre');
            $errores[] = "INCOMPATIBLE: La fuente {$nombrePsu} ({$wattagePsu}W) es INSUFICIENTE para el consumo estimado ({$consumoTotal}W). Necesitas al menos {$consumoRecomendado}W con margen de seguridad.";
        } elseif ($wattagePsu < $consumoRecomendado) {
            $advertencias[] = "⚠️ La fuente ({$wattagePsu}W) es suficiente pero ajustada. El consumo estimado es {$consumoTotal}W y se recomienda al menos {$consumoRecomendado}W para mayor estabilidad y longevidad.";
        }

        $estado = empty($errores) ? '✅' : '❌';

        return [
            'errores' => $errores,
            'advertencias' => $advertencias,
            'detalle' => "{$estado} Consumo estimado: {$consumoTotal}W (CPU: {$consumoCpu}W + GPU: {$consumoGpu}W + Base: {$consumoBase}W) | PSU: {$wattagePsu}W | Recomendado: {$consumoRecomendado}W"
        ];
    }

    /**
     * Valida compatibilidad de cooler con socket del CPU.
     */
    private function validarCoolerSocket(array $porCategoria): array
    {
        $cpu = $porCategoria['CPU'] ?? null;
        $cooler = $porCategoria['Cooler'] ?? null;

        if (!$cpu || !$cooler) {
            return ['errores' => [], 'detalle' => null];
        }

        $socketCpu = $this->getField($cpu, 'socket');
        $socketCooler = $this->getField($cooler, 'socket');

        if (!$socketCpu || !$socketCooler) {
            return ['errores' => [], 'detalle' => 'Cooler: No se pudo verificar compatibilidad de socket.'];
        }

        // Los coolers tienen sockets múltiples separados por /
        $socketsCompatibles = array_map('trim', explode('/', $socketCooler));

        if (!in_array($socketCpu, $socketsCompatibles)) {
            $nombreCooler = $this->getField($cooler, 'nombre');
            return [
                'errores' => ["INCOMPATIBLE: El cooler {$nombreCooler} no soporta el socket {$socketCpu}. Sockets compatibles: {$socketCooler}."],
                'detalle' => "❌ Cooler no soporta socket {$socketCpu}"
            ];
        }

        return [
            'errores' => [],
            'detalle' => "✅ Cooler compatible con socket {$socketCpu}"
        ];
    }

    /**
     * Calcula el consumo energético estimado de un conjunto de componentes.
     */
    public function calcularConsumo(array $componentes): array
    {
        $consumoCpu = 0;
        $consumoGpu = 0;
        $consumoBase = 75; // Placa, RAM, discos, ventiladores

        foreach ($componentes as $comp) {
            $cat = $this->getField($comp, 'categoria');
            $watts = (int) $this->getField($comp, 'consumo_watts');
            if ($cat === 'CPU') $consumoCpu = $watts;
            if ($cat === 'GPU') $consumoGpu = $watts;
        }

        $consumoTotal = $consumoCpu + $consumoGpu + $consumoBase;
        $recomendado = (int) ceil($consumoTotal * 1.25);

        // Buscar PSUs adecuadas en el inventario
        $psusRecomendadas = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('pc.categoria', 'PSU')
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->where('pc.wattage', '>=', $recomendado)
            ->select(
                'pc.nombre', 'pc.wattage', 'c.precio',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'b.nombre as bodega'
            )
            ->orderBy('pc.wattage', 'ASC')
            ->orderBy('precio_final', 'ASC')
            ->limit(3)
            ->get()
            ->toArray();

        return [
            'consumo_cpu'       => $consumoCpu,
            'consumo_gpu'       => $consumoGpu,
            'consumo_base'      => $consumoBase,
            'consumo_total'     => $consumoTotal,
            'wattaje_recomendado' => $recomendado,
            'psus_recomendadas' => $psusRecomendadas,
        ];
    }

    /**
     * Compara componentes de la misma categoría.
     */
    public function compararComponentes(string $categoria, array $terminosBusqueda, string $criterio = 'general'): array
    {
        $resultados = [];

        foreach ($terminosBusqueda as $termino) {
            $comp = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                ->where('pc.categoria', $categoria)
                ->whereRaw("c.activo IS TRUE")
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->where(function ($q) use ($termino) {
                    $q->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . strtolower($termino) . '%')
                      ->orWhere(DB::raw('LOWER(c.especificacion)'), 'LIKE', '%' . strtolower($termino) . '%');
                })
                ->select(
                    'pc.id as producto_id', 'pc.nombre', 'pc.categoria', 'pc.especificacion as spec_catalogo',
                    'c.especificacion', 'c.gama', 'c.enfoque_uso', 'c.stock',
                    'pc.consumo_watts', 'pc.socket', 'pc.tipo_ram', 'pc.largo_mm', 'pc.marca',
                    DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                    'b.nombre as bodega'
                )
                ->orderBy(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'ASC')
                ->first();

            if ($comp) {
                $resultados[] = $comp;
            }
        }

        if (count($resultados) < 2) {
            return [
                'exito' => false,
                'mensaje' => 'No se encontraron suficientes componentes para comparar. Asegúrate de usar nombres o modelos disponibles en nuestro inventario.'
            ];
        }

        return [
            'exito' => true,
            'categoria' => $categoria,
            'criterio' => $criterio,
            'componentes' => $resultados,
        ];
    }

    /**
     * Recomienda upgrade para un equipo existente.
     */
    public function recomendarUpgrade(array $componentesActuales, float $presupuesto, string $uso): array
    {
        // Identificar cuello de botella según uso
        $prioridadUpgrade = match ($uso) {
            'gaming'  => ['GPU', 'CPU', 'RAM', 'Storage'],
            'diseño'  => ['CPU', 'RAM', 'GPU', 'Storage'],
            'estudio' => ['CPU', 'RAM', 'Storage'],
            'oficina' => ['Storage', 'RAM', 'CPU'],
            default   => ['CPU', 'GPU', 'RAM', 'Storage'],
        };

        $recomendaciones = [];

        foreach ($prioridadUpgrade as $categoria) {
            $actual = null;
            foreach ($componentesActuales as $comp) {
                $cat = is_array($comp) ? ($comp['categoria'] ?? '') : ($comp->categoria ?? '');
                if (strtolower($cat) === strtolower($categoria)) {
                    $actual = $comp;
                    break;
                }
            }

            $nombreActual = is_array($actual) ? ($actual['nombre'] ?? '') : ($actual->nombre ?? '');

            // Buscar mejor opción dentro del presupuesto
            $mejora = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                ->where('pc.categoria', $categoria)
                ->whereRaw("c.activo IS TRUE")
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->where(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $presupuesto)
                ->select(
                    'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.gama', 'pc.socket',
                    DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                    'b.nombre as bodega'
                )
                ->orderBy(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), 'DESC')
                ->first();

            if ($mejora && strtolower($mejora->nombre) !== strtolower($nombreActual)) {
                $recomendaciones[] = [
                    'categoria'      => $categoria,
                    'actual'         => $nombreActual ?: 'No especificado',
                    'recomendado'    => $mejora->nombre,
                    'especificacion' => $mejora->especificacion,
                    'gama'           => $mejora->gama,
                    'precio'         => $mejora->precio_final,
                    'socket'         => $mejora->socket,
                    'bodega'         => $mejora->bodega,
                ];
            }
        }

        return [
            'uso' => $uso,
            'presupuesto' => $presupuesto,
            'recomendaciones' => $recomendaciones,
        ];
    }

    // ═══════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════

    /**
     * Obtiene factores de forma soportados por un tipo de case.
     */
    private function getFactoresFormaSoportados(string $ffCase): array
    {
        return match (strtolower($ffCase)) {
            'atx'       => ['ATX', 'Micro-ATX', 'Mini-ITX'],
            'micro-atx' => ['Micro-ATX', 'Mini-ITX'],
            'mini-itx'  => ['Mini-ITX'],
            default     => ['ATX', 'Micro-ATX', 'Mini-ITX'],
        };
    }

    /**
     * Obtiene un campo de un componente, soportando objetos y arrays.
     */
    private function getField($comp, string $field): ?string
    {
        if (!$comp) return null;

        // Si el componente tiene product_id, buscar datos del catálogo para campos técnicos
        $camposTecnicos = ['socket', 'tipo_ram', 'factor_forma', 'consumo_watts', 'wattage', 'largo_mm', 'espacio_gpu_mm', 'marca'];

        $value = is_object($comp) ? ($comp->$field ?? null) : ($comp[$field] ?? null);

        // Si no tiene el campo y tiene producto_id, buscar en productos_catalogo
        if ($value === null && in_array($field, $camposTecnicos)) {
            $productoId = is_object($comp) ? ($comp->producto_id ?? null) : ($comp['producto_id'] ?? null);
            if ($productoId) {
                $producto = DB::table('productos_catalogo')->where('id', $productoId)->first();
                if ($producto) {
                    $value = $producto->$field ?? null;
                }
            }
        }

        return $value !== null ? (string) $value : null;
    }

    /**
     * Enriquece componentes con datos de compatibilidad desde productos_catalogo.
     */
    public function enriquecerConDatosCatalogo(array $componenteIds): array
    {
        $componentes = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->whereIn('c.id', $componenteIds)
            ->whereRaw("c.activo IS TRUE")
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'pc.socket', 'pc.tipo_ram', 'pc.factor_forma',
                'pc.consumo_watts', 'pc.wattage', 'pc.largo_mm',
                'pc.espacio_gpu_mm', 'pc.marca',
                'c.gama', 'c.enfoque_uso', 'c.stock', 'c.precio',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'b.nombre as bodega'
            )
            ->get()
            ->toArray();

        return $componentes;
    }

    /**
     * Busca componentes por texto fuzzy y devuelve con datos de compatibilidad.
     */
    public function buscarPorTexto(string $texto): ?object
    {
        return DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->where(function ($q) use ($texto) {
                $q->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . strtolower($texto) . '%')
                  ->orWhere(DB::raw('LOWER(c.especificacion)'), 'LIKE', '%' . strtolower($texto) . '%');
            })
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'pc.socket', 'pc.tipo_ram', 'pc.factor_forma',
                'pc.consumo_watts', 'pc.wattage', 'pc.largo_mm',
                'pc.espacio_gpu_mm', 'pc.marca',
                'c.gama', 'c.enfoque_uso', 'c.stock', 'c.precio',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'b.nombre as bodega'
            )
            ->orderBy('precio_final', 'DESC')
            ->first();
    }
}
