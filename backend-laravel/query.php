<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Bodega;

try {
    $bodega = Bodega::first();
    if ($bodega) {
        $bodega->activa = true; 
        $bodega->save();
        echo "Saved bodega successfully.\n";
    } else {
        echo "No bodega found.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
