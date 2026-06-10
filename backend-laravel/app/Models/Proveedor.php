<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Proveedor extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'proveedores';
    public $timestamps = false;

    public function productosCatalogo()
    {
        return $this->belongsToMany(ProductoCatalogo::class, 'proveedor_producto_catalogo', 'proveedor_id', 'producto_catalogo_id');
    }
}
