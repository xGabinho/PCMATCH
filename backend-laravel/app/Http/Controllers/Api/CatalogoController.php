<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    /**
     * Equivalente a GET api/catalogo/index.php (Público)
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
}
