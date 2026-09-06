<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'especificacion')) {
                $table->string('especificacion', 1000)->nullable();
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'gama')) {
                $table->string('gama', 10)->nullable();
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'enfoque_uso')) {
                $table->string('enfoque_uso', 20)->nullable();
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'nucleos')) {
                $table->integer('nucleos')->nullable();
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'hilos')) {
                $table->integer('hilos')->nullable();
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'frecuencia_hz')) {
                $table->decimal('frecuencia_hz', 8, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            $cols = ['especificacion', 'gama', 'enfoque_uso', 'nucleos', 'hilos', 'frecuencia_hz'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('proveedor_producto_catalogo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
