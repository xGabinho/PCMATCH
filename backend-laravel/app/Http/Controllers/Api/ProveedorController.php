<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Proveedor;
use App\Models\Bodega;
use App\Helpers\AuditLog;

class ProveedorController extends Controller
{
    /**
     * Verifica que el usuario sea admin o superadmin
     */
    private function checkSuperAdmin(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        
        if ($clase !== \App\Models\Usuario::class || !in_array($user->rol, ['admin', 'superadmin'])) {
            return false;
        }
        return true;
    }

    /**
     * Equivalente a GET api/proveedores/index.php
     */
    public function index(Request $request)
    {
        if (!$this->checkSuperAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $proveedores = DB::table('proveedores as p')
            ->leftJoin('bodegas as b', 'b.proveedor_id', '=', 'p.id')
            ->groupBy('p.id', 'p.nombre', 'p.correo', 'p.activo', 'p.created_at', 'p.identificacion_legal', 'p.razon_social', 'p.estado_aprobacion', 'p.documento_soporte')
            ->select('p.id', 'p.nombre', 'p.correo', 'p.activo', 'p.created_at', 'p.identificacion_legal', 'p.razon_social', 'p.estado_aprobacion', 'p.documento_soporte', DB::raw('COUNT(b.id) AS total_bodegas'))
            ->orderBy('p.created_at', 'DESC')
            ->get();

        // Convert the relative path of documents to full URLs
        foreach ($proveedores as $prov) {
            if ($prov->documento_soporte) {
                $prov->documento_soporte_url = url('storage/' . $prov->documento_soporte);
            } else {
                $prov->documento_soporte_url = null;
            }
        }

        return response()->json([
            'proveedores' => $proveedores
        ]);
    }

    /**
     * Equivalente a POST api/proveedores/index.php
     */
    public function store(Request $request)
    {
        if (!$this->checkSuperAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:proveedores,correo',
            'password' => 'required|string|min:8',
            'identificacion_legal' => 'required|string|max:255|unique:proveedores,identificacion_legal',
            'razon_social' => 'required|string|max:255',
            'documento_soporte' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
            'estado_aprobacion' => 'nullable|string|in:pendiente,aprobado,rechazado'
        ], [
            'nombre.required' => 'El nombre es requerido',
            'correo.required' => 'El correo es requerido',
            'password.required' => 'La contraseña debe tener al menos 8 caracteres',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'identificacion_legal.required' => 'La identificación legal es requerida',
            'identificacion_legal.unique' => 'La identificación legal ya está en uso',
            'razon_social.required' => 'La razón social es requerida',
            'documento_soporte.file' => 'El documento de soporte debe ser un archivo',
            'documento_soporte.mimes' => 'El documento de soporte debe ser PDF, JPG, JPEG o PNG'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $proveedor = new Proveedor();
        $proveedor->nombre = $request->input('nombre');
        $proveedor->correo = $request->input('correo');
        $proveedor->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        
        $proveedor->identificacion_legal = $request->input('identificacion_legal');
        $proveedor->razon_social = $request->input('razon_social');
        if ($request->has('estado_aprobacion')) {
            $proveedor->estado_aprobacion = $request->input('estado_aprobacion');
        }

        if ($request->hasFile('documento_soporte')) {
            $path = $request->file('documento_soporte')->store('documentos_proveedores', 'public');
            $proveedor->documento_soporte = $path;
        }

        $proveedor->save();

        AuditLog::log($request, "Registró el proveedor «{$proveedor->nombre}» ({$proveedor->razon_social})", 'Proveedores');

        return response()->json(['message' => 'Proveedor creado', 'id' => $proveedor->id], 201);
    }

    /**
     * Equivalente a PUT api/proveedores/index.php
     */
    public function update(Request $request)
    {
        if (!$this->checkSuperAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'activo' => 'nullable|integer',
            'estado_aprobacion' => 'nullable|string|in:pendiente,aprobado,rechazado',
            'identificacion_legal' => 'nullable|string|max:255',
            'razon_social' => 'nullable|string|max:255'
        ], [
            'nombre.required' => 'El nombre es requerido'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $proveedor->nombre = $request->input('nombre');
        
        if ($request->has('identificacion_legal')) {
            $proveedor->identificacion_legal = $request->input('identificacion_legal');
        }
        if ($request->has('razon_social')) {
            $proveedor->razon_social = $request->input('razon_social');
        }
        if ($request->has('activo')) {
            $proveedor->activo = (int) $request->input('activo');
        }
        if ($request->has('estado_aprobacion')) {
            if ($request->user()->rol !== 'superadmin') {
                return response()->json(['success' => false, 'message' => 'Solo el Super Administrador puede aprobar o rechazar proveedores'], 403);
            }
            $proveedor->estado_aprobacion = $request->input('estado_aprobacion');
        }

        $dirty = $proveedor->getDirty();
        $detalles = AuditLog::formatChanges($dirty, $proveedor);
        $proveedor->save();

        AuditLog::log($request, "Editó el proveedor «{$proveedor->nombre}» — {$detalles}", 'Proveedores');

        return response()->json(['message' => 'Proveedor actualizado']);
    }

    /**
     * Equivalente a DELETE api/proveedores/index.php
     */
    public function destroy(Request $request, $id = null)
    {
        if (!$this->checkSuperAdmin($request)) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $proveedor = Proveedor::find($id);
        if (!$proveedor) {
            return response()->json(['success' => false, 'message' => 'Proveedor no encontrado'], 404);
        }

        // Desasociar bodegas antes de eliminar
        Bodega::where('proveedor_id', $id)->update(['proveedor_id' => null]);

        $proveedor->delete();

        AuditLog::log($request, "Eliminó el proveedor «{$proveedor->nombre}»", 'Proveedores');

        return response()->json(['message' => 'Proveedor eliminado']);
    }
}
