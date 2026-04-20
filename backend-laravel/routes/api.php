<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;

use App\Http\Controllers\Api\BodegaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ComponenteController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\HistorialController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// RUTAS PÚBLICAS (Sin token)
Route::get('/componentes/publico', [ComponenteController::class, 'indexPublic']);
Route::get('/catalogo', [CatalogoController::class, 'index']);

// Endpoint opcional para probar si Sanctum está funcionando
Route::middleware('auth:sanctum')->get('/auth/me', function (Request $request) {
    return $request->user();
});

// RUTAS PROTEGIDAS
Route::middleware('auth:sanctum')->group(function () {
    // RUTAS DE USUARIOS
    Route::get('/usuarios', [UsuarioController::class, 'index']); // Listar (Admin)
    Route::post('/usuarios', [UsuarioController::class, 'store']); // Crear (Admin)
    Route::put('/usuarios', [UsuarioController::class, 'update']); // Editar (Admin) 
    Route::delete('/usuarios', [UsuarioController::class, 'destroy']); // Eliminar (Admin)
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']); // Soporte para Laravel normal

    // RUTAS DE BODEGAS
    Route::get('/bodegas', [BodegaController::class, 'index']); // Listar
    Route::post('/bodegas', [BodegaController::class, 'store']); // Crear
    Route::put('/bodegas', [BodegaController::class, 'update']); // Editar
    Route::delete('/bodegas', [BodegaController::class, 'destroy']); // Eliminar
    Route::delete('/bodegas/{id}', [BodegaController::class, 'destroy']); // Soporte para url params

    // RUTAS DE PROVEEDORES
    Route::get('/proveedores', [ProveedorController::class, 'index']); // Listar
    Route::post('/proveedores', [ProveedorController::class, 'store']); // Crear
    Route::put('/proveedores', [ProveedorController::class, 'update']); // Editar
    Route::delete('/proveedores', [ProveedorController::class, 'destroy']); // Eliminar
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']); // Soporte params

    // RUTAS DE COMPONENTES (Admin/Proveedor/Bodega)
    Route::get('/componentes/admin', [ComponenteController::class, 'indexAdmin']); // Ver Componentes (admin/superadmin)
    Route::get('/componentes', [ComponenteController::class, 'indexBodega']);      // Ver los propios (bodega)
    Route::post('/componentes/admin', [ComponenteController::class, 'store']);     // Crear (admin/superadmin)
    Route::post('/componentes', [ComponenteController::class, 'store']);           // Crear (bodega/proveedor)
    Route::put('/componentes', [ComponenteController::class, 'update']);           // Editar
    Route::delete('/componentes', [ComponenteController::class, 'destroy']);       // Eliminar
    Route::delete('/componentes/{id}', [ComponenteController::class, 'destroy']); // Eliminar por ID

    // RUTAS DE COTIZACIONES
    Route::get('/cotizaciones', [CotizacionController::class, 'index']); // Listar
    Route::post('/cotizaciones', [CotizacionController::class, 'store']); // Crear (Solo cliente)
    Route::delete('/cotizaciones', [CotizacionController::class, 'destroy']); // Eliminar
    Route::delete('/cotizaciones/{id}', [CotizacionController::class, 'destroy']); // Soporte params

    // RUTAS DE HISTORIAL (Admin / Superadmin)
    Route::get('/historial', [HistorialController::class, 'index']);
});
