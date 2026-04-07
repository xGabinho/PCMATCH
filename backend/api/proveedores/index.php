<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

setCORS();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(200); exit(); }

// Proveedores o bodegas autenticados
$payload = requireAuth(['proveedor', 'bodega']);

$db = getDB();

// ════════════════════════════════════════════════════
// GET — Listar bodegas del proveedor/bodega autenticado
// ════════════════════════════════════════════════════
if ($method === 'GET') {
    $id  = $payload['id'];
    $rol = $payload['rol'];

    if ($rol === 'bodega') {
        // Una bodega ve su propia info
        $stmt = $db->prepare("
            SELECT id, nombre, correo, telefono, activa, created_at
            FROM bodegas
            WHERE id = ?
        ");
        $stmt->bind_param("i", $id);
    } else {
        // Un proveedor ve sus bodegas asignadas
        $stmt = $db->prepare("
            SELECT id, nombre, correo, telefono, activa, created_at
            FROM bodegas
            WHERE proveedor_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->bind_param("i", $id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $bodegas = [];
    while ($row = $result->fetch_assoc()) {
        $row['activa'] = (bool) $row['activa'];
        $bodegas[] = $row;
    }
    $stmt->close();

    response($bodegas, 200);
}

// ════════════════════════════════════════════════════
// POST — Crear bodega (vinculada al proveedor/bodega autenticado)
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
    $creadorId = $payload['id'];

    $stmt = $db->prepare("INSERT INTO bodegas (nombre, correo, telefono, password, activa, proveedor_id) VALUES (?, ?, ?, ?, 1, ?)");
    $stmt->bind_param("ssssi", $nombre, $correo, $telefono, $hash, $creadorId);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();

    response(['message' => 'Bodega creada correctamente', 'id' => $id], 201);
}

error('Método no permitido', 405);
