<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Usuario;
use Illuminate\Support\Facades\Route;

$user = Usuario::where('correo', 'superadmin@pcmatch.com')->first();
if (!$user) {
    echo "Superadmin not found in DB\n";
    exit(1);
}

$token = $user->createToken('test_token')->plainTextToken;
echo "Token created: " . $token . "\n";

// Test request to Sanctum guard
$request = Illuminate\Http\Request::create('/api/auth/profile', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $app->handle($request);
echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Response Body: " . $response->getContent() . "\n";
