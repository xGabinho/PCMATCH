<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'imagen_url')) {
                $table->string('imagen_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (Schema::hasColumn('proveedor_producto_catalogo', 'imagen_url')) {
                $table->dropColumn('imagen_url');
            }
        });
    }
};
