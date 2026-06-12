<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * RF-15 RN02, RF-16 RN02, RF-18 RN01
     * Agrega columnas sku, activo, deleted_at e índice unique compuesto.
     */
    public function up(): void
    {
        // Add columns only if they don't exist
        if (!Schema::hasColumn('componentes', 'sku')) {
            Schema::table('componentes', function (Blueprint $table) {
                $table->string('sku', 50)->nullable()->unique()->after('id');
            });
        }
        if (!Schema::hasColumn('componentes', 'activo')) {
            Schema::table('componentes', function (Blueprint $table) {
                $table->tinyInteger('activo')->default(1)->after('stock');
            });
        }
        if (!Schema::hasColumn('componentes', 'deleted_at')) {
            Schema::table('componentes', function (Blueprint $table) {
                $table->softDeletes()->after('created_at');
            });
        }

        // Generar SKUs para registros existentes que no tengan uno
        $componentes = DB::table('componentes')
            ->whereNull('sku')
            ->join('productos_catalogo', 'componentes.producto_id', '=', 'productos_catalogo.id')
            ->select('componentes.id', 'componentes.bodega_id', 'componentes.producto_id', 'productos_catalogo.categoria')
            ->get();

        foreach ($componentes as $comp) {
            $sku = strtoupper($comp->categoria)
                 . '-' . str_pad($comp->producto_id, 3, '0', STR_PAD_LEFT)
                 . '-' . str_pad($comp->bodega_id ?? 0, 3, '0', STR_PAD_LEFT)
                 . '-' . strtoupper(substr(md5($comp->id . now()->timestamp), 0, 4));

            DB::table('componentes')->where('id', $comp->id)->update(['sku' => $sku]);
        }

        // Hacer sku NOT NULL después de llenar los existentes (solo si hay registros)
        if (Schema::hasColumn('componentes', 'sku')) {
            try {
                Schema::table('componentes', function (Blueprint $table) {
                    $table->string('sku', 50)->nullable(false)->unique()->change();
                });
            } catch (\Exception $e) {
                // Column may already be NOT NULL
            }
        }

        // Índice unique compuesto (PostgreSQL compatible)
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS uq_bodega_producto_spec ON componentes (bodega_id, producto_id, LEFT(especificacion::text, 191))');
        } catch (\Exception $e) {
            // Index may already exist or especificacion type doesn't support this
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar el índice compuesto primero
        Schema::table('componentes', function (Blueprint $table) {
            $table->dropIndex('uq_bodega_producto_spec');
        });

        Schema::table('componentes', function (Blueprint $table) {
            $table->dropColumn(['sku', 'activo', 'deleted_at']);
        });
    }
};
