<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bodega;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ComponenteController;
use Illuminate\Support\Facades\DB;
use App\Models\Componente;

echo "Testing boolean true...\n";
try {
    $c3 = Componente::create([
        'sku' => 'TEST-003-' . time(),
        'bodega_id' => 1,
        'producto_id' => 1,
        'especificacion' => 'Test 3',
        'gama' => 'media',
        'precio' => 10,
        'stock' => 1,
        'activo' => true, // boolean
    ]);
    echo "Boolean true worked! ID: {$c3->id}\n";
} catch (\Exception $e) {
    echo "Boolean true failed: " . $e->getMessage() . "\n";
}
