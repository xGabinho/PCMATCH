<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordMail;
use App\Models\Usuario;
use App\Models\Bodega;
use App\Models\Proveedor;
use Carbon\Carbon;

class PasswordResetController extends Controller
{
    
    /**
    
     * Endpoint lógico de la API.
    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.
    
     */
    
    public function sendResetLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
        ], [
            'correo.required' => 'El correo electrónico es requerido',
            'correo.email' => 'El formato del correo no es válido',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $email = $request->input('correo');

        // Buscar en las 3 tablas
        $tipo = null;
        $nombre = null;

        $usuario = Usuario::where('correo', $email)->first();
        if ($usuario) {
            $tipo = 'usuario';
            $nombre = $usuario->nombre;
        }

        if (!$tipo) {
            $bodega = Bodega::where('correo', $email)->first();
            if ($bodega) {
                $tipo = 'bodega';
                $nombre = $bodega->nombre;
            }
        }

        if (!$tipo) {
            $proveedor = Proveedor::where('correo', $email)->first();
            if ($proveedor) {
                $tipo = 'proveedor';
                $nombre = $proveedor->nombre;
            }
        }

        // Siempre responder con éxito (no revelar si el email existe o no)
        if (!$tipo) {
            return response()->json([
                'success' => true,
                'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
            ]);
        }

        // Generar token
        $token = Str::random(64);

        // Eliminar tokens anteriores para este email+tipo
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('tipo', $tipo)
            ->delete();

        // Guardar nuevo token hasheado
        DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'tipo' => $tipo,
            'token' => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        // Construir URL de restablecimiento
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');
        $resetUrl = "{$frontendUrl}/restablecer-password?token={$token}&email=" . urlencode($email);

        // Enviar email
        try {
            Mail::to($email)->send(new ResetPasswordMail($resetUrl, $nombre));
        } catch (\Exception $e) {
            // Log the error but don't expose it to the user
            \Log::error('Error enviando email de reset: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, recibirás un enlace para restablecer tu contraseña.'
        ]);
    }

    
    /**

    
     * Endpoint lógico de la API.

    
     * Procesa la petición HTTP, interactúa con los modelos y retorna una respuesta JSON.

    
     */

    
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Token de recuperación requerido',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'Correo inválido',
            'password.required' => 'La nueva contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $email = $request->input('email');
        $token = $request->input('token');
        $password = $request->input('password');

        // Buscar todos los tokens para este email
        $records = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'El enlace de recuperación no es válido o ha expirado.'
            ], 400);
        }

        // Encontrar el registro con el token correcto
        $matchedRecord = null;
        foreach ($records as $record) {
            if (Hash::check($token, $record->token)) {
                $matchedRecord = $record;
                break;
            }
        }

        if (!$matchedRecord) {
            return response()->json([
                'success' => false,
                'message' => 'El enlace de recuperación no es válido o ha expirado.'
            ], 400);
        }

        // Verificar que no haya expirado (60 minutos)
        if (Carbon::parse($matchedRecord->created_at)->addMinutes(60)->isPast()) {
            // Limpiar token expirado
            DB::table('password_reset_tokens')
                ->where('email', $email)
                ->where('tipo', $matchedRecord->tipo)
                ->delete();

            return response()->json([
                'success' => false,
                'message' => 'El enlace de recuperación ha expirado. Solicita uno nuevo.'
            ], 400);
        }

        // Actualizar la contraseña en la tabla correspondiente
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        switch ($matchedRecord->tipo) {
            case 'usuario':
                Usuario::where('correo', $email)->update(['password' => $hashedPassword]);
                break;
            case 'bodega':
                Bodega::where('correo', $email)->update(['password' => $hashedPassword]);
                break;
            case 'proveedor':
                Proveedor::where('correo', $email)->update(['password' => $hashedPassword]);
                break;
        }

        // Limpiar token usado
        DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('tipo', $matchedRecord->tipo)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tu contraseña ha sido actualizada correctamente.'
        ]);
    }
}
