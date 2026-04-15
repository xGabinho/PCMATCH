<?php

namespace App\Helpers;

use App\Models\HistorialAccion;

class AuditLog
{
    /**
     * Create an audit log entry for an action.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $accion Description of the action performed.
     * @param string $modulo The corresponding module (e.g. Usuarios, Componentes).
     */
    public static function log($request, $accion, $modulo, $explicitUser = null)
    {
        $user = $request->user() ?? $explicitUser;
        
        if ($user) {
            $clase = get_class($user);

            // Determinar el rol según el tipo de modelo autenticado
            if ($clase === \App\Models\Bodega::class) {
                $rol    = 'bodega';
                $nombre = $user->nombre;
            } elseif ($clase === \App\Models\Proveedor::class) {
                $rol    = 'proveedor';
                $nombre = $user->nombre;
            } else {
                // Usuario normal: puede tener nombre + apellido
                $rol    = $user->rol ?? 'usuario';
                $nombre = $user->nombre;
                if (isset($user->apellido) && !empty($user->apellido)) {
                    $nombre .= ' ' . $user->apellido;
                }
            }

            HistorialAccion::create([
                'usuario_id'     => $user->id,
                'usuario_nombre' => $nombre,
                'rol_usuario'    => $rol,
                'accion'         => $accion,
                'modulo'         => $modulo,
            ]);
        }
    }
}
