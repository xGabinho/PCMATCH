<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerfilPermiso extends Model
{
    protected $table = 'perfil_permisos';
    public $timestamps = false;

    protected $fillable = ['perfil_id', 'permiso'];

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }
}
