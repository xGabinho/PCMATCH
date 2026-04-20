<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HistorialAccion;

class HistorialController extends Controller
{
    /**
     * Display a listing of the audit logs.
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

        // Obtener historial ordenado del mas reciente al mas antiguo
        $historial = HistorialAccion::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'historial' => $historial
        ]);
    }
}
