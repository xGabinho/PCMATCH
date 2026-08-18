<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\RecomendacionService;
use Exception;

class ChatbotBehaviorTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test fast-path catalog query response
     */
    public function test_catalog_fast_path_response()
    {
        $pId = \Illuminate\Support\Facades\DB::table('productos_catalogo')->insertGetId([
            'nombre' => 'Ryzen 5 5600',
            'categoria' => 'CPU'
        ]);
        \Illuminate\Support\Facades\DB::table('componentes')->insert([
            'sku' => 'CPU-001-000-TEST',
            'producto_id' => $pId,
            'especificacion' => '6 núcleos 12 hilos',
            'precio' => 500000,
            'stock' => 10,
            'activo' => 1
        ]);

        $response = $this->postJson('/api/chat', [
            'messages' => [
                ['role' => 'user', 'content' => '¿Qué procesadores y tarjetas de video tienen disponibles?']
            ]
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'type' => 'text'
        ]);

        $content = $response->json('message');
        $this->assertStringContainsString('disponibles en nuestro catálogo', $content);
        $this->assertStringContainsString('¿Te gustaría armar una PC', $content);
    }

    /**
     * Test RecomendacionService returns minimal build or structured low budget exception data
     */
    public function test_recomendacion_service_budget_handling()
    {
        $service = new RecomendacionService();

        try {
            // Presupuesto absurdamente bajo (100 COP)
            $result = $service->buildPcIdeal('gaming', 'baja', 100);
            
            // Si logra armar la build mínima:
            $this->assertTrue($result['success']);
            $this->assertNotEmpty($result['opciones']);
        } catch (Exception $e) {
            $data = json_decode($e->getMessage(), true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('presupuesto_minimo_estimado', $data);
            $this->assertArrayHasKey('diferencia', $data);
            $this->assertArrayHasKey('build_economica', $data);
            $this->assertGreaterThan(0, $data['presupuesto_minimo_estimado']);
        }
    }
}
