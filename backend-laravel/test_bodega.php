<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Bodega;

try {
    $bodega = Bodega::first();
    $bodega->activa = true; // Set explicitly to boolean true
    $bodega->save();
    echo "Saved bodega successfully.\n";
} catch (\Exception $e) {
    echo "Error with boolean true: " . $e->getMessage() . "\n";
}
