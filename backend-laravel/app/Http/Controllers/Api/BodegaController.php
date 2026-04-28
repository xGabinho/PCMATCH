<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\AuditLog;

class BodegaController extends Controller
{
    /**
     * Equivalente a GET api/bodegas/index.php
     */
    public function index(Request $request)
    {
        // 1. Determinar el rol real basado en el modelo que se autenticó
        $user = $request->user();
        $clase = get_class($user);
        $rol = null;

        if ($clase === \App\Models\Proveedor::class) {
            $rol = 'proveedor';
        } elseif ($clase === \App\Models\Bodega::class) {
            $rol = 'bodega';
        } elseif ($clase === \App\Models\Usuario::class) {
            $rol = $user->rol; // 'admin', 'superadmin', 'cliente'
        }

        // 2. Verificar permisos
        if (!in_array($rol, ['superadmin', 'admin', 'proveedor'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        // 3. Recrear las consultas Left Join usando DB Builder (evitamos crear el modelo Componentes prematuramente)
        $query = DB::table('bodegas as b')
            ->leftJoin('componentes as c', 'c.bodega_id', '=', 'b.id')
            ->groupBy('b.id', 'b.nombre', 'b.telefono', 'b.correo', 'b.activa', 'b.proveedor_id');

        if ($rol === 'proveedor') {
            // Proveedor solo ve sus bodegas y cuenta los componentes
            $query->where('b.proveedor_id', $user->id)
                  ->select(
                      'b.id', 'b.nombre', 'b.telefono', 'b.correo', 'b.activa', 'b.proveedor_id',
                      DB::raw('COUNT(c.id) AS total_componentes')
                  );
        } else {
            // Admin y Superadmin ven todas, además cruzamos el nombre del proveedor
            $query->leftJoin('proveedores as p', 'p.id', '=', 'b.proveedor_id')
                  ->groupBy('p.nombre')
                  ->select(
                      'b.id', 'b.nombre', 'b.telefono', 'b.correo', 'b.activa', 'b.proveedor_id',
                      'p.nombre AS proveedor_nombre',
                      DB::raw('COUNT(c.id) AS total_componentes')
                  );
        }

        $bodegas = $query->orderBy('b.nombre', 'ASC')->get();

        return response()->json([
            'bodegas' => $bodegas
        ]);
    }

    /**
     * Equivalente a POST api/bodegas/index.php
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        $rol = $clase === \App\Models\Proveedor::class ? 'proveedor' : ($clase === \App\Models\Usuario::class ? $user->rol : 'bodega');

        // admin, superadmin y proveedor pueden crear bodegas. 'bodega' no puede.
        if ($rol === 'bodega') {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para crear bodegas'], 403);
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|unique:bodegas,correo',
            'password' => 'required|string|min:8',
            'proveedor_id' => 'nullable|integer'
        ], [
            'nombre.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.required' => 'Nombre, correo y contraseña son requeridos',
            'password.required' => 'Nombre, correo y contraseña son requeridos',
            'correo.email' => 'Correo inválido',
            'correo.unique' => 'El correo ya está registrado',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $proveedor_id = $request->input('proveedor_id');
        if ($rol === 'proveedor') {
            $proveedor_id = $user->id; // El proveedor auto-asigna su ID
        }

        // Validar que el proveedor asignado esté activo
        if ($proveedor_id) {
            $proveedor = \App\Models\Proveedor::find($proveedor_id);
            if (!$proveedor || $proveedor->activo != 1) {
                return response()->json(['success' => false, 'message' => 'No se puede asignar un proveedor inactivo'], 400);
            }
        }

        $bodega = new \App\Models\Bodega();
        $bodega->nombre = $request->input('nombre');
        $bodega->telefono = $request->input('telefono', '');
        $bodega->correo = $request->input('correo');
        $bodega->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        $bodega->activa = 1;
        $bodega->proveedor_id = $proveedor_id;
        $bodega->save();

        AuditLog::log($request, "Creó la bodega «{$bodega->nombre}»", 'Bodegas');

        return response()->json(['message' => 'Bodega creada', 'id' => $bodega->id], 201);
    }

    /**
     * Equivalente a PUT api/bodegas/index.php
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $clase = get_class($user);
        $rol = $clase === \App\Models\Proveedor::class ? 'proveedor' : ($clase === \App\Models\Usuario::class ? $user->rol : 'bodega');

        $id = $request->input('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $bodega = \App\Models\Bodega::find($id);
        if (!$bodega) {
            return response()->json(['success' => false, 'message' => 'Bodega no encontrada'], 404);
        }

        if ($rol === 'proveedor' && $bodega->proveedor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para editar esta bodega'], 403);
        }

        $bodega->nombre = $request->input('nombre', $bodega->nombre);
        $bodega->telefono = $request->input('telefono', $bodega->telefono);
        
        if ($request->has('activa')) {
            $bodega->activa = (int) $request->input('activa');
        }

        // Superadmin puede reasignar proveedor
        if ($rol === 'superadmin' && $request->has('proveedor_id')) {
            $prov = $request->input('proveedor_id');
            if ($prov !== '' && $prov !== null) {
                $proveedorModel = \App\Models\Proveedor::find((int) $prov);
                if (!$proveedorModel || $proveedorModel->activo != 1) {
                    return response()->json(['success' => false, 'message' => 'No se puede asignar un proveedor inactivo'], 400);
                }
                $bodega->proveedor_id = (int) $prov;
            } else {
                $bodega->proveedor_id = null;
            }
        }

        // Admin puede reasignar proveedor
        if ($rol === 'admin' && $request->has('proveedor_id')) {
            $prov = $request->input('proveedor_id');
            if ($prov !== '' && $prov !== null) {
                $proveedorModel = \App\Models\Proveedor::find((int) $prov);
                if (!$proveedorModel || $proveedorModel->activo != 1) {
                    return response()->json(['success' => false, 'message' => 'No se puede asignar un proveedor inactivo'], 400);
                }
                $bodega->proveedor_id = (int) $prov;
            } else {
                $bodega->proveedor_id = null;
            }
        }

        $dirty = $bodega->getDirty();
        $detalles = AuditLog::formatChanges($dirty, $bodega);
        $bodega->save();

        AuditLog::log($request, "Editó la bodega «{$bodega->nombre}» — {$detalles}", 'Bodegas');

        return response()->json(['message' => 'Bodega actualizada']);
    }

    /**
     * Equivalente a DELETE api/bodegas/index.php
     */
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $clase = get_class($user);
        $rol = $clase === \App\Models\Proveedor::class ? 'proveedor' : ($clase === \App\Models\Usuario::class ? $user->rol : 'bodega');

        if ($rol === 'bodega') {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para eliminar bodegas'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $bodega = \App\Models\Bodega::find($id);
        if (!$bodega) {
            return response()->json(['success' => false, 'message' => 'Bodega no encontrada'], 404);
        }

        if ($rol === 'superadmin' || $rol === 'admin') {
            // Si es admin/superadmin, solo bloqueamos si tiene un proveedor Y NO es el proveedor quien lo borra
            // Pero en realidad, el admin debería poder borrarla si quiere, 
            // aunque el requerimiento previo decía que no si tenía proveedor.
            // Vamos a mantener la restricción para admins pero permitirla para proveedores sobre sus propias bodegas.
            if (!is_null($bodega->proveedor_id)) {
                return response()->json(['success' => false, 'message' => 'No se puede eliminar una bodega si tiene un proveedor asignado'], 400);
            }
        }

        if ($rol === 'proveedor' && $bodega->proveedor_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para eliminar esta bodega'], 403);
        }

        // Limpiar componentes e ítems de cotización antes de borrar la bodega
        $componenteIds = DB::table('componentes')->where('bodega_id', $id)->pluck('id');
        if ($componenteIds->isNotEmpty()) {
            DB::table('cotizacion_items')->whereIn('componente_id', $componenteIds)->delete();
            DB::table('componentes')->where('bodega_id', $id)->delete();
        }

        $bodega->delete();

        AuditLog::log($request, "Eliminó la bodega «{$bodega->nombre}»", 'Bodegas');

        return response()->json(['message' => 'Bodega eliminada']);
    }
}
