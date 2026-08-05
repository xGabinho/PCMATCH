<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InsertProductosBodegaSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $inserts = [
            // CPU (Procesadores)
            ['COMP-CPU-01', 30, 8, '6 Núcleos 12 Hilos 4.4GHz 18MB Cache', 'media', 'gaming', 620000, 25, 25],
            ['COMP-CPU-02', 32, 2, '6 Núcleos 12 Hilos 4.4GHz Vega Graphics', 'baja', 'oficina', 480000, 30, 26],
            ['COMP-CPU-03', 31, 5, '8 Núcleos 16 Hilos 5.4GHz 40MB Cache', 'alta', 'gaming', 1450000, 15, 25],

            // GPU (Tarjetas de Video)
            ['COMP-GPU-01', 30, 12, '4GB GDDR6 128-bit PCIe 3.0', 'baja', 'gaming', 680000, 20, 25],
            ['COMP-GPU-02', 31, 14, '12GB GDDR6 192-bit Ray Tracing DLSS', 'media', 'gaming', 1350000, 18, 25],
            ['COMP-GPU-03', 35, 16, '12GB GDDR6X 192-bit DLSS 3.0', 'alta', 'diseño', 2850000, 10, 28],

            // RAM (Memorias RAM)
            ['COMP-RAM-01', 33, 23, '8GB 3200MHz DDR4 CL16 1.35V', 'baja', 'estudio', 110000, 50, 26],
            ['COMP-RAM-02', 30, 25, '16GB (2x8GB) 3600MHz DDR4 CL18', 'media', 'gaming', 195000, 40, 25],
            ['COMP-RAM-03', 34, 28, '32GB (2x16GB) 6000MHz DDR5 CL36 RGB', 'alta', 'diseño', 540000, 15, 27],

            // Storage (Almacenamiento)
            ['COMP-STO-01', 32, 29, '480GB SSD SATA III 2.5 pulgadas 500MB/s', 'baja', 'oficina', 135000, 45, 26],
            ['COMP-STO-02', 30, 31, '1TB NVMe M.2 2280 PCIe 3.0 3500MB/s', 'media', 'gaming', 360000, 35, 25],
            ['COMP-STO-03', 31, 32, '1TB NVMe M.2 2280 PCIe 4.0 7300MB/s', 'alta', 'diseño', 490000, 20, 25],

            // Motherboard (Placas Madre)
            ['COMP-MB-01', 33, 35, 'Socket AM4 Micro-ATX DDR4 PCIe 3.0', 'baja', 'estudio', 320000, 25, 26],
            ['COMP-MB-02', 30, 39, 'Socket LGA1700 ATX DDR4 Wi-Fi 6', 'media', 'gaming', 720000, 15, 25],
            ['COMP-MB-03', 35, 40, 'Socket LGA1700 ATX DDR5 PCIe 5.0 USB 3.2', 'alta', 'diseño', 980000, 12, 28],

            // PSU (Fuentes de Poder)
            ['COMP-PSU-01', 34, 41, '500W 80 Plus Bronze No Modular', 'baja', 'oficina', 210000, 30, 27],
            ['COMP-PSU-02', 31, 42, '650W 80 Plus Bronze Semimodular', 'media', 'gaming', 340000, 22, 25],
            ['COMP-PSU-03', 30, 43, '750W 80 Plus Gold Full Modular', 'alta', 'gaming', 520000, 18, 25],

            // Cooler (Disipadores)
            ['COMP-CLR-01', 32, 47, 'Torre de Aire 120mm RGB 4 Heatpipes', 'baja', 'estudio', 140000, 35, 26],
            ['COMP-CLR-02', 30, 48, 'Torre de Aire Dual 120mm PWM', 'media', 'gaming', 165000, 28, 25],
            ['COMP-CLR-03', 31, 51, 'Refrigeración Líquida AIO 240mm RGB', 'alta', 'diseño', 590000, 14, 25],

            // Case (Gabinetes)
            ['COMP-CAS-01', 33, 53, 'Mid-Tower Vidrio Templado USB 3.0', 'baja', 'estudio', 290000, 20, 26],
            ['COMP-CAS-02', 30, 54, 'Mid-Tower Malla Frontal Vidrio Templado 3x Fans', 'media', 'gaming', 395000, 25, 25],
            ['COMP-CAS-03', 35, 57, 'Chasis Doble Cámara Vidrio Templado Panorámico', 'alta', 'gaming', 680000, 10, 28],
        ];

        $sql = "INSERT INTO componentes (sku, bodega_id, producto_id, especificacion, gama, enfoque_uso, precio, stock, activo, proveedor_id, descuento_porcentaje, descuento_activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, true, ?, 0, false, ?)
                ON CONFLICT (sku) DO UPDATE SET 
                    precio = EXCLUDED.precio, 
                    stock = EXCLUDED.stock, 
                    especificacion = EXCLUDED.especificacion,
                    bodega_id = EXCLUDED.bodega_id";

        foreach ($inserts as $item) {
            DB::statement($sql, [
                $item[0], // sku
                $item[1], // bodega_id
                $item[2], // producto_id
                $item[3], // especificacion
                $item[4], // gama
                $item[5], // enfoque_uso
                $item[6], // precio
                $item[7], // stock
                $item[8], // proveedor_id
                $now
            ]);
        }
    }
}
