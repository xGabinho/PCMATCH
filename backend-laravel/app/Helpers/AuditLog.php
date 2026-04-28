<?php

namespace App\Helpers;

use App\Models\HistorialAccion;
use Illuminate\Support\Facades\DB;

class AuditLog
{
    /**
     * Labels amigables para los campos de la base de datos
     */
    private static $fieldLabels = [
        // Usuarios
        'nombre'               => 'Nombre',
        'apellido'             => 'Apellido',
        'correo'               => 'Correo',
        'telefono'             => 'Teléfono',
        'rol'                  => 'Rol',
        'activo'               => 'Estado',
        'activa'               => 'Estado',
        'password'             => 'Contraseña',
        // Proveedores
        'razon_social'         => 'Razón social',
        'identificacion_legal' => 'Identificación legal',
        'estado_aprobacion'    => 'Aprobación',
        'documento_soporte'    => 'Documento',
        // Bodegas
        'proveedor_id'         => 'Proveedor',
        // Componentes
        'especificacion'       => 'Especificación',
        'gama'                 => 'Gama',
        'precio'               => 'Precio',
        'stock'                => 'Stock',
        'bodega_id'            => 'Bodega',
        'producto_id'          => 'Producto',
    ];

    /**
     * Formatea un valor para mostrarlo de forma amigable
     */
    private static function formatValue($campo, $valor)
    {
        // Campos booleanos de estado
        if (in_array($campo, ['activo', 'activa'])) {
            return $valor == 1 ? 'Activo' : 'Inactivo';
        }

        // Roles
        if ($campo === 'rol') {
            $roles = [
                'superadmin' => 'Super Admin',
                'admin'      => 'Administrador',
                'cliente'    => 'Cliente',
                'proveedor'  => 'Proveedor',
                'bodega'     => 'Bodega',
            ];
            return $roles[$valor] ?? $valor;
        }

        // Estado de aprobación
        if ($campo === 'estado_aprobacion') {
            $estados = [
                'pendiente' => 'Pendiente',
                'aprobado'  => 'Aprobado',
                'rechazado' => 'Rechazado',
            ];
            return $estados[$valor] ?? $valor;
        }

        // Resolver proveedor_id a nombre
        if ($campo === 'proveedor_id') {
            if (empty($valor)) return 'Ninguno';
            $nombre = DB::table('proveedores')->where('id', $valor)->value('nombre');
            return $nombre ?? 'Desconocido';
        }

        // Resolver bodega_id a nombre
        if ($campo === 'bodega_id') {
            if (empty($valor)) return 'Ninguna';
            $nombre = DB::table('bodegas')->where('id', $valor)->value('nombre');
            return $nombre ?? 'Desconocida';
        }

        // Precio: formatear con $
        if ($campo === 'precio') {
            return '$' . number_format((float) $valor, 0, ',', '.');
        }

        // Gama
        if ($campo === 'gama') {
            return ucfirst($valor);
        }

        // Contraseña: nunca mostrar
        if ($campo === 'password') {
            return '(actualizada)';
        }

        return $valor;
    }

    /**
     * Formatea los cambios detectados en un modelo a texto legible
     *
     * @param array $dirty Array de campos modificados (getdirty())
     * @param \Illuminate\Database\Eloquent\Model $model El modelo con getOriginal()
     * @return string Texto formateado de cambios
     */
    public static function formatChanges($dirty, $model)
    {
        $cambios = [];

        foreach ($dirty as $campo => $nuevo) {
            if ($campo === 'updated_at' || $campo === 'created_at') continue;

            $label = self::$fieldLabels[$campo] ?? $campo;
            $viejo = $model->getOriginal($campo);

            // Para contraseña, no mostrar valores
            if ($campo === 'password') {
                $cambios[] = "Contraseña actualizada";
                continue;
            }

            $viejoFormatted = self::formatValue($campo, $viejo);
            $nuevoFormatted = self::formatValue($campo, $nuevo);

            // Si ambos valores formateados son iguales, saltar
            if ($viejoFormatted === $nuevoFormatted) continue;

            $cambios[] = "{$label}: {$viejoFormatted} → {$nuevoFormatted}";
        }

        return empty($cambios) ? 'Sin cambios' : implode(' · ', $cambios);
    }

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
