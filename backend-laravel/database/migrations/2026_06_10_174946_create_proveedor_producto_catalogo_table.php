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
        if (!Schema::hasTable('proveedor_producto_catalogo')) {
            Schema::create('proveedor_producto_catalogo', function (Blueprint $table) {
                $table->id();
                $table->integer('proveedor_id');
                $table->integer('producto_catalogo_id');
                $table->timestamps();

                $table->foreign('proveedor_id')->references('id')->on('proveedores')->onDelete('cascade');
                $table->foreign('producto_catalogo_id')->references('id')->on('productos_catalogo')->onDelete('cascade');

                $table->unique(['proveedor_id', 'producto_catalogo_id'], 'prov_prod_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedor_producto_catalogo');
    }
};
