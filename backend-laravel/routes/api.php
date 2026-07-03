<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuarioController;
use App\Http\Controllers\Api\PasswordResetController;

use App\Http\Controllers\Api\BodegaController;
use App\Http\Controllers\Api\ProveedorController;
use App\Http\Controllers\Api\ComponenteController;
use App\Http\Controllers\Api\CatalogoController;
use App\Http\Controllers\Api\CotizacionController;
use App\Http\Controllers\Api\HistorialController;
use App\Http\Controllers\Api\RecomendacionController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'resetPassword']);

// RUTAS PÚBLICAS (Sin token)
Route::get('/componentes/publico', [ComponenteController::class, 'indexPublic']);
Route::get('/catalogo', [CatalogoController::class, 'index']);

// RUTAS DE RECOMENDACIONES (Públicas)
Route::get('/recomendaciones/mas-vendidos', [RecomendacionController::class, 'getMasVendidos']);
Route::post('/recomendaciones/pc-ideal', [RecomendacionController::class, 'buildPcIdeal']);

// Endpoint opcional para probar si Sanctum está funcionando
Route::middleware('auth:sanctum')->get('/auth/me', function (Request $request) {
    return $request->user();
});

// RUTAS PROTEGIDAS
Route::middleware('auth:sanctum')->group(function () {
    // PERFIL DEL USUARIO AUTENTICADO
    Route::get('/auth/profile', [AuthController::class, 'profile']);
    Route::put('/auth/profile', [AuthController::class, 'updateProfile']);

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
    Route::get('/proveedores/{id}/productos', [ProveedorController::class, 'productos']); // Obtener catálogo asignado
    Route::post('/proveedores/{id}/productos', [ProveedorController::class, 'syncProductos']); // Asignar catálogo
    Route::put('/proveedores/catalogo/item', [ProveedorController::class, 'updateCatalogoItem']); // Editar precio mayorista
    Route::delete('/proveedores/catalogo/item', [ProveedorController::class, 'removeCatalogoItem']); // Quitar producto del catálogo

    // RUTAS DE COMPONENTES (Admin/Proveedor/Bodega)
    Route::get('/componentes/admin', [ComponenteController::class, 'indexAdmin']); // Ver Componentes (admin/superadmin)
    Route::get('/componentes/maestros', [ComponenteController::class, 'maestros']); // Ver maestros (admin/bodega/proveedor)
    Route::get('/componentes', [ComponenteController::class, 'indexBodega']);      // Ver los propios (bodega)
    Route::post('/componentes/admin', [ComponenteController::class, 'store']);     // Crear (admin/superadmin)
    Route::post('/componentes', [ComponenteController::class, 'store']);           // Crear (bodega/proveedor)

    // RUTAS DE CATÁLOGO BASE
    Route::post('/productos-catalogo', [CatalogoController::class, 'store']); // Crear producto base (Admin)
    Route::get('/productos-catalogo', [CatalogoController::class, 'index']); // GET alternativo
    Route::put('/componentes', [ComponenteController::class, 'update']);           // Editar
    Route::patch('/componentes/stock', [ComponenteController::class, 'adjustStock']); // Ajustar stock +/-
    Route::delete('/componentes', [ComponenteController::class, 'destroy']);       // Eliminar
    Route::delete('/componentes/{id}', [ComponenteController::class, 'destroy']); // Eliminar por ID

    // RUTAS DE COTIZACIONES
    Route::get('/cotizaciones', [CotizacionController::class, 'index']); // Listar
    Route::post('/cotizaciones', [CotizacionController::class, 'store']); // Crear (Solo cliente)
    Route::delete('/cotizaciones', [CotizacionController::class, 'destroy']); // Eliminar
    Route::delete('/cotizaciones/{id}', [CotizacionController::class, 'destroy']); // Soporte params

    // RUTAS DE PERFILES Y PERMISOS (Admin / Superadmin)
    Route::get('/perfiles/permisos', [\App\Http\Controllers\Api\PerfilController::class, 'available']);
    Route::get('/perfiles', [\App\Http\Controllers\Api\PerfilController::class, 'index']);
    Route::post('/perfiles', [\App\Http\Controllers\Api\PerfilController::class, 'store']);
    Route::put('/perfiles', [\App\Http\Controllers\Api\PerfilController::class, 'update']);
    Route::delete('/perfiles', [\App\Http\Controllers\Api\PerfilController::class, 'destroy']);
    Route::delete('/perfiles/{id}', [\App\Http\Controllers\Api\PerfilController::class, 'destroy']);
    Route::put('/perfiles/asignar', [\App\Http\Controllers\Api\PerfilController::class, 'assign']);

    // RUTAS DE HISTORIAL (Admin / Superadmin)
    Route::get('/historial', [HistorialController::class, 'index']);
});
