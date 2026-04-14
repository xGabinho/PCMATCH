<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use App\Models\Proveedor;
use App\Models\Bodega;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Correo y contraseña son requeridos',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('correo');
        $password = $request->input('password');

        // 1. Buscar en usuarios
        $usuario = Usuario::where('correo', $email)->first();
        if ($usuario && password_verify($password, $usuario->password)) {
            if (!$usuario->activo) {
                return response()->json(['success' => false, 'message' => 'Este usuario está desactivado'], 403);
            }
            $token = $usuario->createToken('auth_token_usuario')->plainTextToken;
            return response()->json([
                'token' => $token,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'correo' => $usuario->correo,
                    'rol' => $usuario->rol
                ]
            ]);
        }

        // 2. Buscar en bodegas
        $bodega = Bodega::where('correo', $email)->first();
        if ($bodega && password_verify($password, $bodega->password)) {
            if (!$bodega->activa) {
                return response()->json(['success' => false, 'message' => 'Esta bodega está desactivada'], 403);
            }
            $token = $bodega->createToken('auth_token_bodega')->plainTextToken;
            return response()->json([
                'token' => $token,
                'usuario' => [
                    'id' => $bodega->id,
                    'nombre' => $bodega->nombre,
                    'correo' => $bodega->correo,
                    'rol' => 'bodega'
                ]
            ]);
        }

        // 3. Buscar en proveedores
        $proveedor = Proveedor::where('correo', $email)->first();
        if ($proveedor && password_verify($password, $proveedor->password)) {
            if (!$proveedor->activo) {
                return response()->json(['success' => false, 'message' => 'Este proveedor está desactivado'], 403);
            }
            if ($proveedor->estado_aprobacion !== 'aprobado') {
                return response()->json(['success' => false, 'message' => 'Su cuenta está ' . $proveedor->estado_aprobacion . ' de aprobación.'], 403);
            }
            $token = $proveedor->createToken('auth_token_proveedor')->plainTextToken;
            return response()->json([
                'token' => $token,
                'usuario' => [
                    'id' => $proveedor->id,
                    'nombre' => $proveedor->nombre,
                    'correo' => $proveedor->correo,
                    'rol' => 'proveedor'
                ]
            ]);
        }

        // 4. Ninguno encontrado
        return response()->json([
            'success' => false,
            'message' => 'Correo o contraseña incorrectos'
        ], 401);
    }

    public function register(Request $request)
    {
        // 1. Validaciones
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:usuarios,correo',
            'password' => 'required|string|min:8',
        ], [
            'nombre.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.required' => 'Nombre, correo y contraseña son requeridos',
            'password.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres'
        ]);

        if ($validator->fails()) {
            // Retornamos el primer error exacto como si fuera el backend nativo (que usaba un helper error())
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // 2. Insertar en base de datos usando Eloquent puro
        $usuario = new Usuario();
        $usuario->nombre = $request->input('nombre');
        $usuario->apellido = $request->input('apellido', '');
        $usuario->correo = $request->input('correo');
        $usuario->telefono = $request->input('telefono', '');
        // Hasheamos la contraseña de forma compatible con PHP Nativo o el que traíamos
        $usuario->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        $usuario->rol = 'cliente';

        if (!$usuario->save()) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario'
            ], 500);
        }

        // 3. Generar token
        $token = $usuario->createToken('auth_token_usuario')->plainTextToken;

        // 4. Respuesta JSON tal cual la esperaba el viejo frontend
        return response()->json([
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'rol' => $usuario->rol
            ]
        ], 201);
    }
}
