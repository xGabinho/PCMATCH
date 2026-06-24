<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\Usuario::where('correo', 'gabriel@gmail.com')->first();
$request = Illuminate\Http\Request::create('/api/auth/profile', 'PUT', ['nombre'=>'Gabriel', 'apellido'=>'Q', 'correo'=>'gabriel@gmail.com']);
$request->setUserResolver(function() use ($user) { return $user; });

$controller = app(\App\Http\Controllers\Api\AuthController::class);
try {
    $response = $controller->updateProfile($request);
    echo json_encode($response->original);
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
