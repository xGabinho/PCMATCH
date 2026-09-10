<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Services\RecomendacionService;
use App\Services\CompatibilidadService;
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
1. NUNCA inventes componentes, precios, marcas ni especificaciones. Para precios y disponibilidad usa EXCLUSIVAMENTE las herramientas de PCMATCH.
2. NUNCA respondas con un mensaje de error sin acompañarlo de al menos una alternativa concreta o pregunta que permita avanzar.
3. PREGUNTAS INTRODUCTORIAS DE ARMADO: Si el usuario desea armar una PC y NO ha especificado Gama/Nivel, Presupuesto y Uso, pregúntaselos amablemente.
4. BÚSQUEDA DE COMPONENTES: Si pregunta por piezas, precios o stock, usa 'ver_inventario' INMEDIATAMENTE.
5. RECOMENDACIÓN COMPLETA: Usa 'build_pc' cuando el usuario quiera armar/cotizar PC y mencione presupuesto y/o uso. Si menciona estudiar Y jugar, usa 'gaming'.
6. PRESUPUESTO: Extrae número limpio (de '3.800.000' extrae 3800000). 'media-alta' → 'alta' o 'media' según presupuesto.
7. SEGUIMIENTO: Si pregunta si son los mejores componentes, ejecuta 'build_pc' con desempeno='alta'.
8. PRESUPUESTO INSUFICIENTE: Indica monto mínimo real, ofrece configuración económica y pregunta si ajustar.
9. COMPATIBILIDAD: Antes de cerrar una configuración, menciona si todos los componentes son compatibles. Si el usuario pregunta explícitamente, usa 'verificar_compatibilidad'.
10. COMPARACIONES: Si pregunta cuál es mejor entre componentes, usa 'comparar_componentes' con los términos de búsqueda.
11. UPGRADES: Si quiere mejorar su equipo actual, usa 'recomendar_upgrade' con los componentes que mencione.
12. MODIFICACIONES: Si tras una build pide cambiar un componente, usa 'modificar_build'.
13. CONSUMO: Si pregunta qué fuente necesita o cuántos watts consume, usa 'calcular_consumo_energetico'.
14. CONOCIMIENTO GENERAL: Para preguntas educativas/conceptuales sobre hardware (ej: '¿qué es un socket?', '¿DDR4 vs DDR5?'), usa 'consultar_conocimiento_general'. NUNCA inventes precios ni stock.
15. FUERA DE ALCANCE: Si la pregunta no tiene relación con hardware, armado de PCs o tecnología, responde cortésmente que solo puedes ayudar con temas de computadoras.
16. TONO: Respuestas breves, cifras concretas, cierra SIEMPRE con pregunta accionable.";

        $payload = [
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'contents' => $geminiContents,
            'tools' => [
                [
                    'functionDeclarations' => [
                        // ═══ HERRAMIENTA 1: build_pc ═══
                        [
                            'name' => 'build_pc',
                            'description' => 'Arma y recomienda una PC completa ideal basada en uso, desempeño y presupuesto. Valida compatibilidad automáticamente.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'uso' => [
                                        'type' => 'STRING',
                                        'description' => 'Uso principal. Si menciona estudiar y jugar, prioriza gaming.',
                                        'enum' => ['gaming', 'estudio', 'oficina', 'diseño', 'streaming', 'edicion_video', 'servidor', 'ia_machine_learning']
                                    ],
                                    'desempeno' => [
                                        'type' => 'STRING',
                                        'description' => 'Nivel de desempeño deseado.',
                                        'enum' => ['alta', 'media', 'baja']
                                    ],
                                    'presupuesto_max' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Presupuesto máximo en número entero sin puntos ni símbolos.'
                                    ],
                                    'marca_preferida' => [
                                        'type' => 'STRING',
                                        'description' => 'Preferencia de marca del usuario para CPU/GPU.',
                                        'enum' => ['AMD', 'Intel', 'NVIDIA', 'ninguna']
                                    ],
                                    'factor_forma' => [
                                        'type' => 'STRING',
                                        'description' => 'Factor de forma preferido del gabinete.',
                                        'enum' => ['ATX', 'Micro-ATX', 'Mini-ITX', 'sin_preferencia']
                                    ]
                                ],
                                'required' => ['uso', 'desempeno']
                            ]
                        ],
                        // ═══ HERRAMIENTA 2: ver_inventario ═══
                        [
                            'name' => 'ver_inventario',
                            'description' => 'Busca componentes en la base de datos de PCMATCH por categoría, palabra clave, marca o precio.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'categoria' => [
                                        'type' => 'STRING',
                                        'description' => 'Categoría del componente.',
                                        'enum' => ['CPU', 'GPU', 'RAM', 'Motherboard', 'Storage', 'PSU', 'Cooler', 'Case']
                                    ],
                                    'palabra_clave' => [
                                        'type' => 'STRING',
                                        'description' => 'Palabra clave para buscar (ej: Ryzen, RTX 3060, 16GB).',
                                    ],
                                    'precio_maximo' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Presupuesto máximo para filtrar.',
                                    ],
                                    'marca' => [
                                        'type' => 'STRING',
                                        'description' => 'Filtrar por marca (ej: AMD, Intel, NVIDIA, Corsair, ASUS).',
                                    ],
                                    'orden' => [
                                        'type' => 'STRING',
                                        'description' => 'Orden de resultados.',
                                        'enum' => ['precio_asc', 'precio_desc', 'relevancia']
                                    ]
                                ]
                            ]
                        ],
                        // ═══ HERRAMIENTA 3: verificar_compatibilidad ═══
                        [
                            'name' => 'verificar_compatibilidad',
                            'description' => 'Verifica la compatibilidad entre componentes de hardware: socket CPU/Mobo, tipo RAM, factor forma, espacio GPU en case, y consumo vs PSU.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'componentes_ids' => [
                                        'type' => 'ARRAY',
                                        'description' => 'Array de IDs de componentes del inventario PCMATCH.',
                                        'items' => ['type' => 'NUMBER']
                                    ],
                                    'componentes_texto' => [
                                        'type' => 'STRING',
                                        'description' => 'Texto libre describiendo los componentes a verificar (ej: "Ryzen 5 5600X con B450M-A y DDR4").',
                                    ]
                                ]
                            ]
                        ],
                        // ═══ HERRAMIENTA 4: comparar_componentes ═══
                        [
                            'name' => 'comparar_componentes',
                            'description' => 'Compara 2-4 componentes de la misma categoría del inventario PCMATCH (precio, rendimiento, eficiencia, etc).',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'categoria' => [
                                        'type' => 'STRING',
                                        'description' => 'Categoría de los componentes a comparar.',
                                        'enum' => ['CPU', 'GPU', 'RAM', 'Motherboard', 'Storage', 'PSU', 'Cooler', 'Case']
                                    ],
                                    'terminos_busqueda' => [
                                        'type' => 'ARRAY',
                                        'description' => 'Array de 2-4 términos para buscar (ej: ["RTX 4060", "RX 7600"]).',
                                        'items' => ['type' => 'STRING']
                                    ],
                                    'criterio' => [
                                        'type' => 'STRING',
                                        'description' => 'Criterio principal de comparación.',
                                        'enum' => ['precio', 'rendimiento', 'eficiencia_energetica', 'general']
                                    ]
                                ],
                                'required' => ['categoria', 'terminos_busqueda']
                            ]
                        ],
                        // ═══ HERRAMIENTA 5: recomendar_upgrade ═══
                        [
                            'name' => 'recomendar_upgrade',
                            'description' => 'Recomienda la mejor mejora para un equipo existente según presupuesto y uso. Identifica el cuello de botella y busca reemplazo compatible.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'componentes_actuales' => [
                                        'type' => 'ARRAY',
                                        'description' => 'Array de objetos con los componentes actuales del usuario.',
                                        'items' => [
                                            'type' => 'OBJECT',
                                            'properties' => [
                                                'categoria' => ['type' => 'STRING'],
                                                'nombre' => ['type' => 'STRING']
                                            ]
                                        ]
                                    ],
                                    'presupuesto_max' => [
                                        'type' => 'NUMBER',
                                        'description' => 'Presupuesto disponible para el upgrade.'
                                    ],
                                    'uso' => [
                                        'type' => 'STRING',
                                        'description' => 'Uso principal del equipo.',
                                        'enum' => ['gaming', 'estudio', 'oficina', 'diseño']
                                    ]
                                ],
                                'required' => ['componentes_actuales', 'presupuesto_max', 'uso']
                            ]
                        ],
                        // ═══ HERRAMIENTA 6: modificar_build ═══
                        [
                            'name' => 'modificar_build',
                            'description' => 'Modifica un build existente sustituyendo un componente por otro, recalcula el total y revalida compatibilidad.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'build_actual' => [
                                        'type' => 'ARRAY',
                                        'description' => 'Array de componentes del build actual (incluir al menos categoria y nombre).',
                                        'items' => [
                                            'type' => 'OBJECT',
                                            'properties' => [
                                                'categoria' => ['type' => 'STRING'],
                                                'nombre' => ['type' => 'STRING'],
                                                'id' => ['type' => 'NUMBER']
                                            ]
                                        ]
                                    ],
                                    'instruccion' => [
                                        'type' => 'STRING',
                                        'description' => 'Instrucción de cambio (ej: "cambiar GPU por RTX 4070", "usar RAM de 32GB")'
                                    ]
                                ],
                                'required' => ['build_actual', 'instruccion']
                            ]
                        ],
                        // ═══ HERRAMIENTA 7: calcular_consumo_energetico ═══
                        [
                            'name' => 'calcular_consumo_energetico',
                            'description' => 'Calcula el consumo estimado en watts de un conjunto de componentes y recomienda PSUs adecuadas del inventario.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'componentes_ids' => [
                                        'type' => 'ARRAY',
                                        'description' => 'Array de IDs de componentes del inventario.',
                                        'items' => ['type' => 'NUMBER']
                                    ],
                                    'componentes_texto' => [
                                        'type' => 'STRING',
                                        'description' => 'Texto libre con los componentes (ej: "Ryzen 7 5700X con RTX 3070").',
                                    ]
                                ]
                            ]
                        ],
                        // ═══ HERRAMIENTA 8: consultar_conocimiento_general ═══
                        [
                            'name' => 'consultar_conocimiento_general',
                            'description' => 'Responde preguntas educativas o conceptuales sobre hardware (ej: qué es un socket, diferencias DDR4 vs DDR5, qué significan los núcleos). NO incluir precios ni stock inventado.',
                            'parameters' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'pregunta' => [
                                        'type' => 'STRING',
                                        'description' => 'La pregunta conceptual del usuario sobre hardware.'
                                    ]
                                ],
                                'required' => ['pregunta']
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
        // Modelos activos con soporte nativo de function calling
        $models = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.6-flash'];
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

            // Si se excede la cuota o el modelo está saturado (503 / 429 / 500), esperar y reintentar con el siguiente modelo
            if (in_array($response->status(), [429, 500, 503])) {
                sleep(2);
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
                        $presupuesto = 0;

                        if (is_numeric($rawBudget)) {
                            $presupuesto = (float) $rawBudget;
                        } elseif (is_string($rawBudget)) {
                            $clean = preg_replace('/[^\d]/', '', $rawBudget);
                            if (!empty($clean)) {
                                $presupuesto = (float) $clean;
                            }
                        }

                        // Normalizar uso
                        $usoRaw = mb_strtolower(trim($args['uso'] ?? 'gaming'));
                        if (str_contains($usoRaw, 'game') || str_contains($usoRaw, 'jueg') || str_contains($usoRaw, 'jugar')) {
                            $uso = 'gaming';
                        } elseif (str_contains($usoRaw, 'diseñ') || str_contains($usoRaw, 'render') || str_contains($usoRaw, 'edici')) {
                            $uso = 'diseño';
                        } elseif (str_contains($usoRaw, 'estudi') || str_contains($usoRaw, 'tarea')) {
                            $uso = 'estudio';
                        } elseif (str_contains($usoRaw, 'oficin') || str_contains($usoRaw, 'trabajo')) {
                            $uso = 'oficina';
                        } else {
                            $uso = in_array($usoRaw, ['gaming', 'estudio', 'oficina', 'diseño']) ? $usoRaw : 'gaming';
                        }

                        // Normalizar desempeño
                        $desempenoRaw = mb_strtolower(trim($args['desempeno'] ?? 'media'));
                        if (str_contains($desempenoRaw, 'media alta') || str_contains($desempenoRaw, 'media-alta') || str_contains($desempenoRaw, 'media_alta')) {
                            $desempeno = ($presupuesto > 0 && $presupuesto < 4500000) ? 'media' : 'alta';
                        } elseif (str_contains($desempenoRaw, 'alta') || str_contains($desempenoRaw, 'alto')) {
                            $desempeno = 'alta';
                        } elseif (str_contains($desempenoRaw, 'baja') || str_contains($desempenoRaw, 'bajo') || str_contains($desempenoRaw, 'entrada')) {
                            $desempeno = 'baja';
                        } else {
                            $desempeno = 'media';
                        }

                        if ($presupuesto <= 0) {
                            $presupuesto = match ($desempeno) {
                                'alta' => 10000000.0,
                                'media' => 5000000.0,
                                'baja' => 2500000.0,
                                default => 5000000.0,
                            };
                        }

                        $service = new RecomendacionService();
                        $buildResult = $service->buildPcIdeal($uso, $desempeno, $presupuesto);
                        
                        $opcionesCount = count($buildResult['opciones'] ?? []);
                        $mejorOpt = $buildResult['opciones'][0] ?? null;
                        $totalFormateado = $mejorOpt ? $this->formatMoney($mejorOpt['total']) : '';
                        $msg = "¡Excelente! He analizado nuestro inventario para tu presupuesto de " . $this->formatMoney($presupuesto) . " y preparé " . ($opcionesCount > 1 ? "$opcionesCount configuraciones optimizadas" : "la mejor configuración posible") . ($totalFormateado ? " con un total de $totalFormateado" : "") . ". Puedes comparar las opciones en las pestañas y cargar la que prefieras al ensamblador.";

                        return response()->json([
                            'type' => 'build',
                            'buildResult' => $buildResult,
                            'message' => $msg
                        ]);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Chatbot build_pc error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
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

                // ═══ Herramientas que devuelven datos a Gemini para generar respuesta ═══
                $toolsConRespuesta = ['ver_inventario', 'verificar_compatibilidad', 'comparar_componentes', 'recomendar_upgrade', 'modificar_build', 'calcular_consumo_energetico', 'consultar_conocimiento_general'];

                if (in_array($name, $toolsConRespuesta)) {
                    $resultadoHerramienta = $this->ejecutarHerramienta($name, $args);

                    // Preservar la respuesta completa del modelo (incluyendo thoughtSignature)
                    $payload['contents'][] = $candidates['content'];

                    // Agregar la respuesta de la función
                    $payload['contents'][] = [
                        'role' => 'function',
                        'parts' => [
                            [
                                'functionResponse' => [
                                    'name' => $name,
                                    'response' => [
                                        'name' => $name,
                                        'content' => $resultadoHerramienta
                                    ]
                                ]
                            ]
                        ]
                    ];
                    $retryCount++;
                    continue;
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

    /**
     * Dispatcher: ejecuta la herramienta correspondiente y devuelve datos para Gemini.
     */
    private function ejecutarHerramienta(string $name, array $args): array
    {
        return match ($name) {
            'ver_inventario'              => ['items' => $this->buscarInventario($args)],
            'verificar_compatibilidad'    => $this->handleVerificarCompatibilidad($args),
            'comparar_componentes'        => $this->handleCompararComponentes($args),
            'recomendar_upgrade'          => $this->handleRecomendarUpgrade($args),
            'modificar_build'             => $this->handleModificarBuild($args),
            'calcular_consumo_energetico' => $this->handleCalcularConsumo($args),
            'consultar_conocimiento_general' => $this->handleConocimientoGeneral($args),
            default => ['error' => "Herramienta desconocida: {$name}"],
        };
    }

    private function buscarInventario(array $args): array
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion',
                'pc.socket', 'pc.tipo_ram', 'pc.consumo_watts', 'pc.wattage', 'pc.marca',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
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
            $query->where(DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $args['precio_maximo']);
        }

        if (!empty($args['marca'])) {
            $query->where(DB::raw('LOWER(pc.marca)'), 'LIKE', '%' . strtolower($args['marca']) . '%');
        }

        // Orden de resultados
        $orden = $args['orden'] ?? 'precio_asc';
        match ($orden) {
            'precio_desc' => $query->orderBy('precio_final', 'desc'),
            'relevancia'  => $query->orderBy('c.stock', 'desc')->orderBy('precio_final', 'asc'),
            default       => $query->orderBy('precio_final', 'asc'),
        };

        $resultados = $query->limit(10)->get();

        if ($resultados->isEmpty()) {
            return ['mensaje' => 'No se encontraron componentes que coincidan con la búsqueda.'];
        }

        return $resultados->toArray();
    }

    /**
     * Verificar compatibilidad de componentes.
     */
    private function handleVerificarCompatibilidad(array $args): array
    {
        $compatService = new CompatibilidadService();
        $componentes = [];

        // Por IDs
        if (!empty($args['componentes_ids'])) {
            $ids = is_array($args['componentes_ids']) ? $args['componentes_ids'] : [$args['componentes_ids']];
            $ids = array_map('intval', $ids);
            $componentes = $compatService->enriquecerConDatosCatalogo($ids);
        }

        // Por texto libre (separar por saltos de línea, comas, o palabras clave)
        if (empty($componentes) && !empty($args['componentes_texto'])) {
            $texto = $args['componentes_texto'];
            $lineas = preg_split('/[\r\n]+/', $texto);
            $candidatos = [];
            foreach ($lineas as $l) {
                // Si la línea tiene formato "Categoría: Componente"
                if (preg_match('/^[^:]+:\s*(.+)$/i', trim($l), $m)) {
                    $candidatos[] = trim($m[1]);
                } else {
                    $subPartes = preg_split('/\s+(con|y|,|;|\+)\s+/i', $l);
                    foreach ($subPartes as $sp) {
                        $candidatos[] = trim($sp);
                    }
                }
            }
            foreach ($candidatos as $parte) {
                $parte = trim($parte);
                if (strlen($parte) > 2) {
                    $comp = $compatService->buscarPorTexto($parte);
                    if ($comp) {
                        $componentes[] = $comp;
                    }
                }
            }
        }

        if (count($componentes) < 2) {
            return [
                'componentes_encontrados_en_tienda' => count($componentes),
                'mensaje' => 'Varios o todos los componentes listados no se encuentran en el inventario actual de PCMATCH. Por favor analiza la compatibilidad técnica y física de estos componentes usando tu conocimiento experto de hardware (socket, RAM, dimensiones físicas, fuente requerida), señala claramente cada incompatibilidad al usuario y aclara cuáles piezas están o no en el catálogo.'
            ];
        }

        $resultado = $compatService->validarConjunto($componentes);
        $resultado['componentes_evaluados'] = array_map(function ($c) {
            return [
                'nombre'   => is_object($c) ? $c->nombre : ($c['nombre'] ?? ''),
                'categoria'=> is_object($c) ? $c->categoria : ($c['categoria'] ?? ''),
                'socket'   => is_object($c) ? ($c->socket ?? '') : ($c['socket'] ?? ''),
                'tipo_ram' => is_object($c) ? ($c->tipo_ram ?? '') : ($c['tipo_ram'] ?? ''),
            ];
        }, $componentes);

        return $resultado;
    }

    /**
     * Comparar componentes de la misma categoría.
     */
    private function handleCompararComponentes(array $args): array
    {
        $categoria = $args['categoria'] ?? 'GPU';
        $terminos = $args['terminos_busqueda'] ?? [];
        $criterio = $args['criterio'] ?? 'general';

        if (!is_array($terminos) || count($terminos) < 2) {
            return ['error' => 'Se necesitan al menos 2 términos de búsqueda para comparar.'];
        }

        $compatService = new CompatibilidadService();
        return $compatService->compararComponentes($categoria, $terminos, $criterio);
    }

    /**
     * Recomendar upgrade para equipo existente.
     */
    private function handleRecomendarUpgrade(array $args): array
    {
        $componentesActuales = $args['componentes_actuales'] ?? [];
        $presupuesto = $this->limpiarPresupuesto($args['presupuesto_max'] ?? 0);
        $uso = $this->normalizarUso($args['uso'] ?? 'gaming');

        if (empty($componentesActuales) || $presupuesto <= 0) {
            return ['error' => 'Se necesitan componentes actuales y presupuesto para recomendar un upgrade.'];
        }

        $compatService = new CompatibilidadService();
        return $compatService->recomendarUpgrade($componentesActuales, $presupuesto, $uso);
    }

    /**
     * Modificar un build existente.
     */
    private function handleModificarBuild(array $args): array
    {
        $buildActual = $args['build_actual'] ?? [];
        $instruccion = $args['instruccion'] ?? '';

        if (empty($buildActual) || empty($instruccion)) {
            return ['error' => 'Se necesita el build actual y la instrucción de modificación.'];
        }

        // Identificar qué categoría se quiere cambiar desde la instrucción
        $instrLower = mb_strtolower($instruccion);
        $categoriaTarget = null;
        $terminoBusqueda = $instruccion;

        $mapPatrones = [
            'GPU'         => ['gpu', 'tarjeta', 'gráfica', 'grafica', 'video'],
            'CPU'         => ['cpu', 'procesador'],
            'RAM'         => ['ram', 'memoria'],
            'Motherboard' => ['motherboard', 'placa', 'board'],
            'Storage'     => ['disco', 'ssd', 'nvme', 'almacenamiento', 'storage'],
            'PSU'         => ['psu', 'fuente'],
            'Cooler'      => ['cooler', 'refrigeración', 'refrigeracion', 'ventilador'],
            'Case'        => ['case', 'gabinete', 'chasis'],
        ];

        foreach ($mapPatrones as $cat => $patrones) {
            foreach ($patrones as $patron) {
                if (str_contains($instrLower, $patron)) {
                    $categoriaTarget = $cat;
                    break 2;
                }
            }
        }

        if (!$categoriaTarget) {
            return ['error' => 'No se pudo identificar qué componente deseas cambiar. Intenta ser más específico (ej: "cambiar GPU por RTX 4070").'];
        }

        // Buscar el nuevo componente
        $nuevoComp = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->where('pc.categoria', $categoriaTarget)
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->where(function ($q) use ($instrLower) {
                $q->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . $instrLower . '%')
                  ->orWhere(DB::raw('LOWER(c.especificacion)'), 'LIKE', '%' . $instrLower . '%');
            })
            ->select(
                'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.gama',
                'pc.socket', 'pc.tipo_ram', 'pc.consumo_watts', 'pc.wattage', 'pc.largo_mm',
                DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'c.stock', 'b.nombre as bodega'
            )
            ->orderBy('precio_final', 'DESC')
            ->first();

        if (!$nuevoComp) {
            // Intentar búsqueda más amplia extrayendo modelo del texto
            preg_match('/(?:por|a|con)\s+(.+)/i', $instruccion, $matches);
            $modeloBuscar = $matches[1] ?? $instruccion;

            $nuevoComp = DB::table('componentes as c')
                ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
                ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
                ->where('pc.categoria', $categoriaTarget)
                ->whereRaw("c.activo IS TRUE")
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . strtolower(trim($modeloBuscar)) . '%')
                ->select(
                    'c.id', 'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.gama',
                    'pc.socket', 'pc.tipo_ram', 'pc.consumo_watts', 'pc.wattage', 'pc.largo_mm',
                    DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                    'c.stock', 'b.nombre as bodega'
                )
                ->orderBy('precio_final', 'DESC')
                ->first();
        }

        if (!$nuevoComp) {
            return ['error' => "No encontré el componente '{$instruccion}' en nuestro inventario de {$categoriaTarget}. ¿Deseas ver las opciones disponibles?"];
        }

        // Reconstruir build con el componente sustituido
        $nuevoTotal = 0;
        $buildModificado = [];
        foreach ($buildActual as $comp) {
            $cat = $comp['categoria'] ?? '';
            if ($cat === $categoriaTarget) {
                $buildModificado[] = [
                    'categoria'      => $nuevoComp->categoria,
                    'nombre'         => $nuevoComp->nombre,
                    'especificacion' => $nuevoComp->especificacion,
                    'precio_final'   => $nuevoComp->precio_final,
                    'id'             => $nuevoComp->id,
                ];
                $nuevoTotal += (float) $nuevoComp->precio_final;
            } else {
                $buildModificado[] = $comp;
                $nuevoTotal += (float) ($comp['precio_final'] ?? 0);
            }
        }

        // Revalidar compatibilidad
        $idsParaValidar = array_filter(array_map(function ($c) { return $c['id'] ?? null; }, $buildModificado));
        $compatService = new CompatibilidadService();
        $validacion = [];
        if (count($idsParaValidar) >= 2) {
            $componentes = $compatService->enriquecerConDatosCatalogo($idsParaValidar);
            $validacion = $compatService->validarConjunto($componentes);
        }

        return [
            'build_modificado'   => $buildModificado,
            'total_nuevo'        => round($nuevoTotal, 2),
            'componente_cambiado'=> [
                'categoria' => $categoriaTarget,
                'nuevo'     => $nuevoComp->nombre,
                'precio'    => $nuevoComp->precio_final,
            ],
            'compatibilidad'     => $validacion,
        ];
    }

    /**
     * Calcular consumo energético.
     */
    private function handleCalcularConsumo(array $args): array
    {
        $compatService = new CompatibilidadService();
        $componentes = [];

        if (!empty($args['componentes_ids'])) {
            $ids = array_map('intval', is_array($args['componentes_ids']) ? $args['componentes_ids'] : [$args['componentes_ids']]);
            $componentes = $compatService->enriquecerConDatosCatalogo($ids);
        }

        if (empty($componentes) && !empty($args['componentes_texto'])) {
            $partes = preg_split('/\s+(con|y|,|;|\+)\s+/i', $args['componentes_texto']);
            foreach ($partes as $parte) {
                $parte = trim($parte);
                if (strlen($parte) > 2) {
                    $comp = $compatService->buscarPorTexto($parte);
                    if ($comp) $componentes[] = $comp;
                }
            }
        }

        if (empty($componentes)) {
            return ['error' => 'No se encontraron componentes para calcular el consumo. Indica nombres de productos o IDs.'];
        }

        return $compatService->calcularConsumo($componentes);
    }

    /**
     * Consultar conocimiento general (pasa la pregunta de vuelta a Gemini con contexto).
     */
    private function handleConocimientoGeneral(array $args): array
    {
        $pregunta = $args['pregunta'] ?? '';
        return [
            'tipo'     => 'conocimiento_general',
            'pregunta' => $pregunta,
            'nota'     => 'Responde esta pregunta conceptual sobre hardware de computadoras. Puedes usar tu conocimiento general, pero NO inventes precios, stock ni disponibilidad. Si la respuesta involucra productos, sugiere usar ver_inventario para precios reales.'
        ];
    }

    // ═══════════════════════════════════════
    // Helpers
    // ═══════════════════════════════════════

    private function limpiarPresupuesto($raw): float
    {
        if (is_numeric($raw)) return (float) $raw;
        if (is_string($raw)) {
            $clean = preg_replace('/[^\d]/', '', $raw);
            return !empty($clean) ? (float) $clean : 0;
        }
        return 0;
    }

    private function normalizarUso(string $usoRaw): string
    {
        $u = mb_strtolower(trim($usoRaw));
        if (str_contains($u, 'game') || str_contains($u, 'jueg') || str_contains($u, 'jugar')) return 'gaming';
        if (str_contains($u, 'diseñ') || str_contains($u, 'render') || str_contains($u, 'edici')) return 'diseño';
        if (str_contains($u, 'estudi') || str_contains($u, 'tarea')) return 'estudio';
        if (str_contains($u, 'oficin') || str_contains($u, 'trabajo')) return 'oficina';
        return in_array($u, ['gaming', 'estudio', 'oficina', 'diseño']) ? $u : 'gaming';
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

        // Si es pregunta conceptual, no es catálogo
        $conceptuales = ['qué es', 'que es', 'diferencia entre', 'para qué sirve', 'para que sirve', 'cómo funciona', 'como funciona', 'explicame', 'explícame'];
        foreach ($conceptuales as $patron) {
            if (str_contains($msg, $patron)) return false;
        }

        // Patrones de consulta de catálogo
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
                ->whereRaw("c.activo IS TRUE")
                ->where('c.stock', '>', 0)
                ->whereNull('c.deleted_at')
                ->select(
                    'pc.nombre', 'pc.categoria', 'c.especificacion', 'c.gama', 'c.stock',
                    DB::raw('CASE WHEN c.descuento_activo IS TRUE AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final')
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
