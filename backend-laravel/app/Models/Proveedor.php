<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Proveedor extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'proveedores';
    public $timestamps = false;

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Productos del catálogo global que este proveedor ofrece,
     * con su precio mayorista y descripción comercial.
     */
    public function productosCatalogo()
    {
        return $this->belongsToMany(ProductoCatalogo::class, 'proveedor_producto_catalogo', 'proveedor_id', 'producto_catalogo_id')
            ->withPivot('precio_mayorista', 'descripcion_comercial', 'stock', 'especificacion', 'gama', 'enfoque_uso', 'nucleos', 'hilos', 'frecuencia_hz')
            ->withTimestamps();
    }

    /**
     * Bodegas que pertenecen a este proveedor.
     */
    public function bodegas()
    {
        return $this->hasMany(Bodega::class, 'proveedor_id');
    }
}
