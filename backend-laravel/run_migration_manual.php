<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

Schema::table('componentes', function (Blueprint $table) {
    if (!Schema::hasColumn('componentes', 'nucleos')) {
        $table->integer('nucleos')->nullable()->after('especificacion');
        $table->integer('hilos')->nullable()->after('nucleos');
        $table->decimal('frecuencia_hz', 8, 2)->nullable()->comment('GHz')->after('hilos');
        $table->enum('enfoque_uso', ['estudio', 'oficina', 'gaming', 'diseño'])->nullable()->after('frecuencia_hz');
    }
});

echo "Columnas agregadas con éxito.\n";
