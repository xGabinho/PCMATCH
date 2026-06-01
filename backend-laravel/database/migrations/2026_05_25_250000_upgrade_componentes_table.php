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
        Schema::table('componentes', function (Blueprint $table) {
            $table->string('sku', 50)->nullable()->unique()->after('id');
            $table->tinyInteger('activo')->default(1)->after('stock');
            $table->softDeletes()->after('created_at');
        });

        // Generar SKUs para registros existentes
        $componentes = DB::table('componentes')
            ->join('productos_catalogo', 'componentes.producto_id', '=', 'productos_catalogo.id')
            ->select('componentes.id', 'componentes.bodega_id', 'componentes.producto_id', 'productos_catalogo.categoria')
            ->get();

        foreach ($componentes as $comp) {
            $sku = strtoupper($comp->categoria)
                 . '-' . str_pad($comp->producto_id, 3, '0', STR_PAD_LEFT)
                 . '-' . str_pad($comp->bodega_id, 3, '0', STR_PAD_LEFT)
                 . '-' . strtoupper(substr(md5($comp->id . now()->timestamp), 0, 4));

            DB::table('componentes')->where('id', $comp->id)->update(['sku' => $sku]);
        }

        // Hacer sku NOT NULL después de llenar los existentes
        Schema::table('componentes', function (Blueprint $table) {
            $table->string('sku', 50)->nullable(false)->unique()->change();
        });

        // Índice unique compuesto: no duplicar misma especificación en misma bodega y producto
        // Usamos un raw statement porque especificacion es TEXT y necesita prefijo
        DB::statement('ALTER TABLE componentes ADD UNIQUE INDEX uq_bodega_producto_spec (bodega_id, producto_id, especificacion(191))');
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
