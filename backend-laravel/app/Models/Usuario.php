<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';
    public $timestamps = false; // El backend viejo PHP asume tablas sin created_at

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function perfil()
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function hasPermission(string $permiso): bool
    {
        if ($this->rol === 'superadmin') {
            return true;
        }
        if (!$this->perfil_id) {
            return $this->rol === 'admin';
        }
        if (!$this->perfil || !$this->perfil->activo) {
            return false;
        }
        return $this->perfil->tienePermiso($permiso);
    }
}
