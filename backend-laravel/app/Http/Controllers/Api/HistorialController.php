<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HistorialAccion;

class HistorialController extends Controller
{
    
    /**
    
     * Obtiene una lista de registros o recursos.
    
     * Ejecuta la consulta a la base de datos (con posibles filtros/paginación) y retorna los datos en formato JSON.
    
     */
    
    public function index(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);

        // Solo permitir a 'admin' y 'superadmin' que sean instancias de 'Usuario'
        if ($clase !== \App\Models\Usuario::class || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol de administrador.'
            ], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('historial.ver')) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere el permiso: historial.ver'
            ], 403);
        }

        // Obtener historial ordenado del mas reciente al mas antiguo
        $historial = HistorialAccion::orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'historial' => $historial
        ]);
    }
}
