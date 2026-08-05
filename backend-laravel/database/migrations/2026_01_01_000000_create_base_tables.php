<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('apellido', 100)->default('');
                $table->string('correo', 150)->unique();
                $table->string('telefono', 20)->default('');
                $table->string('password', 255);
                $table->string('rol', 20)->default('cliente');
                $table->unsignedBigInteger('perfil_id')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('productos_catalogo')) {
            Schema::create('productos_catalogo', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 255);
                $table->string('categoria', 50);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('bodegas')) {
            Schema::create('bodegas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 150);
                $table->string('correo', 150)->unique();
                $table->string('telefono', 20)->nullable();
                $table->string('password', 255);
                $table->boolean('activa')->default(true);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('componentes')) {
            Schema::create('componentes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('bodega_id')->nullable();
                $table->unsignedBigInteger('producto_id');
                $table->text('especificacion')->nullable();
                $table->integer('nucleos')->nullable();
                $table->integer('hilos')->nullable();
                $table->decimal('frecuencia_hz', 8, 2)->nullable();
                $table->string('enfoque_uso', 20)->nullable();
                $table->string('gama', 10)->nullable();
                $table->decimal('precio', 15, 2)->default(0);
                $table->integer('stock')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('cotizaciones')) {
            Schema::create('cotizaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->string('perfil', 20)->default('gaming');
                $table->decimal('total', 15, 2)->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('cotizacion_items')) {
            Schema::create('cotizacion_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cotizacion_id');
                $table->unsignedBigInteger('componente_id');
                $table->integer('cantidad')->default(1);
                $table->decimal('precio_unitario', 15, 2)->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
        Schema::dropIfExists('componentes');
        Schema::dropIfExists('bodegas');
        Schema::dropIfExists('productos_catalogo');
        Schema::dropIfExists('usuarios');
    }
};
