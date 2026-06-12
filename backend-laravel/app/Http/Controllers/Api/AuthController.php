<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Usuario;
use App\Models\Proveedor;
use App\Models\Bodega;
use App\Helpers\AuditLog;

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

            $permisos = [];
            if ($usuario->perfil_id) {
                $perfil = \App\Models\Perfil::with('permisos')->find($usuario->perfil_id);
                if ($perfil && $perfil->activo) {
                    $permisos = $perfil->permisos->pluck('permiso')->toArray();
                }
            }

            return response()->json([
                'token' => $token,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'correo' => $usuario->correo,
                    'rol' => $usuario->rol,
                    'perfil_id' => $usuario->perfil_id,
                    'permisos' => $permisos
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

        // Registrar en historial de auditoría
        AuditLog::log($request, "Se registró como nuevo usuario", 'Usuarios', $usuario);

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

    /**
     * GET /api/auth/profile — Obtener datos del perfil autenticado
     */
    public function profile(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);

        if ($clase === Bodega::class) {
            return response()->json([
                'perfil' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'correo' => $user->correo,
                    'telefono' => $user->telefono,
                    'rol' => 'bodega',
                ],
                'tipo' => 'bodega'
            ]);
        }

        if ($clase === Proveedor::class) {
            return response()->json([
                'perfil' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'correo' => $user->correo,
                    'identificacion_legal' => $user->identificacion_legal,
                    'razon_social' => $user->razon_social,
                    'rol' => 'proveedor',
                ],
                'tipo' => 'proveedor'
            ]);
        }

        // Usuario (cliente, admin, superadmin)
        $permisos = [];
        if ($user->perfil_id) {
            $perfil = \App\Models\Perfil::with('permisos')->find($user->perfil_id);
            if ($perfil && $perfil->activo) {
                $permisos = $perfil->permisos->pluck('permiso')->toArray();
            }
        }

        return response()->json([
            'perfil' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'apellido' => $user->apellido,
                'correo' => $user->correo,
                'telefono' => $user->telefono,
                'rol' => $user->rol,
                'perfil_id' => $user->perfil_id,
                'permisos' => $permisos
            ],
            'tipo' => 'usuario'
        ]);
    }

    /**
     * PUT /api/auth/profile — Actualizar datos del perfil autenticado
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);

        // --- Validación de contraseña actual si se quiere cambiar ---
        if ($request->filled('password')) {
            if (!$request->filled('password_actual')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debes ingresar tu contraseña actual para cambiarla',
                    'errors' => ['password_actual' => ['Contraseña actual requerida']]
                ], 422);
            }
            if (!password_verify($request->input('password_actual'), $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta',
                    'errors' => ['password_actual' => ['Contraseña actual incorrecta']]
                ], 422);
            }
        }

        // --- Validación según tipo de modelo ---
        if ($clase === Bodega::class) {
            $rules = [
                'nombre' => 'required|string|max:150',
                'correo' => 'required|email|max:150|unique:bodegas,correo,' . $user->id,
                'telefono' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:8',
            ];
        } elseif ($clase === Proveedor::class) {
            $rules = [
                'nombre' => 'required|string|max:100',
                'correo' => 'required|email|max:100|unique:proveedores,correo,' . $user->id,
                'password' => 'nullable|string|min:8',
            ];
        } else {
            $rules = [
                'nombre' => 'required|string|max:100',
                'apellido' => 'nullable|string|max:100',
                'correo' => 'required|email|max:150|unique:usuarios,correo,' . $user->id,
                'telefono' => 'nullable|string|max:20',
                'password' => 'nullable|string|min:8',
            ];
        }

        $messages = [
            'nombre.required' => 'El nombre es requerido',
            'correo.required' => 'El correo es requerido',
            'correo.email' => 'El correo no es válido',
            'correo.unique' => 'Este correo ya está en uso por otra cuenta',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        // --- Aplicar cambios ---
        $user->nombre = $request->input('nombre');
        $user->correo = $request->input('correo');

        if ($clase === Bodega::class) {
            $user->telefono = $request->input('telefono', $user->telefono);
        } elseif ($clase === Proveedor::class) {
            // Proveedor solo edita nombre y correo desde perfil
        } else {
            $user->apellido = $request->input('apellido', $user->apellido);
            $user->telefono = $request->input('telefono', $user->telefono);
        }

        if ($request->filled('password')) {
            $user->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        }

        // Audit trail
        $dirty = $user->getDirty();
        $cambios = [];
        foreach ($dirty as $campo => $nuevo) {
            if ($campo === 'password') {
                $cambios[] = 'contraseña actualizada';
                continue;
            }
            $viejo = $user->getOriginal($campo);
            $cambios[] = "{$campo}: '{$viejo}' → '{$nuevo}'";
        }

        $user->save();

        $detalles = empty($cambios) ? 'Sin cambios' : implode(', ', $cambios);
        $rol = ($clase === Bodega::class) ? 'bodega' : (($clase === Proveedor::class) ? 'proveedor' : $user->rol);
        AuditLog::log($request, "Editó su propio perfil — {$detalles}", 'Perfil');

        // Retornar datos actualizados
        $perfil = [
            'id' => $user->id,
            'nombre' => $user->nombre,
            'correo' => $user->correo,
            'rol' => $rol,
        ];

        if ($clase === Bodega::class) {
            $perfil['telefono'] = $user->telefono;
        } elseif ($clase === Proveedor::class) {
            $perfil['identificacion_legal'] = $user->identificacion_legal;
            $perfil['razon_social'] = $user->razon_social;
        } else {
            $perfil['apellido'] = $user->apellido;
            $perfil['telefono'] = $user->telefono;
            $perfil['perfil_id'] = $user->perfil_id;
            
            $permisos = [];
            if ($user->perfil_id) {
                $perfil_model = \App\Models\Perfil::with('permisos')->find($user->perfil_id);
                if ($perfil_model && $perfil_model->activo) {
                    $permisos = $perfil_model->permisos->pluck('permiso')->toArray();
                }
            }
            $perfil['permisos'] = $permisos;
        }

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'perfil' => $perfil
        ]);
    }
}
