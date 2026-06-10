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
        Schema::table('componentes', function (Blueprint $table) {
            $table->integer('nucleos')->nullable()->after('especificacion');
            $table->integer('hilos')->nullable()->after('nucleos');
            $table->decimal('frecuencia_hz', 8, 2)->nullable()->comment('GHz')->after('hilos');
            $table->enum('enfoque_uso', ['estudio', 'oficina', 'gaming', 'diseño'])->nullable()->after('frecuencia_hz');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropColumn(['nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso']);
        });
    }
};
