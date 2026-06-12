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
        try {
            Schema::table('componentes', function (Blueprint $table) {
                $table->dropForeign('componentes_ibfk_1');
            });
        } catch (\Exception $e) {
            // Foreign key may not exist or have a different name
        }

        try {
            Schema::table('componentes', function (Blueprint $table) {
                $table->integer('bodega_id')->nullable()->change();
                $table->foreign('bodega_id', 'componentes_ibfk_1')->references('id')->on('bodegas')->onDelete('restrict');
            });
        } catch (\Exception $e) {
            // Column may already be nullable or FK already exists
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropForeign('componentes_ibfk_1');
            $table->integer('bodega_id')->nullable(false)->change();
            $table->foreign('bodega_id', 'componentes_ibfk_1')->references('id')->on('bodegas')->onDelete('restrict');
        });
    }
};
