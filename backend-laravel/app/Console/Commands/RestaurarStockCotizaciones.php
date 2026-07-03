<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestaurarStockCotizaciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restaurar-stock-cotizaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura el stock a las bodegas de las cotizaciones con más de 7 días de antigüedad.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limite = \Carbon\Carbon::now()->subDays(7);

        // Buscar cotizaciones que tengan más de 7 días y su stock no se haya restaurado
        $cotizaciones = \Illuminate\Support\Facades\DB::table('cotizaciones')
            ->where('created_at', '<', $limite)
            ->where('stock_restaurado', 'false')
            ->get();

        $count = 0;

        foreach ($cotizaciones as $cotizacion) {
            $items = \Illuminate\Support\Facades\DB::table('cotizacion_items')
                ->where('cotizacion_id', $cotizacion->id)
                ->get();

            foreach ($items as $item) {
                // Devolver stock al componente
                \Illuminate\Support\Facades\DB::table('componentes')
                    ->where('id', $item->componente_id)
                    ->increment('stock', $item->cantidad);
            }

            // Marcar cotización como restaurada
            \Illuminate\Support\Facades\DB::table('cotizaciones')
                ->where('id', $cotizacion->id)
                ->update(['stock_restaurado' => 'true']);

            $count++;
        }

        $this->info("Stock restaurado para {$count} cotizaciones expiradas.");
    }
}
