<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Helpers\AuditLog;

class CatalogoController extends Controller
{
    
    /**
     * GET /api/catalogo — Lista pública del catálogo global
     * Obtiene una lista de registros o recursos.
     * Ejecuta la consulta a la base de datos (con posibles filtros/paginación) y retorna los datos en formato JSON.
     */
    
    public function index(Request $request)
    {
        $query = DB::table('productos_catalogo')
            ->select('id', 'nombre', 'categoria', 'especificacion', 'imagen_url',
                     'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama');

        if ($request->has('categoria')) {
            $query->where('categoria', $request->query('categoria'))
                  ->orderBy('nombre', 'ASC');
        } else {
            $query->orderBy('categoria', 'ASC')
                  ->orderBy('nombre', 'ASC');
        }

        if ($request->filled('buscar')) {
            $q = $request->query('buscar');
            $query->where(function ($w) use ($q) {
                $w->where('nombre', 'ILIKE', "%{$q}%")
                  ->orWhere('especificacion', 'ILIKE', "%{$q}%");
            });
        }

        $productos = $query->get();

        return response()->json([
            'productos' => $productos
        ]);
    }

    
    /**
     * POST /api/productos-catalogo — Crear un producto base completo (Admin/Superadmin)
     * Almacena un nuevo registro en la base de datos.
     * Valida la información recibida en la petición HTTP y crea el nuevo recurso.
     */

    
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->rol ?? '', ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('componentes.crear')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.crear'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre'        => 'required|string|max:255',
            'categoria'     => 'required|string|in:CPU,GPU,RAM,Storage,PSU,Motherboard,Cooler,Case',
            'especificacion' => 'nullable|string|max:1000',
            'nucleos'       => 'nullable|integer|min:1',
            'hilos'         => 'nullable|integer|min:1',
            'frecuencia_hz' => 'nullable|numeric|min:0',
            'enfoque_uso'   => 'nullable|in:estudio,oficina,gaming,diseño',
            'gama'          => 'nullable|in:alta,media,baja',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $imagenUrl = null;
        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('catalogo', 'supabase');
            $imagenUrl = Storage::disk('supabase')->url($path);
        }

        $id = DB::table('productos_catalogo')->insertGetId([
            'nombre'        => $request->input('nombre'),
            'categoria'     => $request->input('categoria'),
            'especificacion' => $request->input('especificacion'),
            'imagen_url'    => $imagenUrl,
            'nucleos'       => $request->input('nucleos'),
            'hilos'         => $request->input('hilos'),
            'frecuencia_hz' => $request->input('frecuencia_hz'),
            'enfoque_uso'   => $request->input('enfoque_uso'),
            'gama'          => $request->input('gama'),
        ]);

        $producto = DB::table('productos_catalogo')->where('id', $id)->first();

        AuditLog::log($request, "Creó el producto base «{$request->input('nombre')}» ({$request->input('categoria')})", 'Catálogo');

        return response()->json([
            'success'  => true,
            'message'  => 'Producto base creado',
            'producto' => $producto
        ], 201);
    }

    /**
     * PUT /api/productos-catalogo — Editar un producto base (Admin/Superadmin)
     */
    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->rol ?? '', ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('componentes.editar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.editar'], 403);
        }

        $id = $request->input('id');
        if (!$id) return response()->json(['success' => false, 'message' => 'id es requerido'], 400);

        $producto = DB::table('productos_catalogo')->where('id', $id)->first();
        if (!$producto) return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);

        $validator = Validator::make($request->all(), [
            'nombre'        => 'nullable|string|max:255',
            'categoria'     => 'nullable|string|in:CPU,GPU,RAM,Storage,PSU,Motherboard,Cooler,Case',
            'especificacion' => 'nullable|string|max:1000',
            'nucleos'       => 'nullable|integer|min:1',
            'hilos'         => 'nullable|integer|min:1',
            'frecuencia_hz' => 'nullable|numeric|min:0',
            'enfoque_uso'   => 'nullable|in:estudio,oficina,gaming,diseño',
            'gama'          => 'nullable|in:alta,media,baja',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        $data = [];
        foreach (['nombre', 'categoria', 'especificacion', 'nucleos', 'hilos', 'frecuencia_hz', 'enfoque_uso', 'gama'] as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->input($field);
            }
        }

        if ($request->hasFile('imagen')) {
            if ($producto->imagen_url) {
                $oldPath = 'catalogo/' . basename($producto->imagen_url);
                Storage::disk('supabase')->delete($oldPath);
            }
            $path = $request->file('imagen')->store('catalogo', 'supabase');
            $data['imagen_url'] = Storage::disk('supabase')->url($path);
        }

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No se enviaron campos para actualizar'], 400);
        }

        DB::table('productos_catalogo')->where('id', $id)->update($data);

        AuditLog::log($request, "Editó el producto base «{$producto->nombre}» (ID: {$id})", 'Catálogo');

        return response()->json(['success' => true, 'message' => 'Producto actualizado']);
    }

    /**
     * DELETE /api/productos-catalogo/{id} — Eliminar un producto base (Admin/Superadmin)
     */
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        if (!$user || !in_array($user->rol ?? '', ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($user->rol === 'admin' && !$user->hasPermission('componentes.eliminar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: componentes.eliminar'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) return response()->json(['success' => false, 'message' => 'id es requerido'], 400);

        $producto = DB::table('productos_catalogo')->where('id', $id)->first();
        if (!$producto) return response()->json(['success' => false, 'message' => 'Producto no encontrado'], 404);

        // Check if any componentes use this product
        $count = DB::table('componentes')->where('producto_id', $id)->whereNull('deleted_at')->count();
        if ($count > 0) {
            return response()->json(['success' => false, 'message' => "No se puede eliminar. Hay {$count} componentes activos usando este producto."], 409);
        }

        DB::table('proveedor_producto_catalogo')->where('producto_catalogo_id', $id)->delete();
        DB::table('productos_catalogo')->where('id', $id)->delete();

        AuditLog::log($request, "Eliminó el producto base «{$producto->nombre}»", 'Catálogo');

        return response()->json(['success' => true, 'message' => 'Producto eliminado']);
    }
}
