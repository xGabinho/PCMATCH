<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
 
setCORS();
 
$method = $_SERVER['REQUEST_METHOD'];
 
if ($method === 'OPTIONS') { http_response_code(200); exit(); }
 
// RN01 RF-16 / RN03 RF-18: Solo admin o bodega
$payload = requireAuth(['admin', 'bodega']);
 
$db = getDB();
 
// ════════════════════════════════════════════════════
// GET — Consultar componentes (RF-16)
// ════════════════════════════════════════════════════
if ($method === 'GET') {
    $where  = ['1=1'];
    $params = [];
    $types  = '';
 
    // Si es bodega, solo ve sus propios componentes
    if ($payload['rol'] === 'bodega') {
        $where[]  = 'c.bodega_id = ?';
        $params[] = $payload['id'];
        $types   .= 'i';
    }
 
    // Filtro categoría
    if (!empty($_GET['categoria'])) {
        $where[]  = 'pc.categoria = ?';
        $params[] = $_GET['categoria'];
        $types   .= 's';
    }
 
    // Filtro bodega (solo admin)
    if (!empty($_GET['bodega_id']) && $payload['rol'] === 'admin') {
        $where[]  = 'c.bodega_id = ?';
        $params[] = intval($_GET['bodega_id']);
        $types   .= 'i';
    }
 
    // Filtro proveedor (solo admin)
    if (!empty($_GET['proveedor_id']) && $payload['rol'] === 'admin') {
        $where[]  = 'b.proveedor_id = ?';
        $params[] = intval($_GET['proveedor_id']);
        $types   .= 'i';
    }
 
    // Filtro precio mínimo
    if (!empty($_GET['precio_min'])) {
        $where[]  = 'c.precio >= ?';
        $params[] = floatval($_GET['precio_min']);
        $types   .= 'd';
    }
 
    // Filtro precio máximo
    if (!empty($_GET['precio_max'])) {
        $where[]  = 'c.precio <= ?';
        $params[] = floatval($_GET['precio_max']);
        $types   .= 'd';
    }
 
    // Filtro con stock
    if (isset($_GET['con_stock']) && $_GET['con_stock'] === '1') {
        $where[] = 'c.stock > 0';
    }
 
    // Filtro activo/inactivo
    if (isset($_GET['activo']) && $_GET['activo'] !== '') {
        $where[]  = 'c.activo = ?';
        $params[] = intval($_GET['activo']);
        $types   .= 'i';
    }
 
    // Filtro búsqueda
    if (!empty($_GET['buscar'])) {
        $where[]  = '(pc.nombre LIKE ? OR c.especificacion LIKE ?)';
        $buscar   = '%' . $_GET['buscar'] . '%';
        $params[] = $buscar;
        $params[] = $buscar;
        $types   .= 'ss';
    }
 
    $whereStr = implode(' AND ', $where);
 
    $sql = "
        SELECT
            c.id,
            pc.nombre,
            pc.categoria,
            c.especificacion,
            c.gama,
            c.precio,
            c.stock,
            c.activo,
            b.nombre   AS bodega,
            p.nombre   AS proveedor,
            c.bodega_id,
            c.producto_id,
            b.proveedor_id,
            c.created_at
        FROM componentes c
        JOIN productos_catalogo pc ON pc.id = c.producto_id
        JOIN bodegas b             ON b.id  = c.bodega_id
        JOIN proveedores p         ON p.id  = b.proveedor_id
        WHERE $whereStr
        ORDER BY c.created_at DESC
    ";
 
    $stmt = $db->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result      = $stmt->get_result();
    $componentes = [];
    while ($row = $result->fetch_assoc()) {
        $row['activo'] = (bool) $row['activo'];
        $componentes[] = $row;
    }
    $stmt->close();
 
    response($componentes, 200);
}
 
// ════════════════════════════════════════════════════
// POST — Registrar componente (RF-15)
// ════════════════════════════════════════════════════
if ($method === 'POST') {
    $body           = getBody();
    $bodega_id      = intval($body['bodega_id']    ?? 0);
    $producto_id    = intval($body['producto_id']  ?? 0);
    $especificacion = trim($body['especificacion'] ?? '');
    $gama           = trim($body['gama']           ?? 'media');
    $precio         = floatval($body['precio']     ?? 0);
    $stock          = intval($body['stock']        ?? 0);
 
    // Si es bodega, registra en su propia bodega
    if ($payload['rol'] === 'bodega') {
        $bodega_id = $payload['id'];
    }
 
    // RN01 — Campos obligatorios
    if (!$bodega_id || !$producto_id || !$especificacion || $precio <= 0)
        error('Bodega, producto, especificación y precio son obligatorios', 400);
 
    if (!in_array($gama, ['alta', 'media', 'baja']))
        error('Gama inválida. Debe ser alta, media o baja', 400);
 
    // RN03 — Bodega activa y proveedor activo
    $stmt = $db->prepare("
        SELECT b.id FROM bodegas b
        JOIN proveedores p ON p.id = b.proveedor_id
        WHERE b.id = ? AND b.activa = 1 AND p.activo = 1
    ");
    $stmt->bind_param("i", $bodega_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        error('La bodega no existe, está inactiva, o su proveedor está inactivo', 400);
    }
    $stmt->close();
 
    // Verificar que el producto existe
    $stmt = $db->prepare("SELECT id FROM productos_catalogo WHERE id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) { $stmt->close(); error('El producto no existe', 400); }
    $stmt->close();
 
    // RN02 — Unicidad: mismo producto + bodega + especificación
    $stmt = $db->prepare("
        SELECT id FROM componentes
        WHERE bodega_id = ? AND producto_id = ? AND especificacion = ?
    ");
    $stmt->bind_param("iis", $bodega_id, $producto_id, $especificacion);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        error('Ya existe un componente con la misma especificación en esta bodega', 409);
    }
    $stmt->close();
 
    $stmt = $db->prepare("
        INSERT INTO componentes (bodega_id, producto_id, especificacion, gama, precio, stock, activo)
        VALUES (?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->bind_param("iissdi", $bodega_id, $producto_id, $especificacion, $gama, $precio, $stock);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
 
    response(['message' => 'Componente registrado correctamente', 'id' => $id], 201);
}
 
// ════════════════════════════════════════════════════
// DELETE — Eliminar componente (RF-18)
// ════════════════════════════════════════════════════
if ($method === 'DELETE') {
    // RN03: solo admin
    if ($payload['rol'] !== 'admin')
        error('Solo el administrador puede eliminar componentes', 403);
 
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) error('ID inválido', 400);
 
    $stmt = $db->prepare("
        SELECT c.id, pc.nombre, c.stock, c.activo
        FROM componentes c
        JOIN productos_catalogo pc ON pc.id = c.producto_id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $comp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    if (!$comp) error('Componente no encontrado', 404);
    if (!$comp['activo']) error('El componente ya está inactivo', 409);
 
    // RN02 — No eliminar si tiene stock
    if ($comp['stock'] > 0)
        error('No se puede eliminar: tiene ' . $comp['stock'] . ' unidad(es) en inventario. Primero reduce el stock a 0.', 409);
 
    // RN01 — Eliminación lógica
    $stmt = $db->prepare("UPDATE componentes SET activo = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
 
    response(['message' => 'Componente "' . $comp['nombre'] . '" eliminado correctamente.', 'id' => $id, 'activo' => false], 200);
}
 
// ════════════════════════════════════════════════════
// PATCH — Reactivar componente
// ════════════════════════════════════════════════════
if ($method === 'PATCH') {
    if ($payload['rol'] !== 'admin')
        error('Solo el administrador puede reactivar componentes', 403);
 
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) error('ID inválido', 400);
 
    $stmt = $db->prepare("
        SELECT c.id, pc.nombre, c.activo
        FROM componentes c
        JOIN productos_catalogo pc ON pc.id = c.producto_id
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $comp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
 
    if (!$comp) error('Componente no encontrado', 404);
    if ($comp['activo']) error('El componente ya está activo', 409);
 
    $stmt = $db->prepare("UPDATE componentes SET activo = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
 
    response(['message' => 'Componente "' . $comp['nombre'] . '" reactivado correctamente.', 'id' => $id, 'activo' => true], 200);
}
 
error('Método no permitido', 405);