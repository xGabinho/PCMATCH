<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Bodega extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'bodegas';
    public $timestamps = false;
}
