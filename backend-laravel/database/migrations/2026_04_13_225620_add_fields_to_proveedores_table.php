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
        Schema::table('proveedores', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedores', 'identificacion_legal')) {
                $table->string('identificacion_legal')->nullable()->unique();
            }
            if (!Schema::hasColumn('proveedores', 'razon_social')) {
                $table->string('razon_social')->nullable();
            }
            if (!Schema::hasColumn('proveedores', 'estado_aprobacion')) {
                $table->enum('estado_aprobacion', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            }
            if (!Schema::hasColumn('proveedores', 'documento_soporte')) {
                $table->string('documento_soporte')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn([
                'identificacion_legal',
                'razon_social',
                'estado_aprobacion',
                'documento_soporte'
            ]);
        });
    }
};
