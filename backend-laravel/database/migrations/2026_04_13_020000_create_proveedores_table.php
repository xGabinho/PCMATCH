<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear tabla proveedores (no existía en el SQL legacy)
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('correo', 150)->unique();
            $table->string('password', 255);
            $table->boolean('activo')->default(1);
            $table->timestamp('created_at')->useCurrent();
        });

        // Agregar columna proveedor_id a bodegas (para la relación)
        if (!Schema::hasColumn('bodegas', 'proveedor_id')) {
            Schema::table('bodegas', function (Blueprint $table) {
                $table->unsignedBigInteger('proveedor_id')->nullable()->after('activa');
            });
        }
    }

    public function down(): void
    {
        Schema::table('bodegas', function (Blueprint $table) {
            if (Schema::hasColumn('bodegas', 'proveedor_id')) {
                $table->dropColumn('proveedor_id');
            }
        });
        Schema::dropIfExists('proveedores');
    }
};
