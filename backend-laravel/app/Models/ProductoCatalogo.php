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
        'especificacion',
        'imagen_url',
        'nucleos',
        'hilos',
        'frecuencia_hz',
        'enfoque_uso',
        'gama',
    ];

    protected $casts = [
        'nucleos' => 'integer',
        'hilos' => 'integer',
        'frecuencia_hz' => 'decimal:2',
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
     * Incluye precio_mayorista y descripcion_comercial del pivot.
     */
    public function proveedores()
    {
        return $this->belongsToMany(Proveedor::class, 'proveedor_producto_catalogo', 'producto_catalogo_id', 'proveedor_id')
            ->withPivot('precio_mayorista', 'descripcion_comercial')
            ->withTimestamps();
    }
}
