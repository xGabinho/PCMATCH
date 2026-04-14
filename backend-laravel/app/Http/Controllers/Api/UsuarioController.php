<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Usuario;

class UsuarioController extends Controller
{
    /**
     * Equivalente a GET api/usuarios/index.php
     */
    public function index(Request $request)
    {
        // 1. Verificación del Rol (emulando requireAuth(['admin']))
        if (!in_array($request->user()->rol, ['admin', 'superadmin'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado. Se requiere rol de administrador.'
            ], 403);
        }

        // 2. Consulta de usuarios
        // Seleccionamos las columnas específicas que pedía el PHP y las ordenamos
        $usuarios = Usuario::select('id', 'nombre', 'apellido', 'correo', 'telefono', 'rol', 'activo', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Respuesta idéntica al original
        return response()->json([
            'usuarios' => $usuarios
        ]);
    }

    /**
     * Equivalente a POST api/usuarios/index.php (Crear usuario como Admin)
     */
    public function store(Request $request)
    {
        if (!in_array($request->user()->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|string|min:8',
            'rol' => 'nullable|in:admin,cliente'
        ], [
            'nombre.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.required' => 'Nombre, correo y contraseña son requeridos',
            'password.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'rol.in' => 'Rol inválido'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $usuario = new Usuario();
        $usuario->nombre = $request->input('nombre');
        $usuario->apellido = $request->input('apellido', '');
        $usuario->correo = $request->input('correo');
        $usuario->telefono = $request->input('telefono', '');
        $usuario->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        $usuario->rol = $request->input('rol', 'cliente');

        if (!$usuario->save()) {
            return response()->json(['success' => false, 'message' => 'Error al crear el usuario'], 500);
        }

        return response()->json([
            'message' => 'Usuario creado',
            'id' => $usuario->id
        ], 201);
    }

    /**
     * Equivalente a PUT api/usuarios/index.php (Editar usuario)
     */
    public function update(Request $request)
    {
        if (!in_array($request->user()->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // El backend legacy recibía el ID desde el body JSON
        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo,' . $id,
            'rol' => 'required|in:superadmin,admin,cliente',
            'activo' => 'nullable|boolean'
        ], [
            'nombre.required' => 'El nombre es requerido',
            'correo.required' => 'El correo es requerido',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'rol.in' => 'Rol inválido',
            'rol.required' => 'Rol es requerido',
            'activo.boolean' => 'El estado activo debe ser verdadero o falso'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $usuario->nombre = $request->input('nombre');
        $usuario->apellido = $request->input('apellido', '');
        $usuario->correo = $request->input('correo');
        $usuario->telefono = $request->input('telefono', '');
        $usuario->rol = $request->input('rol');
        if ($request->has('activo')) {
            $usuario->activo = $request->input('activo');
        }
        $usuario->save();

        return response()->json([
            'message' => 'Usuario actualizado'
        ]);
    }

    /**
     * Equivalente a DELETE api/usuarios/index.php (Eliminar usuario)
     */
    public function destroy(Request $request, $id = null)
    {
        if (!in_array($request->user()->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // El legacy lo pasaba pos HTTP GET param: ?id=X
        $id = $id ?? $request->query('id');
        
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $usuario = Usuario::find($id);
        if (!$usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado'
        ]);
    }
}
