<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\AuditLog;
use App\Mail\CotizacionEnviadaMail;

class CotizacionController extends Controller
{
    
    /**
    
     * Obtiene una lista de registros o recursos.
    
     * Ejecuta la consulta a la base de datos (con posibles filtros/paginación) y retorna los datos en formato JSON.
    
     */
    
    public function index(Request $request)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        // Validación principal
        if (!in_array($rol, ['cliente', 'admin', 'superadmin', 'proveedor'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' || $rol === 'superadmin') {
            if ($rol === 'admin' && !$user->hasPermission('cotizaciones.ver')) {
                return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: cotizaciones.ver'], 403);
            }
            $query = DB::table('cotizaciones as c')
                ->join('usuarios as u', 'c.usuario_id', '=', 'u.id')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    'u.nombre', 'u.apellido', 'u.correo',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at', 'u.nombre', 'u.apellido', 'u.correo');
        } 
        elseif ($rol === 'proveedor') {
            // Proveedor ve cotizaciones que incluyen componentes de sus bodegas
            $query = DB::table('cotizaciones as c')
                ->join('usuarios as u', 'c.usuario_id', '=', 'u.id')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->leftJoin('componentes as comp', 'ci.componente_id', '=', 'comp.id')
                ->leftJoin('bodegas as b', 'comp.bodega_id', '=', 'b.id')
                ->where('b.proveedor_id', $user->id)
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    'u.nombre', 'u.apellido', 'u.correo',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at', 'u.nombre', 'u.apellido', 'u.correo');
        } 
        else {
            // Cliente solo ve sus propias cotizaciones
            $query = DB::table('cotizaciones as c')
                ->leftJoin('cotizacion_items as ci', 'ci.cotizacion_id', '=', 'c.id')
                ->where('c.usuario_id', $user->id)
                ->select(
                    'c.id', 'c.perfil', 'c.total', 'c.created_at',
                    DB::raw('COUNT(ci.id) AS total_items')
                )
                ->groupBy('c.id', 'c.perfil', 'c.total', 'c.created_at');
        }

        $cotizaciones = $query->orderBy('c.created_at', 'DESC')->paginate(15);

        return response()->json([
            'cotizaciones' => $cotizaciones
        ]);
    }

    
    /**

    
     * Almacena un nuevo registro en la base de datos.

    
     * Valida la información recibida en la petición HTTP y crea el nuevo recurso.

    
     */

    
    public function store(Request $request)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        if ($rol !== 'cliente') {
            return response()->json(['success' => false, 'message' => 'Solo los clientes pueden crear cotizaciones'], 403);
        }

        $items = $request->input('items', []);
        if (empty($items)) {
            return response()->json(['success' => false, 'message' => 'Debes seleccionar al menos un componente'], 400);
        }

        $total = $request->input('total', 0);
        $perfilesValidos = ['gaming', 'oficina', 'diseño', 'estudio'];
        $perfil = in_array($request->input('perfil'), $perfilesValidos) ? $request->input('perfil') : 'gaming';

        // Usamos una trasacción de base de datos para asegurar integridad
        $cotizacionId = null;
        $codigoUnico = 'COT-' . strtoupper(Str::random(8));
        
        try {
            DB::transaction(function () use ($user, $perfil, $total, $items, &$cotizacionId, $codigoUnico) {
                // 1. Insertar la cotización madre
                $cotizacionId = DB::table('cotizaciones')->insertGetId([
                    'usuario_id' => $user->id,
                    'perfil' => $perfil,
                    'total' => $total,
                    'codigo' => $codigoUnico,
                    'created_at' => now(),
                    'stock_restaurado' => false
                ]);

                // 2. Insertar cada uno de los items y descontar stock
                $itemsData = [];
                foreach ($items as $item) {
                    $cantidad = $item['cantidad'] ?? 1;
                    
                    // Validar stock del componente
                    $componente = DB::table('componentes')->where('id', $item['componente_id'])->lockForUpdate()->first();
                    if (!$componente) {
                        throw new \Exception("El componente con ID {$item['componente_id']} no existe.");
                    }
                    if ($componente->stock < $cantidad) {
                        throw new \Exception("Stock insuficiente para el componente: {$componente->especificacion}. Disponible: {$componente->stock}");
                    }
                    
                    // Descontar stock
                    DB::table('componentes')->where('id', $item['componente_id'])->decrement('stock', $cantidad);

                    $itemsData[] = [
                        'cotizacion_id' => $cotizacionId,
                        'componente_id' => $item['componente_id'],
                        'cantidad' => $cantidad,
                        'precio_unitario' => $item['precio']
                    ];
                }
                DB::table('cotizacion_items')->insert($itemsData);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        // 3. Obtener los datos completos para el PDF
        $cotizacion = DB::table('cotizaciones')->where('id', $cotizacionId)->first();
        $cotizacionItems = DB::table('cotizacion_items as ci')
            ->join('componentes as c', 'ci.componente_id', '=', 'c.id')
            ->join('productos_catalogo as pc', 'c.producto_id', '=', 'pc.id')
            ->leftJoin('bodegas as b', 'c.bodega_id', '=', 'b.id')
            ->leftJoin('proveedores as p', 'b.proveedor_id', '=', 'p.id')
            ->where('ci.cotizacion_id', $cotizacionId)
            ->select(
                'ci.cantidad',
                'ci.precio_unitario',
                'pc.nombre as nombre_producto',
                'pc.categoria',
                'c.especificacion',
                'b.nombre as nombre_bodega',
                'p.nombre as nombre_proveedor'
            )
            ->get();

        // 4. Generar el PDF
        $pdf = Pdf::loadView('pdf.cotizacion', [
            'cotizacion' => $cotizacion,
            'items' => $cotizacionItems,
            'user' => $user
        ]);
        
        $pdfContent = $pdf->output();

        // 5. Enviar el correo
        try {
            Mail::to($user->correo)->send(new CotizacionEnviadaMail($cotizacion, $pdfContent, $user));
        } catch (\Exception $e) {
            \Log::error('Error enviando cotizacion: ' . $e->getMessage());
        }

        AuditLog::log($request, "Creó una nueva cotización (ID: {$cotizacionId}, Código: {$codigoUnico}) por {$total}", 'Cotizaciones');

        return response()->json([
            'message' => 'Cotización guardada y enviada a tu correo.', 
            'id' => $cotizacionId,
            'codigo' => $codigoUnico
        ], 201);
    }

    
    /**

    
     * Elimina un registro de la base de datos.

    
     * Dependiendo de la lógica, puede ser una eliminación física o lógica (soft delete).

    
     */

    
    public function destroy(Request $request, $id = null)
    {
        $user = $request->user();
        $rol = $this->getRole($request);

        if (!in_array($rol, ['cliente', 'admin', 'superadmin'])) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if ($rol === 'admin' && !$user->hasPermission('cotizaciones.eliminar')) {
            return response()->json(['success' => false, 'message' => 'No autorizado. Se requiere el permiso: cotizaciones.eliminar'], 403);
        }

        $id = $id ?? $request->query('id');
        if (!$id) {
            return response()->json(['success' => false, 'message' => 'id es requerido'], 400);
        }

        $cotizacion = DB::table('cotizaciones')->where('id', $id)->first();
        if (!$cotizacion) {
            return response()->json(['success' => false, 'message' => 'Cotización no encontrada'], 404);
        }

        // Si es cliente, solo puede borrar su propia cotización
        if ($rol === 'cliente' && $cotizacion->usuario_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'No tienes permiso para eliminar esta cotización'], 403);
        }

        // Eliminar en cascada
        DB::transaction(function () use ($id) {
            DB::table('cotizacion_items')->where('cotizacion_id', $id)->delete();
            DB::table('cotizaciones')->where('id', $id)->delete();
        });

        AuditLog::log($request, "Eliminó la cotización (ID: {$id})", 'Cotizaciones');

        return response()->json(['message' => 'Cotización eliminada']);
    }

    private function getRole(Request $request): ?string
    {
        $user = $request->user();
        if (!$user) return null;
        if (isset($user->rol)) return $user->rol;
        $class = get_class($user);
        if ($class === \App\Models\Bodega::class) return 'bodega';
        if ($class === \App\Models\Proveedor::class) return 'proveedor';
        return null;
    }
}
