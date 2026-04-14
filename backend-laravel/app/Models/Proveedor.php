<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Proveedor extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'proveedores';
    public $timestamps = false;
}
