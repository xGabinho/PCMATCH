<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Storage::disk('supabase')->put('test.txt', 'Hola mundo desde PCMATCH!');
    echo "EXITO: El archivo se subio a Supabase Storage correctamente!\n";
} catch (\Exception $e) {
    echo "ERROR AL SUBIR A SUPABASE:\n";
    echo $e->getMessage() . "\n";
}
