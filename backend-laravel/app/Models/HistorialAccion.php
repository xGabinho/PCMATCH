<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialAccion extends Model
{
    protected $table = 'historial_acciones';

    protected $fillable = [
        'usuario_id',
        'usuario_nombre',
        'rol_usuario',
        'accion',
        'modulo',
    ];
}
