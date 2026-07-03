<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Componente extends Model
{
    use SoftDeletes; // RF-18 RN01: Borrado lógico

    protected $table = 'componentes';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = null; // La tabla original no tiene updated_at

    protected $fillable = [
        'sku',
        'bodega_id',
        'proveedor_id',
        'producto_id',
        'especificacion',
        'nucleos',
        'hilos',
        'frecuencia_hz',
        'enfoque_uso',
        'gama',
        'precio',
        'descuento_porcentaje',
        'descuento_activo',
        'stock',
        'activo',
        'imagen_url',
    ];

    protected $casts = [
        'nucleos' => 'integer',
        'hilos' => 'integer',
        'frecuencia_hz' => 'decimal:2',
        'precio' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_activo' => 'boolean',
        'stock'  => 'integer',
        'activo' => 'boolean',
    ];

    // ──────────────────────────────────────────────
    // Relaciones
    // ──────────────────────────────────────────────

    /**
     * Bodega a la que pertenece este componente.
     */
    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    /**
     * Producto del catálogo asociado (define nombre, categoría y specs técnicas).
     */
    public function producto()
    {
        return $this->belongsTo(ProductoCatalogo::class, 'producto_id');
    }

    /**
     * Proveedor del que la bodega adquirió este componente.
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    /**
     * Precio final del componente aplicando descuento por porcentaje.
     */
    public function getPrecioFinalAttribute(): float
    {
        if ($this->descuento_activo && $this->descuento_porcentaje > 0) {
            return round((float)$this->precio * (1 - (float)$this->descuento_porcentaje / 100), 2);
        }
        return (float) $this->precio;
    }

    // Nota: cotizacion_items no tiene modelo Eloquent propio.
    // La verificación de relaciones activas se hace via DB::table()
    // en el método tieneRelacionesActivas().

    // ──────────────────────────────────────────────
    // Scopes (RF-16 RN02: Filtros Dinámicos)
    // ──────────────────────────────────────────────

    /**
     * Filtrar solo componentes activos.
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', 'true');
    }

    /**
     * Filtrar solo componentes inactivos.
     */
    public function scopeInactivo($query)
    {
        return $query->where('activo', 'false');
    }

    /**
     * Filtrar solo componentes con stock disponible.
     */
    public function scopeConStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Filtrar por categoría del producto (CPU, GPU, RAM, etc.)
     */
    public function scopePorCategoria($query, $categoria)
    {
        return $query->whereHas('producto', function ($q) use ($categoria) {
            $q->where('categoria', $categoria);
        });
    }

    /**
     * Filtrar por gama (alta, media, baja).
     */
    public function scopePorGama($query, $gama)
    {
        return $query->where('gama', $gama);
    }

    /**
     * Filtrar por rango de precio.
     */
    public function scopeRangoPrecio($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('precio', '>=', $min);
        }
        if ($max !== null) {
            $query->where('precio', '<=', $max);
        }
        return $query;
    }

    /**
     * Búsqueda por nombre del producto o especificación.
     */
    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('especificacion', 'LIKE', "%{$termino}%")
              ->orWhereHas('producto', function ($sub) use ($termino) {
                  $sub->where('nombre', 'LIKE', "%{$termino}%");
              });
        });
    }

    public function scopePorNucleos($query, $nucleos)
    {
        return $query->where('nucleos', $nucleos);
    }

    public function scopePorHilos($query, $hilos)
    {
        return $query->where('hilos', $hilos);
    }

    public function scopePorFrecuencia($query, $min = null, $max = null)
    {
        if ($min !== null) $query->where('frecuencia_hz', '>=', $min);
        if ($max !== null) $query->where('frecuencia_hz', '<=', $max);
        return $query;
    }

    public function scopePorEnfoque($query, $enfoque)
    {
        return $query->where('enfoque_uso', $enfoque);
    }

    // ──────────────────────────────────────────────
    // Métodos auxiliares
    // ──────────────────────────────────────────────

    /**
     * RF-15 RN02: Generar un SKU único para un componente.
     * Formato: {CATEGORIA}-{PRODUCTO_ID}-{BODEGA_ID}-{HEX}
     */
    public static function generarSku($productoId, $bodegaId): string
    {
        $producto = DB::table('productos_catalogo')->where('id', $productoId)->first();
        $categoria = $producto ? strtoupper($producto->categoria) : 'GEN';

        $hex = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));

        $sku = $categoria
             . '-' . str_pad($productoId, 3, '0', STR_PAD_LEFT)
             . '-' . str_pad($bodegaId, 3, '0', STR_PAD_LEFT)
             . '-' . $hex;

        // Garantizar unicidad
        while (self::withTrashed()->where('sku', $sku)->exists()) {
            $hex = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
            $sku = $categoria
                 . '-' . str_pad($productoId, 3, '0', STR_PAD_LEFT)
                 . '-' . str_pad($bodegaId, 3, '0', STR_PAD_LEFT)
                 . '-' . $hex;
        }

        return $sku;
    }

    /**
     * RF-18 RN02: Verificar si el componente tiene relaciones activas que impidan su eliminación.
     * Retorna true si tiene cotizacion_items activos vinculados.
     */
    public function tieneRelacionesActivas(): bool
    {
        $limite = \Carbon\Carbon::now()->subDays(7);
        
        return DB::table('cotizacion_items')
            ->join('cotizaciones', 'cotizacion_items.cotizacion_id', '=', 'cotizaciones.id')
            ->where('cotizacion_items.componente_id', $this->id)
            ->where('cotizaciones.created_at', '>=', $limite)
            ->where('cotizaciones.stock_restaurado', 'false')
            ->exists();
    }
}
