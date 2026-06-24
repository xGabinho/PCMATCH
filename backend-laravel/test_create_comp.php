<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bodega;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ComponenteController;

// Find a bodega user
$bodega = Bodega::first();
if (!$bodega) {
    die("No bodega found\n");
}

$producto = DB::table('productos_catalogo')->first();
if (!$producto) {
    die("No producto_catalogo found\n");
}

echo "Testing store with bodega: {$bodega->correo}\n";

$request = Request::create('/api/componentes', 'POST', [
    'producto_id' => $producto->id,
    'especificacion' => 'Test Spec ' . time(),
    'gama' => 'media',
    'precio' => 150,
    'stock' => 10,
]);
$request->setUserResolver(function () use ($bodega) {
    return $bodega;
});

$controller = new ComponenteController();
$response = $controller->store($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: " . $response->getContent() . "\n";
