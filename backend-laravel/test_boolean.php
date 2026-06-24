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

echo "Testing string 'true'...\n";
try {
    $c1 = Componente::create([
        'sku' => 'TEST-001-' . time(),
        'bodega_id' => 1,
        'producto_id' => 1,
        'especificacion' => 'Test 1',
        'gama' => 'media',
        'precio' => 10,
        'stock' => 1,
        'activo' => 'true', // string
    ]);
    echo "String 'true' worked! ID: {$c1->id}\n";
} catch (\Exception $e) {
    echo "String 'true' failed: " . $e->getMessage() . "\n";
}

echo "Testing DB::raw('true')...\n";
try {
    $c2 = Componente::create([
        'sku' => 'TEST-002-' . time(),
        'bodega_id' => 1,
        'producto_id' => 1,
        'especificacion' => 'Test 2',
        'gama' => 'media',
        'precio' => 10,
        'stock' => 1,
        'activo' => DB::raw('true'),
    ]);
    echo "DB::raw('true') worked! ID: {$c2->id}\n";
} catch (\Exception $e) {
    echo "DB::raw('true') failed: " . $e->getMessage() . "\n";
}
