<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

setCORS();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(200); exit(); }

$payload = requireAuth(['admin']);

$db = getDB();

// ════════════════════════════════════════════════════
// GET — Listar proveedores
// ════════════════════════════════════════════════════
if ($method === 'GET') {
    $result = $db->query("
        SELECT p.id, p.nombre, p.correo, p.telefono, p.created_at,
               (SELECT COUNT(*) FROM bodegas WHERE proveedor_id = p.id) AS total_bodegas
        FROM proveedores p
        ORDER BY p.created_at DESC
    ");
    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = $row;
    }
    response($proveedores, 200);
}

// ════════════════════════════════════════════════════
// POST — Crear proveedor
// ════════════════════════════════════════════════════
if ($method === 'POST') {
    $body     = getBody();
    $nombre   = trim($body['nombre']   ?? '');
    $correo   = trim($body['correo']   ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!$nombre || !$correo || !$password)
        error('Nombre, correo y contraseña son obligatorios', 400);

    $stmt = $db->prepare("SELECT id FROM proveedores WHERE correo = ?");
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); error('Ya existe un proveedor con ese correo', 409); }
    $stmt->close();

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT INTO proveedores (nombre, correo, telefono, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nombre, $correo, $telefono, $hash);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    response(['message' => 'Proveedor creado correctamente', 'id' => $id], 201);
}

// ════════════════════════════════════════════════════
// PUT — Editar proveedor
// ════════════════════════════════════════════════════
if ($method === 'PUT') {
    $body     = getBody();
    $id       = intval($body['id']       ?? 0);
    $nombre   = trim($body['nombre']     ?? '');
    $correo   = trim($body['correo']     ?? '');
    $telefono = trim($body['telefono']   ?? '');

    if ($id <= 0 || !$nombre || !$correo)
        error('id, nombre y correo son obligatorios', 400);

    $stmt = $db->prepare("SELECT id FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) { $stmt->close(); error('Proveedor no encontrado', 404); }
    $stmt->close();

    $stmt = $db->prepare("SELECT id FROM proveedores WHERE correo = ? AND id != ?");
    $stmt->bind_param("si", $correo, $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) { $stmt->close(); error('Ya existe otro proveedor con ese correo', 409); }
    $stmt->close();

    $stmt = $db->prepare("UPDATE proveedores SET nombre = ?, correo = ?, telefono = ? WHERE id = ?");
    $stmt->bind_param("sssi", $nombre, $correo, $telefono, $id);
    $stmt->execute();
    $stmt->close();

    response(['message' => 'Proveedor actualizado correctamente'], 200);
}

// ════════════════════════════════════════════════════
// DELETE — Eliminar proveedor
// ════════════════════════════════════════════════════
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($id <= 0) error('ID de proveedor inválido', 400);

    $stmt = $db->prepare("SELECT id FROM proveedores WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) { $stmt->close(); error('Proveedor no encontrado', 404); }
    $stmt->close();

    // Verificar que no tenga bodegas activas
    $stmt = $db->prepare("SELECT COUNT(*) AS total FROM bodegas WHERE proveedor_id = ? AND activa = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result['total'] > 0) {
        error('No se puede eliminar: el proveedor tiene ' . $result['total'] . ' bodega(s) activa(s).', 409);
    }

    // Verificar si las bodegas inactivas tienen componentes (para ver si se pueden borrar)
    // Si la DB tiene restricciones de clave foránea desde componentes -> bodegas, fallará aquí.
    // Intentaremos eliminar las bodegas; si falla la DB, lo capturamos.
    try {
        $stmt = $db->prepare("DELETE FROM bodegas WHERE proveedor_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // En caso de que no haya ON DELETE CASCADE y tengan componentes
        error('El proveedor tiene bodegas inactivas que no se pueden eliminar (tienen historial). Comuníquese con soporte.', 409);
    }

    try {
        $stmt = $db->prepare("DELETE FROM proveedores WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error('No se puede eliminar el proveedor debido a registros asociados.', 409);
    }

    response(['message' => 'Proveedor eliminado correctamente'], 200);
}

error('Método no permitido', 405);
