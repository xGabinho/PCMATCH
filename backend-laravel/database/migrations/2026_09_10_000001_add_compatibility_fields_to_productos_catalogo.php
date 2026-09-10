<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar campos de compatibilidad hardware a productos_catalogo:
     * socket, tipo_ram, factor_forma, consumo_watts, wattage,
     * largo_mm, espacio_gpu_mm, marca
     */
    public function up(): void
    {
        Schema::table('productos_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('productos_catalogo', 'socket')) {
                $table->string('socket', 20)->nullable()->after('gama');
            }
            if (!Schema::hasColumn('productos_catalogo', 'tipo_ram')) {
                $table->string('tipo_ram', 10)->nullable()->after('socket');
            }
            if (!Schema::hasColumn('productos_catalogo', 'factor_forma')) {
                $table->string('factor_forma', 20)->nullable()->after('tipo_ram');
            }
            if (!Schema::hasColumn('productos_catalogo', 'consumo_watts')) {
                $table->integer('consumo_watts')->nullable()->after('factor_forma');
            }
            if (!Schema::hasColumn('productos_catalogo', 'wattage')) {
                $table->integer('wattage')->nullable()->after('consumo_watts');
            }
            if (!Schema::hasColumn('productos_catalogo', 'largo_mm')) {
                $table->integer('largo_mm')->nullable()->after('wattage');
            }
            if (!Schema::hasColumn('productos_catalogo', 'espacio_gpu_mm')) {
                $table->integer('espacio_gpu_mm')->nullable()->after('largo_mm');
            }
            if (!Schema::hasColumn('productos_catalogo', 'marca')) {
                $table->string('marca', 30)->nullable()->after('espacio_gpu_mm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos_catalogo', function (Blueprint $table) {
            $cols = ['socket', 'tipo_ram', 'factor_forma', 'consumo_watts', 'wattage', 'largo_mm', 'espacio_gpu_mm', 'marca'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('productos_catalogo', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
