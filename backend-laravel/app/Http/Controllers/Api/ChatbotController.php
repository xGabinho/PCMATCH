<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\RecomendacionService;
use Exception;

class ChatbotController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'messages' => 'required|array'
        ]);

        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return response()->json(['error' => 'La API Key de Gemini no está configurada.'], 500);
        }

        $userMessages = $request->input('messages');

        // Fast-path para consultas de solo-lectura de catálogo
        $lastUserMsg = end($userMessages)['content'] ?? '';
        if ($this->esConsultaSoloCatalogo($lastUserMsg)) {
            return response()->json($this->generarRespuestaRapidaCatalogo($lastUserMsg));
        }

        // Formatear mensajes para Gemini API
        $geminiContents = [];
        $firstUserFound = false;
        
        foreach ($userMessages as $msg) {
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            
            if (!$firstUserFound && $role !== 'user') {
                continue; // La API de Gemini requiere que el primer mensaje sea del usuario
            }
            $firstUserFound = true;
            
            $geminiContents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        $systemInstruction = "Eres el asistente inteligente de PCMATCH, experto en hardware de computadoras y ensamblajes. Responde siempre en español, de forma clara, amable y directa.

REGLAS OBLIGATORIAS:
1. NUNCA inventes componentes, precios, marcas ni especificaciones. Todo debe proceder únicamente de las herramientas de PCMATCH ('ver_inventario' o 'build_pc').
2. NUNCA respondas con un mensaje de error, límite o falla sin acompañarlo de al menos una alternativa concreta o una pregunta que permita avanzar.
3. PREGUNTAS INTRODUCTORIAS DE ARMADO: Si el usuario desea armar una PC o recibir recomendación de equipo completo y NO ha especificado alguno de los 3 datos principales (Gama/Nivel, Presupuesto máximo, y Enfoque de uso), pregúntaselos amablemente.
4. BÚSQUEDA DE COMPONENTES Y CATÁLOGO: Si el usuario pregunta por piezas específicas, disponibilidad, precios o stock (ej: '¿tienen tarjetas RTX 3060?'), usa INMEDIATAMENTE 'ver_inventario' sin exigir presupuesto obligatoriamente.
5. RECOMENDACIÓN DE PC COMPLETA: Usa 'build_pc' únicamente cuando tengas o el usuario te provea el uso principal, gama/desempeño y/o presupuesto aproximado. Para juegos de bajos requisitos (ej. Roblox), verifica el mínimo real antes de rechazar.
6. PRESUPUESTO INSUFICIENTE: Si el presupuesto no alcanza para una PC nueva completa, indica el monto mínimo real necesario con cifra exacta, ofrece la configuración más económica disponible, la diferencia de precio y pregunta si puede ajustar el presupuesto o sacrificar algún componente.
7. TONO Y FORMATO: Respuestas breves y claras, cifras concretas del catálogo y cerrando SIEMPRE con una pregunta accionable o propuesta de siguiente paso.";

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => $geminiContents,
            'tools' => [
                [
                    'functionDeclarations' => [
                        [
                            'name' => 'build_pc',
                            'description' => 'Arma y recomienda una PC completa ideal basada en uso, desempeño y presupuesto.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'uso' => [
                                        'type' => 'STRING',
                                        'description' => 'Uso principal de la PC',
                                        'enum' => ['gaming', 'estudio', 'oficina', 'diseño']
                                    ],
                                    'desempeno' => [
                                        'type' => 'STRING',
                                        'description' => 'Nivel de desempeño deseado',
                                        'enum' => ['alta', 'media', 'baja']
                                    ],
                                    'presupuesto_max' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Presupuesto máximo del usuario en un número entero (ej: 5000000). Si el usuario dice "alto", "ilimitado" o "sin límite", pasa 10000000. Si dice "medio", pasa 5000000. Si dice "bajo", pasa 2500000.'
                                    ]
                                ],
                                'required' => ['uso', 'desempeno']
                            ]
                        ],
                        [
                            'name' => 'ver_inventario',
                            'description' => 'Busca componentes específicos en la base de datos de PCMATCH para responder preguntas sobre stock, precios o características.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'categoria' => [
                                        'type' => 'STRING',
                                        'description' => 'Categoría del componente (ej: CPU, GPU, RAM, Motherboard, Storage, PSU, Cooler, Case)',
                                    ],
                                    'palabra_clave' => [
                                        'type' => 'STRING',
                                        'description' => 'Palabra clave para buscar por nombre o especificación (ej: Ryzen, RTX 3060, 16GB)',
                                    ],
                                    'precio_maximo' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Presupuesto máximo para la búsqueda',
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'tool_config' => [
                'function_calling_config' => [
                    'mode' => 'AUTO'
                ]
            ]
        ];

        $maxRetries = 3;
        $retryCount = 0;
        // Lista de modelos a intentar en orden de preferencia
        $models = ['gemini-2.0-flash-lite', 'gemini-2.0-flash', 'gemini-3.5-flash-lite'];
        $modelIndex = 0;

        while ($retryCount < $maxRetries) {
            $model = $models[$modelIndex] ?? $models[0];
            
            try {
                $response = Http::timeout(30)->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $payload);
            } catch (\Throwable $netEx) {
                $retryCount++;
                if ($retryCount >= $maxRetries) {
                    return response()->json([
                        'error' => 'Error de conexión a internet o DNS al contactar Gemini.',
                        'details' => $netEx->getMessage(),
                    ], 503);
                }
                sleep(2);
                continue;
            }

            // Si se excede la cuota, esperar y reintentar con el siguiente modelo
            if ($response->status() === 429) {
                sleep(3);
                $modelIndex = ($modelIndex + 1) % count($models);
                $retryCount++;
                continue;
            }

            // Si el modelo no existe o no está disponible, probar el siguiente
            if ($response->status() === 404) {
                $modelIndex = ($modelIndex + 1) % count($models);
                $retryCount++;
                continue;
            }

            if (!$response->successful()) {
                return response()->json([
                    'error' => 'Error al comunicarse con Gemini API.',
                    'details' => $response->json(),
                    'status_code' => $response->status(),
                ], 502);
            }

            $data = $response->json();
            $candidates = $data['candidates'][0] ?? null;

            if (!$candidates || !isset($candidates['content']['parts'])) {
                break;
            }

            // Check if there is a function call
            $functionCall = null;
            $textResponse = null;

            foreach ($candidates['content']['parts'] as $part) {
                if (isset($part['functionCall'])) {
                    $functionCall = $part['functionCall'];
                } elseif (isset($part['text'])) {
                    $textResponse = $part['text'];
                }
            }

            if ($functionCall) {
                $name = $functionCall['name'];
                $args = $functionCall['args'] ?? [];

                if ($name === 'build_pc') {
                    // Acción terminal: Armar PC y devolver resultado estructurado al Frontend
                    try {
                        $rawBudget = $args['presupuesto_max'] ?? null;
                        $presupuesto = is_numeric($rawBudget) ? (float) $rawBudget : 0;

                        if ($presupuesto <= 0) {
                            $desempeno = $args['desempeno'] ?? 'alta';
                            $presupuesto = match ($desempeno) {
                                'alta' => 10000000.0,
                                'media' => 5000000.0,
                                'baja' => 2500000.0,
                                default => 5000000.0,
                            };
                        }

                        $service = new RecomendacionService();
                        $buildResult = $service->buildPcIdeal(
                            $args['uso'] ?? 'gaming', 
                            $args['desempeno'] ?? 'alta', 
                            $presupuesto
                        );
                        
                        return response()->json([
                            'type' => 'build',
                            'buildResult' => $buildResult,
                            'message' => '¡Excelente! He analizado nuestro inventario y he preparado la mejor configuración posible para ti.'
                        ]);
                    } catch (Exception $e) {
                        $errorData = json_decode($e->getMessage(), true);
                        if (is_array($errorData) && isset($errorData['presupuesto_minimo_estimado'])) {
                            $costoMinimo = $errorData['presupuesto_minimo_estimado'];
                            $diferencia = $errorData['diferencia'] ?? max(0, $costoMinimo - $presupuesto);
                            $buildEconomica = $errorData['build_economica'] ?? [];
                            $uso = $args['uso'] ?? 'el uso solicitado';

                            $msg = "El presupuesto indicado (" . $this->formatMoney($presupuesto) . ") es inferior al mínimo real necesario para armar un PC completo de " . $uso . ".\n\n";
                            $msg .= "• **Presupuesto mínimo real requerido:** " . $this->formatMoney($costoMinimo) . "\n";
                            if ($presupuesto > 0) {
                                $msg .= "• **Diferencia con tu presupuesto:** " . $this->formatMoney($diferencia) . "\n\n";
                            } else {
                                $msg .= "\n";
                            }

                            if (!empty($buildEconomica)) {
                                $msg .= "**Configuración más económica disponible en inventario:**\n";
                                foreach ($buildEconomica as $item) {
                                    $msg .= "- **" . $item['categoria'] . ":** " . $item['nombre'] . " (" . $this->formatMoney($item['precio_final']) . ")\n";
                                }
                                $msg .= "\n";
                            }

                            $msg .= "💡 **Opciones para continuar:**\n";
                            $msg .= "1. Incrementar tu presupuesto a " . $this->formatMoney($costoMinimo) . " para armar este equipo completo.\n";
                            $msg .= "2. Reutilizar o ajustar algún componente (ej. gabinete o almacenamiento).\n\n";
                            $msg .= "¿Te gustaría ajustar tu presupuesto a " . $this->formatMoney($costoMinimo) . " para armar esta configuración o prefieres ver alternativas de componentes?";

                            return response()->json([
                                'type' => 'text',
                                'message' => $msg
                            ]);
                        } elseif (is_array($errorData)) {
                            return response()->json([
                                'type' => 'text',
                                'message' => "No pudimos completar la recomendación con la configuración seleccionada: " . ($errorData['message'] ?? '') . " ¿Te gustaría probar con otro nivel de desempeño o ajustar componentes?"
                            ]);
                        }

                        return response()->json([
                            'type' => 'text',
                            'message' => 'Hubo un problema al intentar armar la PC con el inventario actual. ¿Te gustaría consultar componentes específicos o probar con otro presupuesto?'
                        ]);
                    }
                }

                if ($name === 'ver_inventario') {
                    // Ejecutar búsqueda en DB
                    $resultados = $this->buscarInventario($args);

                    // Agregar el functionCall de la IA a los contents
                    $payload['contents'][] = [
                        'role' => 'model',
                        'parts' => [
                            ['functionCall' => $functionCall]
                        ]
                    ];

                    // Agregar la respuesta de la función (functionResponse) como un rol 'function'
                    $payload['contents'][] = [
                        'role' => 'function', 
                        'parts' => [
                            [
                                'functionResponse' => [
                                    'name' => 'ver_inventario',
                                    'response' => [
                                        'name' => 'ver_inventario',
                                        'content' => ['items' => $resultados]
                                    ]
                                ]
                            ]
                        ]
                    ];
                    $retryCount++;
                    continue; // Volver a llamar a Gemini con los resultados
                }
            }

            // Si llegamos aquí y hay texto (o se superó max loops), devolvemos el texto
            return response()->json([
                'type' => 'text',
                'message' => $textResponse ?? 'No pude generar una respuesta. ¿Deseas consultar componentes de nuestro catálogo o probar con una búsqueda de componentes?'
            ]);
        }

        return response()->json([
            'type' => 'text',
            'message' => 'Tuve una pequeña demora al procesar tu solicitud. ¿Te gustaría consultar sobre alguno de nuestros componentes en catálogo o ver las recomendaciones de armado?'
        ]);
    }

    private function buscarInventario(array $args): array
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->where('c.activo', true)
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'pc.nombre', 'pc.categoria', 'c.especificacion',
                DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'c.stock'
            );

        if (!empty($args['categoria'])) {
            $catLower = strtolower($args['categoria']);
            $query->where(DB::raw('LOWER(pc.categoria)'), 'LIKE', '%' . $catLower . '%');
        }

        if (!empty($args['palabra_clave'])) {
            $keyword = strtolower($args['palabra_clave']);
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . $keyword . '%')
                  ->orWhere(DB::raw('LOWER(c.especificacion)'), 'LIKE', '%' . $keyword . '%');
            });
        }

        if (!empty($args['precio_maximo'])) {
            $query->where(DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $args['precio_maximo']);
        }

        // Limitar a los 10 más baratos o relevantes para evitar sobrecargar a Gemini
        $resultados = $query->orderBy('precio_final', 'asc')->limit(10)->get();

        if ($resultados->isEmpty()) {
            return ['mensaje' => 'No se encontraron componentes que coincidan con la búsqueda.'];
        }

        return $resultados->toArray();
    }

    private function esConsultaSoloCatalogo(string $message): bool
    {
        $msg = mb_strtolower(trim($message));

        // Si pide armar o recomienda equipo completo, NO es solo catálogo
        $palabrasArmado = ['armame', 'arma una', 'armar', 'recomiendame una pc', 'recomienda una pc', 'configurar pc', 'configura una pc', 'build pc', 'presupuesto de', 'mi presupuesto'];
        foreach ($palabrasArmado as $palabra) {
            if (str_contains($msg, $palabra)) {
                return false;
            }
        }

        // Patrones o palabras clave de consulta de catálogo
        $patronesCatalogo = [
            'procesador', 'procesadores', 'tarjeta de video', 'tarjetas de video', 'gpu', 'gpus',
            'cpu', 'cpus', 'placa madre', 'placas madre', 'motherboard', 'ram', 'memorias',
            'almacenamiento', 'disco', 'ssd', 'fuente de poder', 'psu', 'gabinete', 'case',
            'tienen disponible', 'tienen disponibles', 'que tienen', 'cuales tienen',
            'mostrar catalogo', 'ver catalogo', 'lista de', 'stock de', 'precios de'
        ];

        $coincidencias = 0;
        foreach ($patronesCatalogo as $patron) {
            if (str_contains($msg, $patron)) {
                $coincidencias++;
            }
        }

        return $coincidencias >= 1 && (
            str_contains($msg, '?') ||
            str_contains($msg, 'que') ||
            str_contains($msg, 'qué') ||
            str_contains($msg, 'tienen') ||
            str_contains($msg, 'muestra') ||
            str_contains($msg, 'mostrar') ||
            str_contains($msg, 'lista') ||
            str_contains($msg, 'disponible') ||
            str_contains($msg, 'catalogo') ||
            str_contains($msg, 'catálogo')
        );
    }

    private function generarRespuestaRapidaCatalogo(string $message): array
    {
        $msg = mb_strtolower($message);
        
        $categoriasABuscar = [];
        if (str_contains($msg, 'procesador') || str_contains($msg, 'cpu')) {
            $categoriasABuscar[] = 'CPU';
        }
        if (str_contains($msg, 'tarjeta') || str_contains($msg, 'gpu') || str_contains($msg, 'grafica') || str_contains($msg, 'vídeo') || str_contains($msg, 'video')) {
            $categoriasABuscar[] = 'GPU';
        }
        if (str_contains($msg, 'ram') || str_contains($msg, 'memoria')) {
            $categoriasABuscar[] = 'RAM';
        }
        if (str_contains($msg, 'placa') || str_contains($msg, 'motherboard') || str_contains($msg, 'madre')) {
            $categoriasABuscar[] = 'Motherboard';
        }
        if (str_contains($msg, 'disco') || str_contains($msg, 'ssd') || str_contains($msg, 'almacenamiento')) {
            $categoriasABuscar[] = 'Storage';
        }
        if (str_contains($msg, 'fuente') || str_contains($msg, 'psu')) {
            $categoriasABuscar[] = 'PSU';
        }
        if (str_contains($msg, 'gabinete') || str_contains($msg, 'case') || str_contains($msg, 'chasis')) {
            $categoriasABuscar[] = 'Case';
        }
        if (str_contains($msg, 'cooler') || str_contains($msg, 'refrigeracion')) {
            $categoriasABuscar[] = 'Cooler';
        }

        if (empty($categoriasABuscar)) {
            $categoriasABuscar = ['CPU', 'GPU'];
        }

        $nombresCategorias = [
            'CPU' => 'Procesadores (CPU)',
            'GPU' => 'Tarjetas de Video (GPU)',
            'RAM' => 'Memorias RAM',
            'Motherboard' => 'Placas Madre (Motherboards)',
            'Storage' => 'Almacenamiento (SSD/HDD)',
            'PSU' => 'Fuentes de Poder (PSU)',
            'Cooler' => 'Refrigeración / Coolers',
            'Case' => 'Gabinetes / Cases',
        ];

        $lineas = ["Aquí tienes los componentes disponibles en nuestro catálogo según tu consulta:\n"];

        foreach ($categoriasABuscar as $cat) {
            $items = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->where('pc.categoria', $cat)
                ->where('c.activo', true)
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->select(
                    'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.gama', 'c.stock',
                    DB::raw('CASE WHEN (c.descuento_activo = true OR c.descuento_activo = 1) AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
                )
                ->orderBy('c.gama', 'desc')
                ->orderBy('precio_final', 'asc')
                ->limit(10)
                ->get();

            if ($items->isNotEmpty()) {
                $titulo = $nombresCategorias[$cat] ?? $cat;
                $lineas[] = "**" . $titulo . ":**";
                
                foreach ($items as $item) {
                    $gamaTag = !empty($item->gama) ? " [" . ucfirst($item->gama) . "]" : "";
                    $precioFormateado = '$' . number_format($item->precio_final, 0, ',', '.');
                    $lineas[] = "- **" . $item->nombre . "**" . $gamaTag . " - " . $precioFormateado . " (Stock: " . $item->stock . ")";
                }
                $lineas[] = "";
            }
        }

        if (count($lineas) <= 1) {
            return [
                'type' => 'text',
                'message' => "Actualmente no encontramos componentes disponibles en esa categoría en nuestro catálogo. ¿Te gustaría consultar por otra categoría o armar una PC completa?"
            ];
        }

        $lineas[] = "¿Te gustaría armar una PC con alguno de estos componentes o necesitas asesoría para elegir?";

        return [
            'type' => 'text',
            'message' => implode("\n", $lineas)
        ];
    }

    private function formatMoney($amount): string
    {
        return '$' . number_format((float)$amount, 0, ',', '.');
    }
}
