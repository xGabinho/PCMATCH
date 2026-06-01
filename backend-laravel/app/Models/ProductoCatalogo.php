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
}
