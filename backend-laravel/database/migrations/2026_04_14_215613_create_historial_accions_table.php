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
        Schema::create('historial_acciones', function (Blueprint $table) {
            $table->id();
            $table->integer('usuario_id')->nullable();
            $table->string('usuario_nombre')->nullable();
            $table->string('rol_usuario')->nullable();
            $table->string('accion');
            $table->string('modulo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_acciones');
    }
};
