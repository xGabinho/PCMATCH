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
1. NUNCA inventes componentes, precios, marcas ni especificaciones. Todo debe proceder únicamente de la herramienta 'ver_inventario' o 'build_pc'.
2. PREGUNTAS INTRODUCTORIAS DE ARMADO: Si el usuario desea armar una PC o recibir recomendación de equipo completo y NO ha especificado alguno de los 3 datos principales (Gama/Nivel, Presupuesto máximo, y Enfoque de uso), pregúntaselos amablemente.
3. BÚSQUEDA DE COMPONENTES: Si el usuario pregunta por piezas específicas, disponibilidad, precios o stock (ej: '¿tienen tarjetas RTX 3060?'), usa INMEDIATAMENTE 'ver_inventario' sin exigir presupuesto obligatoriamente.
4. RECOMENDACIÓN DE PC COMPLETA: Usa 'build_pc' únicamente cuando tengas o el usuario te provea el uso principal, gama/desempeño y presupuesto aproximado.
5. DUDAS GENERALES: Para preguntas teóricas sobre hardware, responde breve y concisamente. No repitas lo que el usuario dijo.";

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
                        if(is_array($errorData)) {
                             return response()->json([
                                'type' => 'text',
                                'message' => "Lo siento, hubo un problema al armar tu PC: " . ($errorData['message'] ?? '') . " " . ($errorData['detalle'] ?? '')
                            ]);
                        }
                        return response()->json([
                            'type' => 'text',
                            'message' => 'Hubo un error interno al intentar armar la PC con el inventario actual.'
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
                'message' => $textResponse ?? 'No pude generar una respuesta.'
            ]);
        }

        return response()->json([
            'type' => 'text',
            'message' => 'El asistente tardó demasiado en procesar la información. Inténtalo de nuevo.'
        ]);
    }

    private function buscarInventario(array $args): array
    {
        $query = DB::table('componentes as c')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->whereRaw("c.activo IS TRUE")
            ->where('c.stock', '>', 0)
            ->whereNull('c.deleted_at')
            ->select(
                'pc.nombre', 'pc.categoria', 'c.especificacion',
                DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END as precio_final'),
                'c.stock'
            );

        if (!empty($args['categoria'])) {
            $query->where('pc.categoria', 'ILIKE', '%' . $args['categoria'] . '%');
        }

        if (!empty($args['palabra_clave'])) {
            $keyword = strtolower($args['palabra_clave']);
            $query->where(function ($q) use ($keyword) {
                $q->where(DB::raw('LOWER(pc.nombre)'), 'LIKE', '%' . $keyword . '%')
                  ->orWhere(DB::raw('LOWER(c.especificacion)'), 'LIKE', '%' . $keyword . '%');
            });
        }

        if (!empty($args['precio_maximo'])) {
            $query->where(DB::raw('CASE WHEN c.descuento_activo = true AND c.descuento_porcentaje > 0 THEN ROUND(c.precio * (1 - c.descuento_porcentaje / 100), 2) ELSE c.precio END'), '<=', $args['precio_maximo']);
        }

        // Limitar a los 10 más baratos o relevantes para evitar sobrecargar a Gemini
        $resultados = $query->orderBy('precio_final', 'asc')->limit(10)->get();

        if ($resultados->isEmpty()) {
            return ['mensaje' => 'No se encontraron componentes que coincidan con la búsqueda.'];
        }

        return $resultados->toArray();
    }
}
