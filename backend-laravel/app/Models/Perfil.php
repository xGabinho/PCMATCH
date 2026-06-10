<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perfil extends Model
{
    protected $table = 'perfiles';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    public function permisos()
    {
        return $this->hasMany(PerfilPermiso::class, 'perfil_id');
    }

    /**
     * Verificar si el perfil tiene un permiso específico.
     */
    public function tienePermiso(string $permiso): bool
    {
        return $this->permisos()->where('permiso', $permiso)->exists();
    }

    /**
     * Obtener la lista de strings de permisos.
     */
    public function getPermisosListAttribute(): array
    {
        return $this->permisos->pluck('permiso')->toArray();
    }
}
