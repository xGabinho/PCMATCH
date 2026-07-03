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
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'stock')) {
                $table->integer('stock')->default(0)->after('precio_mayorista');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (Schema::hasColumn('proveedor_producto_catalogo', 'stock')) {
                $table->dropColumn('stock');
            }
        });
    }
};
