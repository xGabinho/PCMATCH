<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Bodega extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'bodegas';
    public $timestamps = false;

    protected $casts = [
        'activa' => 'boolean',
    ];

    protected $fillable = [
        'nombre',
        'telefono',
        'correo',
        'password',
        'activa',
        'proveedor_id',
    ];

    /**
     * Relación: Una bodega tiene muchos componentes.
     */
    public function componentes()
    {
        return $this->hasMany(Componente::class, 'bodega_id');
    }

    /**
     * Relación: Una bodega pertenece a un proveedor (opcional).
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
