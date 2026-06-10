<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Perfil;
use App\Models\PerfilPermiso;
use App\Models\Usuario;
use App\Helpers\AuditLog;

class PerfilController extends Controller
{
    /**
     * Lista de todos los permisos disponibles en el sistema, agrupados por módulo.
     */
    private const PERMISOS_DISPONIBLES = [
        'Usuarios' => [
            'usuarios.ver'      => 'Ver usuarios',
            'usuarios.crear'    => 'Crear usuarios',
            'usuarios.editar'   => 'Editar usuarios',
            'usuarios.eliminar' => 'Eliminar usuarios',
        ],
        'Bodegas' => [
            'bodegas.ver'      => 'Ver bodegas',
            'bodegas.crear'    => 'Crear bodegas',
            'bodegas.editar'   => 'Editar bodegas',
            'bodegas.eliminar' => 'Eliminar bodegas',
        ],
        'Componentes' => [
            'componentes.ver'      => 'Ver componentes',
            'componentes.crear'    => 'Crear componentes',
            'componentes.editar'   => 'Editar componentes',
            'componentes.eliminar' => 'Eliminar componentes',
        ],
        'Proveedores' => [
            'proveedores.ver'      => 'Ver proveedores',
            'proveedores.crear'    => 'Crear proveedores',
            'proveedores.editar'   => 'Editar proveedores',
            'proveedores.eliminar' => 'Eliminar proveedores',
        ],
        'Cotizaciones' => [
            'cotizaciones.ver'      => 'Ver cotizaciones',
            'cotizaciones.eliminar' => 'Eliminar cotizaciones',
        ],
        'Historial' => [
            'historial.ver' => 'Ver historial de acciones',
        ],
    ];

    /**
     * Verificar que el usuario es admin o superadmin.
     */
    private function checkAdmin(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->rol ?? '', ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol de administrador.'
            ], 403);
        }
        return null;
    }

    /**
     * GET /api/perfiles/permisos — Lista de permisos disponibles agrupados por módulo
     */
    public function available(Request $request)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        return response()->json([
            'permisos' => self::PERMISOS_DISPONIBLES
        ]);
    }

    /**
     * GET /api/perfiles — Listar todos los perfiles con sus permisos
     */
    public function index(Request $request)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        $perfiles = Perfil::with('permisos')->orderBy('created_at', 'desc')->get();

        $resultado = $perfiles->map(function ($perfil) {
            return [
                'id'          => $perfil->id,
                'nombre'      => $perfil->nombre,
                'descripcion' => $perfil->descripcion,
                'activo'      => $perfil->activo,
                'permisos'    => $perfil->permisos->pluck('permiso')->toArray(),
                'usuarios_count' => Usuario::where('perfil_id', $perfil->id)->count(),
                'created_at'  => $perfil->created_at,
            ];
        });

        return response()->json(['perfiles' => $resultado]);
    }

    /**
     * POST /api/perfiles — Crear un nuevo perfil con permisos
     */
    public function store(Request $request)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        $validator = Validator::make($request->all(), [
            'nombre'      => 'required|string|max:100|unique:perfiles,nombre',
            'descripcion' => 'nullable|string|max:255',
            'permisos'    => 'required|array|min:1',
            'permisos.*'  => 'string|max:100',
        ], [
            'nombre.required' => 'El nombre del perfil es requerido',
            'nombre.unique'   => 'Ya existe un perfil con ese nombre',
            'permisos.required' => 'Debes seleccionar al menos un permiso',
            'permisos.min'    => 'Debes seleccionar al menos un permiso',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        // Validar que los permisos enviados existen en el catálogo
        $permisosValidos = collect(self::PERMISOS_DISPONIBLES)->flatten()->keys()->toArray();
        // Flatten the nested array properly
        $allKeys = [];
        foreach (self::PERMISOS_DISPONIBLES as $modulo => $permisos) {
            foreach ($permisos as $key => $label) {
                $allKeys[] = $key;
            }
        }

        $permisosInvalidos = array_diff($request->input('permisos'), $allKeys);
        if (!empty($permisosInvalidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Permisos inválidos: ' . implode(', ', $permisosInvalidos)
            ], 422);
        }

        $perfil = Perfil::create([
            'nombre'      => $request->input('nombre'),
            'descripcion' => $request->input('descripcion', ''),
        ]);

        // Insertar permisos
        foreach ($request->input('permisos') as $permiso) {
            PerfilPermiso::create([
                'perfil_id' => $perfil->id,
                'permiso'   => $permiso,
            ]);
        }

        AuditLog::log($request, "Creó el perfil «{$perfil->nombre}» con " . count($request->input('permisos')) . " permisos", 'Perfiles');

        return response()->json([
            'success' => true,
            'message' => 'Perfil creado correctamente',
            'id'      => $perfil->id,
        ], 201);
    }

    /**
     * PUT /api/perfiles — Actualizar un perfil y sus permisos
     */
    public function update(Request $request)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID es requerido'], 400);
        }

        $perfil = Perfil::find($id);
        if (!$perfil) {
            return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre'      => 'required|string|max:100|unique:perfiles,nombre,' . $id,
            'descripcion' => 'nullable|string|max:255',
            'permisos'    => 'required|array|min:1',
            'permisos.*'  => 'string|max:100',
            'activo'      => 'nullable|boolean',
        ], [
            'nombre.required' => 'El nombre del perfil es requerido',
            'nombre.unique'   => 'Ya existe un perfil con ese nombre',
            'permisos.required' => 'Debes seleccionar al menos un permiso',
            'permisos.min'    => 'Debes seleccionar al menos un permiso',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors()
            ], 422);
        }

        $perfil->nombre = $request->input('nombre');
        $perfil->descripcion = $request->input('descripcion', '');
        if ($request->has('activo')) {
            $perfil->activo = $request->input('activo');
        }
        $perfil->save();

        // Reemplazar permisos: borrar existentes e insertar nuevos
        PerfilPermiso::where('perfil_id', $perfil->id)->delete();
        foreach ($request->input('permisos') as $permiso) {
            PerfilPermiso::create([
                'perfil_id' => $perfil->id,
                'permiso'   => $permiso,
            ]);
        }

        AuditLog::log($request, "Editó el perfil «{$perfil->nombre}» — " . count($request->input('permisos')) . " permisos asignados", 'Perfiles');

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
        ]);
    }

    /**
     * DELETE /api/perfiles/{id} — Eliminar un perfil
     */
    public function destroy(Request $request, $id = null)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'ID es requerido'], 400);
        }

        $perfil = Perfil::find($id);
        if (!$perfil) {
            return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
        }

        $nombre = $perfil->nombre;

        // Desasignar el perfil de todos los usuarios que lo tengan
        Usuario::where('perfil_id', $id)->update(['perfil_id' => null]);

        $perfil->delete(); // CASCADE eliminará perfil_permisos

        AuditLog::log($request, "Eliminó el perfil «{$nombre}»", 'Perfiles');

        return response()->json([
            'success' => true,
            'message' => 'Perfil eliminado correctamente',
        ]);
    }

    /**
     * PUT /api/perfiles/asignar — Asignar o quitar un perfil a un usuario
     */
    public function assign(Request $request)
    {
        $denied = $this->checkAdmin($request);
        if ($denied) return $denied;

        $validator = Validator::make($request->all(), [
            'usuario_id' => 'required|integer',
            'perfil_id'  => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $usuario = Usuario::find($request->input('usuario_id'));
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $perfilId = $request->input('perfil_id');

        if ($perfilId) {
            $perfil = Perfil::find($perfilId);
            if (!$perfil) {
                return response()->json(['success' => false, 'message' => 'Perfil no encontrado'], 404);
            }
            $usuario->perfil_id = $perfilId;
            $usuario->save();

            AuditLog::log($request, "Asignó el perfil «{$perfil->nombre}» al usuario «{$usuario->nombre}»", 'Perfiles');
        } else {
            $usuario->perfil_id = null;
            $usuario->save();

            AuditLog::log($request, "Removió el perfil del usuario «{$usuario->nombre}»", 'Perfiles');
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil asignado correctamente',
        ]);
    }
}
