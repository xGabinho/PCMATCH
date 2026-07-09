<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $password = password_hash('12345678', PASSWORD_BCRYPT);
        $now = Carbon::now();

        // ═══════════════════════════════════════════════════════
        // 0. LIMPIAR DATOS DEMO PREVIOS (si existen)
        // ═══════════════════════════════════════════════════════
        $demoCompIds = DB::table('componentes')->where('sku', 'like', 'DEMO-%')->pluck('id')->toArray();
        if (!empty($demoCompIds)) {
            DB::table('cotizacion_items')->whereIn('componente_id', $demoCompIds)->delete();
            DB::table('componentes')->whereIn('id', $demoCompIds)->delete();
        }
        $demoCotIds = DB::table('cotizaciones')->where('codigo', 'like', 'COT-%')->pluck('id')->toArray();
        if (!empty($demoCotIds)) {
            DB::table('cotizacion_items')->whereIn('cotizacion_id', $demoCotIds)->delete();
            DB::table('cotizaciones')->whereIn('id', $demoCotIds)->delete();
        }
        $demoProvEmails = ['ventas@techdistributor.com','contacto@compuglobal.co','soporte@microelectro.com.co','info@digitalparts.co'];
        $demoBodEmails = ['bodega.norte@techdistributor.com','bodega.medellin@techdistributor.com','almacen.cali@compuglobal.co','logistica.bquilla@compuglobal.co','deposito@microelectro.com.co','hub.pereira@digitalparts.co'];
        DB::table('bodegas')->whereIn('correo', $demoBodEmails)->delete();
        DB::table('proveedores')->whereIn('correo', $demoProvEmails)->delete();
        $this->command->info("🧹 Datos demo previos limpiados");

        // ═══════════════════════════════════════════════════════
        // 1. PROVEEDORES (4 proveedores profesionales)
        //    Columna activo = boolean en PostgreSQL
        // ═══════════════════════════════════════════════════════
        $proveedoresData = [
            ['TechDistributor S.A.S.', 'ventas@techdistributor.com', '900123456-1', 'TechDistributor Soluciones Tecnológicas S.A.S.', 6],
            ['CompuGlobal Ltda.', 'contacto@compuglobal.co', '900654321-7', 'CompuGlobal Distribuciones Limitada', 5],
            ['MicroElectro Colombia S.A.', 'soporte@microelectro.com.co', '800987654-3', 'MicroElectro Colombia Sociedad Anónima', 4],
            ['Digital Parts Corp.', 'info@digitalparts.co', '901234567-9', 'Digital Parts Corporation S.A.S.', 3],
        ];

        $proveedorIds = [];
        foreach ($proveedoresData as [$nombre, $correo, $idLegal, $razon, $mesesAtras]) {
            $row = DB::selectOne(
                "INSERT INTO proveedores (nombre, correo, password, activo, identificacion_legal, razon_social, estado_aprobacion, created_at)
                 VALUES (?, ?, ?, true, ?, ?, 'aprobado', ?) RETURNING id",
                [$nombre, $correo, $password, $idLegal, $razon, $now->copy()->subMonths($mesesAtras)]
            );
            $proveedorIds[] = $row->id;
        }
        $this->command->info("✅ Creados " . count($proveedorIds) . " proveedores");

        // ═══════════════════════════════════════════════════════
        // 2. BODEGAS (6 bodegas profesionales)
        //    Columna activa = boolean en PostgreSQL
        // ═══════════════════════════════════════════════════════
        $bodegasData = [
            ['Bodega Norte Bogotá',           '+573001234567', 'bodega.norte@techdistributor.com',   0, 5],
            ['Bodega Centro Medellín',        '+573009876543', 'bodega.medellin@techdistributor.com',0, 5],
            ['Almacén Principal Cali',        '+573005551234', 'almacen.cali@compuglobal.co',        1, 4],
            ['Centro Logístico Barranquilla', '+573007774321', 'logistica.bquilla@compuglobal.co',   1, 4],
            ['Depósito Sur Bucaramanga',      '+573003332211', 'deposito@microelectro.com.co',       2, 3],
            ['Hub Tecnológico Pereira',       '+573006667788', 'hub.pereira@digitalparts.co',        3, 2],
        ];

        $bodegaIds = [];
        foreach ($bodegasData as [$nombre, $tel, $correo, $provIdx, $mesesAtras]) {
            $row = DB::selectOne(
                "INSERT INTO bodegas (nombre, telefono, correo, password, activa, proveedor_id, created_at)
                 VALUES (?, ?, ?, ?, true, ?, ?) RETURNING id",
                [$nombre, $tel, $correo, $password, $proveedorIds[$provIdx], $now->copy()->subMonths($mesesAtras)]
            );
            $bodegaIds[] = $row->id;
        }
        $this->command->info("✅ Creadas " . count($bodegaIds) . " bodegas");

        // ═══════════════════════════════════════════════════════
        // 3. COMPONENTES
        //    Campos NOT NULL sin default: sku, producto_id, gama, precio, descuento_activo
        //    activo y descuento_activo son boolean en PostgreSQL
        // ═══════════════════════════════════════════════════════

        // Obtener productos del catálogo existente
        $catalogo = DB::table('productos_catalogo')->select('id', 'nombre', 'categoria')->get();
        if ($catalogo->isEmpty()) {
            $this->command->error("❌ No hay productos en el catálogo. Agrega productos primero.");
            return;
        }

        $gamas = ['baja', 'media', 'alta'];
        $enfoques = ['gaming', 'estudio', 'oficina'];

        // Mapeo de productos a usar por categoría
        $productosPorCategoria = $catalogo->groupBy('categoria');

        // Tomar hasta 13 productos variados del catálogo
        $productosSeleccionados = [];
        foreach (['CPU', 'GPU', 'RAM', 'Storage', 'Motherboard', 'PSU', 'Cooler', 'Case'] as $cat) {
            if (isset($productosPorCategoria[$cat])) {
                foreach ($productosPorCategoria[$cat]->take(2) as $p) {
                    $productosSeleccionados[] = $p;
                }
            }
        }
        // Si no hay suficientes, tomar lo que haya
        if (count($productosSeleccionados) < 5) {
            $productosSeleccionados = $catalogo->take(13)->values()->all();
        }

        $componenteIds = [];
        $counter = 1;

        // Precios base por categoría
        $preciosPorCategoria = [
            'CPU' => [350000, 850000], 'GPU' => [450000, 2500000], 'RAM' => [120000, 350000],
            'Storage' => [150000, 500000], 'Motherboard' => [250000, 700000], 'PSU' => [180000, 450000],
            'Cooler' => [80000, 250000], 'Case' => [150000, 400000],
        ];

        // Distribución: [bodega_idx, proveedor_idx, [producto_indices_del_array]]
        $distribucion = [
            [0, 0, [0,1,2,3,4,5,6]],      // Bodega Norte Bogotá - TechDistributor
            [1, 0, [0,2,4,6,7,8,9]],       // Bodega Centro Medellín - TechDistributor
            [2, 1, [1,3,5,7,9,10,11]],     // Almacén Cali - CompuGlobal
            [3, 1, [0,2,4,8,10,11,12]],    // Centro Barranquilla - CompuGlobal
            [4, 2, [1,3,5,6,9,12]],        // Depósito Bucaramanga - MicroElectro
            [5, 3, [0,1,4,7,10,11,12]],    // Hub Pereira - Digital Parts
        ];

        $sql = "INSERT INTO componentes (sku, bodega_id, producto_id, especificacion, gama, enfoque_uso, precio, stock, activo, proveedor_id, descuento_porcentaje, descuento_activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, true, ?, ?, false, ?) RETURNING id";

        foreach ($distribucion as [$bIdx, $pIdx, $prodIndices]) {
            foreach ($prodIndices as $pidx) {
                if ($pidx >= count($productosSeleccionados)) continue;

                $producto = $productosSeleccionados[$pidx];
                $sku = 'DEMO-' . str_pad($counter, 4, '0', STR_PAD_LEFT);

                // Precio realista según categoría
                $rango = $preciosPorCategoria[$producto->categoria] ?? [100000, 500000];
                $precio = rand($rango[0], $rango[1]);

                $gama = $gamas[array_rand($gamas)];
                $enfoque = $enfoques[array_rand($enfoques)];
                $stock = rand(5, 80);
                $descPct = rand(0, 3) === 0 ? rand(5, 15) : 0; // 25% chance de descuento

                $row = DB::selectOne($sql, [
                    $sku,
                    $bodegaIds[$bIdx],
                    $producto->id,
                    $producto->nombre . ' - Lote ' . $counter,
                    $gama,
                    $enfoque,
                    $precio,
                    $stock,
                    $proveedorIds[$pIdx],
                    $descPct,
                    $now->copy()->subDays(rand(10, 150)),
                ]);
                $componenteIds[] = $row->id;
                $counter++;
            }
        }
        $this->command->info("✅ Creados " . count($componenteIds) . " componentes");

        // ═══════════════════════════════════════════════════════
        // 4. COTIZACIONES y COTIZACION_ITEMS
        //    stock_restaurado = boolean en PostgreSQL
        // ═══════════════════════════════════════════════════════
        $clienteIds = DB::table('usuarios')->where('rol', 'cliente')->pluck('id')->toArray();
        if (empty($clienteIds)) {
            // Si no hay clientes, usar cualquier usuario
            $clienteIds = DB::table('usuarios')->pluck('id')->toArray();
        }
        if (empty($clienteIds)) {
            $this->command->error("❌ No hay usuarios en la BD.");
            return;
        }

        $cotizacionCount = 0;
        $itemCount = 0;

        for ($i = 0; $i < 30; $i++) {
            $diasAtras = rand(1, 120); // últimos 4 meses
            $fechaCot = $now->copy()->subDays($diasAtras);
            $usuarioId = $clienteIds[array_rand($clienteIds)];

            // Seleccionar 2-5 componentes aleatorios
            $numItems = rand(2, 5);
            $shuffled = $componenteIds;
            shuffle($shuffled);
            $elegidos = array_slice($shuffled, 0, min($numItems, count($shuffled)));

            $total = 0;
            $items = [];

            foreach ($elegidos as $compId) {
                $comp = DB::table('componentes')->where('id', $compId)->first();
                if (!$comp) continue;

                $cantidad = rand(1, 4);
                $precioUnit = (float) $comp->precio;
                $total += $precioUnit * $cantidad;

                $items[] = [
                    'componente_id' => $compId,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnit,
                ];
            }

            if (empty($items)) continue;

            $codigo = 'COT-' . strtoupper(substr(md5(uniqid($i, true)), 0, 8));

            $cotRow = DB::selectOne(
                "INSERT INTO cotizaciones (usuario_id, perfil, total, codigo, stock_restaurado, created_at)
                 VALUES (?, ?, ?, ?, false, ?) RETURNING id",
                [$usuarioId, 'gaming', $total, $codigo, $fechaCot]
            );

            $cotizacionCount++;

            foreach ($items as $item) {
                DB::table('cotizacion_items')->insert([
                    'cotizacion_id' => $cotRow->id,
                    'componente_id' => $item['componente_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                ]);
                $itemCount++;
            }
        }

        $this->command->info("✅ Creadas $cotizacionCount cotizaciones con $itemCount items");

        // ═══════════════════════════════════════════════════════
        // RESUMEN FINAL
        // ═══════════════════════════════════════════════════════
        $this->command->info("");
        $this->command->info("🎉 ═══ DATOS DEMO INSERTADOS EXITOSAMENTE ═══");
        $this->command->info("   Proveedores: " . count($proveedorIds));
        $this->command->info("   Bodegas: " . count($bodegaIds));
        $this->command->info("   Componentes: " . count($componenteIds));
        $this->command->info("   Cotizaciones: $cotizacionCount");
        $this->command->info("   Items de cotización: $itemCount");
        $this->command->info("");
        $this->command->info("   📧 Credenciales proveedores/bodegas: contraseña = 12345678");
        $this->command->info("   📧 Login superadmin: superadmin@pcmatch.com / password");
    }
}
