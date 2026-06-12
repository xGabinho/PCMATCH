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
        Schema::table('bodegas', function (Blueprint $table) {
            $table->index('proveedor_id');
        });

        Schema::table('componentes', function (Blueprint $table) {
            $table->index('bodega_id');
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->index('usuario_id');
        });

        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->index('cotizacion_id');
            $table->index('componente_id');
        });

        Schema::table('historial_acciones', function (Blueprint $table) {
            $table->index('usuario_id');
            $table->index('created_at');
            $table->index('modulo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bodegas', function (Blueprint $table) {
            $table->dropIndex(['proveedor_id']);
        });

        Schema::table('componentes', function (Blueprint $table) {
            $table->dropIndex(['bodega_id']);
        });

        Schema::table('cotizaciones', function (Blueprint $table) {
            $table->dropIndex(['usuario_id']);
        });

        Schema::table('cotizacion_items', function (Blueprint $table) {
            $table->dropIndex(['cotizacion_id']);
            $table->dropIndex(['componente_id']);
        });

        Schema::table('historial_acciones', function (Blueprint $table) {
            $table->dropIndex(['usuario_id']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['modulo']);
        });
    }
};
