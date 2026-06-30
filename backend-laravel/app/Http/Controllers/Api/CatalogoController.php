<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    
    /**
    
     * Obtiene una lista de registros o recursos.
    
     * Ejecuta la consulta a la base de datos (con posibles filtros/paginación) y retorna los datos en formato JSON.
    
     */
    
    public function index(Request $request)
    {
        $query = DB::table('productos_catalogo')
            ->select('id', 'nombre', 'categoria');

        if ($request->has('categoria')) {
            $query->where('categoria', $request->query('categoria'))
                  ->orderBy('nombre', 'ASC');
        } else {
            $query->orderBy('categoria', 'ASC')
                  ->orderBy('nombre', 'ASC');
        }

        $productos = $query->get();

        return response()->json([
            'productos' => $productos
        ]);
    }

    
    /**

    
     * Almacena un nuevo registro en la base de datos.

    
     * Valida la información recibida en la petición HTTP y crea el nuevo recurso.

    
     */

    
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !in_array($user->rol, ['admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'categoria' => 'required|string|max:100'
        ]);

        $id = DB::table('productos_catalogo')->insertGetId([
            'nombre' => $request->input('nombre'),
            'categoria' => $request->input('categoria')
        ]);

        $producto = DB::table('productos_catalogo')->where('id', $id)->first();

        return response()->json([
            'success' => true,
            'message' => 'Producto base creado',
            'producto' => $producto
        ], 201);
    }
}
