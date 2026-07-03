<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Reestructuración del catálogo:
     * 1. Mover specs técnicas de componentes → productos_catalogo
     * 2. Agregar precio_mayorista a tabla pivote proveedor_producto_catalogo
     * 3. Agregar descuento y proveedor_id a componentes
     */
    public function up(): void
    {
        // ═══════════════════════════════════════════════════════
        // PASO 1: Agregar campos técnicos a productos_catalogo
        // ═══════════════════════════════════════════════════════
        Schema::table('productos_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('productos_catalogo', 'especificacion')) {
                $table->string('especificacion', 1000)->nullable()->after('categoria');
            }
            if (!Schema::hasColumn('productos_catalogo', 'imagen_url')) {
                $table->text('imagen_url')->nullable()->after('especificacion');
            }
            if (!Schema::hasColumn('productos_catalogo', 'nucleos')) {
                $table->integer('nucleos')->nullable()->after('imagen_url');
            }
            if (!Schema::hasColumn('productos_catalogo', 'hilos')) {
                $table->integer('hilos')->nullable()->after('nucleos');
            }
            if (!Schema::hasColumn('productos_catalogo', 'frecuencia_hz')) {
                $table->decimal('frecuencia_hz', 8, 2)->nullable()->after('hilos');
            }
            if (!Schema::hasColumn('productos_catalogo', 'enfoque_uso')) {
                $table->string('enfoque_uso', 20)->nullable()->after('frecuencia_hz');
            }
            if (!Schema::hasColumn('productos_catalogo', 'gama')) {
                $table->string('gama', 10)->nullable()->after('enfoque_uso');
            }
        });

        // ═══════════════════════════════════════════════════════
        // PASO 2: Agregar precio_mayorista a tabla pivote
        // ═══════════════════════════════════════════════════════
        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'precio_mayorista')) {
                $table->decimal('precio_mayorista', 15, 2)->nullable()->after('producto_catalogo_id');
            }
            if (!Schema::hasColumn('proveedor_producto_catalogo', 'descripcion_comercial')) {
                $table->text('descripcion_comercial')->nullable()->after('precio_mayorista');
            }
        });

        // ═══════════════════════════════════════════════════════
        // PASO 3: Agregar descuento y proveedor_id a componentes
        // ═══════════════════════════════════════════════════════
        Schema::table('componentes', function (Blueprint $table) {
            if (!Schema::hasColumn('componentes', 'proveedor_id')) {
                $table->integer('proveedor_id')->nullable()->after('bodega_id');
            }
            if (!Schema::hasColumn('componentes', 'descuento_porcentaje')) {
                $table->decimal('descuento_porcentaje', 5, 2)->nullable()->default(0)->after('precio');
            }
            if (!Schema::hasColumn('componentes', 'descuento_activo')) {
                $table->boolean('descuento_activo')->default(false)->after('descuento_porcentaje');
            }
        });

        // ═══════════════════════════════════════════════════════
        // PASO 4: Migrar datos existentes de componentes → productos_catalogo
        // Para cada componente que tenga datos técnicos, copiarlos
        // al producto_catalogo si éste aún no los tiene.
        // ═══════════════════════════════════════════════════════
        $componentes = DB::table('componentes')
            ->select('producto_id', 'especificacion', 'imagen_url', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama')
            ->whereNotNull('producto_id')
            ->whereNotNull('especificacion')
            ->groupBy('producto_id', 'especificacion', 'imagen_url', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama')
            ->get();

        foreach ($componentes as $comp) {
            $producto = DB::table('productos_catalogo')->where('id', $comp->producto_id)->first();
            if ($producto && empty($producto->especificacion)) {
                $updateData = [];
                if ($comp->especificacion) $updateData['especificacion'] = $comp->especificacion;
                if ($comp->imagen_url) $updateData['imagen_url'] = $comp->imagen_url;
                if ($comp->nucleos) $updateData['nucleos'] = $comp->nucleos;
                if ($comp->hilos) $updateData['hilos'] = $comp->hilos;
                if ($comp->frecuencia_hz) $updateData['frecuencia_hz'] = $comp->frecuencia_hz;
                if ($comp->enfoque_uso) $updateData['enfoque_uso'] = $comp->enfoque_uso;
                if ($comp->gama) $updateData['gama'] = $comp->gama;

                if (!empty($updateData)) {
                    DB::table('productos_catalogo')
                        ->where('id', $comp->producto_id)
                        ->update($updateData);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('componentes', function (Blueprint $table) {
            if (Schema::hasColumn('componentes', 'proveedor_id')) $table->dropColumn('proveedor_id');
            if (Schema::hasColumn('componentes', 'descuento_porcentaje')) $table->dropColumn('descuento_porcentaje');
            if (Schema::hasColumn('componentes', 'descuento_activo')) $table->dropColumn('descuento_activo');
        });

        Schema::table('proveedor_producto_catalogo', function (Blueprint $table) {
            if (Schema::hasColumn('proveedor_producto_catalogo', 'precio_mayorista')) $table->dropColumn('precio_mayorista');
            if (Schema::hasColumn('proveedor_producto_catalogo', 'descripcion_comercial')) $table->dropColumn('descripcion_comercial');
        });

        Schema::table('productos_catalogo', function (Blueprint $table) {
            $cols = ['especificacion', 'imagen_url', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('productos_catalogo', $col)) $table->dropColumn($col);
            }
        });
    }
};
