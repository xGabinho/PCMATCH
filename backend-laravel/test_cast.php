<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\Bodega;

$bodega = Bodega::first();
$bodega->activa = 1;
try {
    $bodega->save();
    echo "Saved integer 1 without cast successfully.\n";
} catch (\Exception $e) {
    echo "Error without cast: " . $e->getMessage() . "\n";
}
