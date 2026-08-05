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
            if (Schema::hasColumn('componentes', 'producto_id')) {
                $table->index('producto_id', 'idx_comp_producto_id');
            }
            if (Schema::hasColumn('componentes', 'gama')) {
                $table->index('gama', 'idx_comp_gama');
            }
            if (Schema::hasColumn('componentes', 'stock')) {
                $table->index('stock', 'idx_comp_stock');
            }
            if (Schema::hasColumn('componentes', 'deleted_at')) {
                $table->index('deleted_at', 'idx_comp_deleted_at');
            }
        });

        if (Schema::hasTable('productos_catalogo')) {
            Schema::table('productos_catalogo', function (Blueprint $table) {
                if (Schema::hasColumn('productos_catalogo', 'categoria')) {
                    $table->index('categoria', 'idx_prod_cat_categoria');
                }
                if (Schema::hasColumn('productos_catalogo', 'gama')) {
                    $table->index('gama', 'idx_prod_cat_gama');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropIndex('idx_comp_producto_id');
            $table->dropIndex('idx_comp_gama');
            $table->dropIndex('idx_comp_stock');
            $table->dropIndex('idx_comp_deleted_at');
        });

        if (Schema::hasTable('productos_catalogo')) {
            Schema::table('productos_catalogo', function (Blueprint $table) {
                $table->dropIndex('idx_prod_cat_categoria');
                $table->dropIndex('idx_prod_cat_gama');
            });
        }
    }
};
