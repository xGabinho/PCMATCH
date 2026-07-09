<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$bodegas = Illuminate\Support\Facades\DB::table('bodegas')->where('activa', true)->get();
echo "BODEGAS with true: " . count($bodegas) . "\n";

$bodegas2 = Illuminate\Support\Facades\DB::table('bodegas')->where('activa', 1)->get();
echo "BODEGAS with 1: " . count($bodegas2) . "\n";

$bodegas3 = Illuminate\Support\Facades\DB::table('bodegas')->where('activa', '1')->get();
echo "BODEGAS with '1': " . count($bodegas3) . "\n";

// Also check the JSON response structure
$controller = app()->make(\App\Http\Controllers\Api\AnaliticaController::class);
$request = \Illuminate\Http\Request::create('/api/analiticas/selectores', 'GET');
// Mock user
$user = new \stdClass();
$user->rol = 'superadmin';
$request->setUserResolver(function() use ($user) { return $user; });

$response = $controller->selectores($request);
echo "RESPONSE: " . $response->getContent() . "\n";
