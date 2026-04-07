<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';
 
setCORS();
 
$method = $_SERVER['REQUEST_METHOD'];
 
if ($method === 'OPTIONS') { http_response_code(200); exit(); }
 
// Todos los métodos requieren ser admin (RN03)
$payload = requireAuth(['admin', 'bodega']);
 
$db = getDB();
if ($method === 'GET') {
    $result  = $db->query("
        SELECT b.id, b.nombre, b.correo, b.telefono, b.activa, b.created_at, b.proveedor_id,
               p.nombre AS proveedor_nombre
        FROM bodegas b
        LEFT JOIN proveedores p ON b.proveedor_id = p.id
        ORDER BY b.created_at DESC
    ");
    $bodegas = [];
    while ($row = $result->fetch_assoc()) {
        $row['activa'] = (bool) $row['activa'];
        $bodegas[]     = $row;
    }
    response($bodegas, 200);
}

// ════════════════════════════════════════════════════
// POST — Crear bodega
// ════════════════════════════════════════════════════
if ($method === 'POST') {
    $body     = getBody();
    $nombre   = trim($body['nombre']   ?? '');
    $correo   = trim($body['correo']   ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!$nombre || !$correo || !$password)
        error('Nombre, correo y contraseña son obligatorios', 400);

    $stmt = $db->prepare("SELECT id FROM bodegas WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); error('Ya existe una bodega con ese correo', 409); }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO bodegas (nombre, correo, telefono, password, activa) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $nombre, $correo, $telefono, $hash);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    response(['message' => 'Bodega creada correctamente', 'id' => $id], 201);
}

// ════════════════════════════════════════════════════
// PUT — Editar bodega
// ════════════════════════════════════════════════════
if ($method === 'PUT') {
    $body     = getBody();
    $id       = intval($body['id']       ?? 0);
    $nombre   = trim($body['nombre']     ?? '');
    $correo   = trim($body['correo']     ?? '');
    $telefono = trim($body['telefono']   ?? '');

    if ($id <= 0 || !$nombre || !$correo)
        error('id, nombre y correo son obligatorios', 400);

    $stmt = $db->prepare("SELECT id FROM bodegas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) { $stmt->close(); error('Bodega no encontrada', 404); }
    $stmt->close();

    $stmt = $db->prepare("SELECT id FROM bodegas WHERE correo = ? AND id != ?");
    $stmt->bind_param("si", $correo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); error('Ya existe otra bodega con ese correo', 409); }
    $stmt->close();

    $stmt = $db->prepare("UPDATE bodegas SET nombre = ?, correo = ?, telefono = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nombre, $correo, $telefono, $id);
    $stmt->execute();
    $stmt->close();

    response(['message' => 'Bodega actualizada correctamente'], 200);
}

// ════════════════════════════════════════════════════
// DELETE — Desactivar bodega (RF-12)
// RN01: eliminación lógica
// RN02: bloquear si tiene stock
// RN03: solo admin (ya validado arriba)
// ════════════════════════════════════════════════════
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) error('ID de bodega inválido', 400);

    $stmt = $db->prepare("SELECT id, nombre, activa FROM bodegas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $bodega = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bodega) error('Bodega no encontrada', 404);

    if (!$bodega['activa']) error('La bodega ya se encuentra inactiva', 409);

    // RN02 — Verificar que no tenga stock activo
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM componentes WHERE bodega_id = ? AND stock > 0");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stock = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($stock['total'] > 0) {
        error(
            'No se puede desactivar: la bodega tiene ' . $stock['total'] .
            ' componente(s) con inventario disponible. Primero traslada o cierra el inventario.',
            409
        );
    }

    // RN01 — Eliminación lógica
    $stmt = $db->prepare("UPDATE bodegas SET activa = 0 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    response([
        'message' => 'Bodega "' . $bodega['nombre'] . '" desactivada correctamente.',
        'id'      => $id,
        'activa'  => false,
    ], 200);
}

// ════════════════════════════════════════════════════
// PATCH — Activar bodega (reactivar)
// ════════════════════════════════════════════════════
if ($method === 'PATCH') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($id <= 0) error('ID de bodega inválido', 400);

    $stmt = $db->prepare("SELECT id, nombre, activa FROM bodegas WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $bodega = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bodega) error('Bodega no encontrada', 404);

    if ($bodega['activa']) error('La bodega ya está activa', 409);

    $stmt = $db->prepare("UPDATE bodegas SET activa = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    response([
        'message' => 'Bodega "' . $bodega['nombre'] . '" activada correctamente.',
        'id'      => $id,
        'activa'  => true,
    ], 200);
}

error('Método no permitido', 405);