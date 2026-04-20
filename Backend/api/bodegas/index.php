<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
require_once '../../middleware/auth.php';

setCORS();

$user = requireAuth(['admin']);
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// GET — listar bodegas
if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT b.id, b.nombre, b.telefono, b.correo, b.activa,
               COUNT(c.id) as total_componentes
        FROM bodegas b
        LEFT JOIN componentes c ON c.bodega_id = b.id
        GROUP BY b.id
        ORDER BY b.nombre ASC
    ');
    $stmt->execute();
    $bodegas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    response(['bodegas' => $bodegas]);
}

// POST — crear bodega
if ($method === 'POST') {
    $body     = getBody();
    $nombre   = trim($body['nombre']   ?? '');
    $correo   = trim($body['correo']   ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $password = trim($body['password'] ?? '');

    if (!$nombre || !$correo || !$password) error('Nombre, correo y contraseña son requeridos');
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) error('Correo inválido');
    if (strlen($password) < 8) error('La contraseña debe tener al menos 8 caracteres');

    // Verificar correo duplicado
    $check = $db->prepare('SELECT id FROM bodegas WHERE correo = ?');
    $check->bind_param('s', $correo);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) error('El correo ya está registrado');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO bodegas (nombre, telefono, correo, password, activa) VALUES (?, ?, ?, ?, 1)');
    $stmt->bind_param('ssss', $nombre, $telefono, $correo, $hash);
    if (!$stmt->execute()) error('Error al crear la bodega', 500);

    response(['message' => 'Bodega creada', 'id' => $db->insert_id], 201);
}

// PUT — editar bodega
if ($method === 'PUT') {
    $body     = getBody();
    $id       = $body['id']      ?? null;
    $nombre   = trim($body['nombre']   ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $activa   = isset($body['activa']) ? (int)$body['activa'] : null;

    if (!$id) error('id es requerido');

    $stmt = $db->prepare('UPDATE bodegas SET nombre = ?, telefono = ?, activa = ? WHERE id = ?');
    $stmt->bind_param('ssii', $nombre, $telefono, $activa, $id);
    $stmt->execute();

    response(['message' => 'Bodega actualizada']);
}

// DELETE — eliminar bodega
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) error('id es requerido');

    $stmt = $db->prepare('DELETE FROM bodegas WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    response(['message' => 'Bodega eliminada']);
}

error('Método no permitido', 405);