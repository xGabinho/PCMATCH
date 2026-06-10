<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoCatalogo extends Model
{
    protected $table = 'productos_catalogo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'categoria',
    ];

    /**
     * Relación: Un producto del catálogo puede tener muchos componentes.
     */
    public function componentes()
    {
        return $this->hasMany(Componente::class, 'producto_id');
    }

    /**
     * Relación: Un producto del catálogo puede ser ofrecido por muchos proveedores.
     */
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'proveedor_producto_catalogo', 'producto_catalogo_id', 'proveedor_id');
    }
}
