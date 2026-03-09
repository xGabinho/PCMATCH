<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';
require_once '../../middleware/auth.php';

setCORS();

$user = requireAuth(['admin']);
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

// GET — listar usuarios
if ($method === 'GET') {
    $stmt = $db->prepare('
        SELECT id, nombre, apellido, correo, telefono, rol, created_at
        FROM usuarios
        ORDER BY created_at DESC
    ');
    $stmt->execute();
    $usuarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    response(['usuarios' => $usuarios]);
}

// POST — crear usuario
if ($method === 'POST') {
    $body     = getBody();
    $nombre   = trim($body['nombre']   ?? '');
    $apellido = trim($body['apellido'] ?? '');
    $correo   = trim($body['correo']   ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $password = trim($body['password'] ?? '');
    $rol      = trim($body['rol']      ?? 'cliente');

    if (!$nombre || !$correo || !$password) error('Nombre, correo y contraseña son requeridos');
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) error('Correo inválido');
    if (strlen($password) < 8) error('La contraseña debe tener al menos 8 caracteres');
    if (!in_array($rol, ['admin', 'cliente'])) error('Rol inválido');

    $check = $db->prepare('SELECT id FROM usuarios WHERE correo = ?');
    $check->bind_param('s', $correo);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) error('El correo ya está registrado');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO usuarios (nombre, apellido, correo, telefono, password, rol) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ssssss', $nombre, $apellido, $correo, $telefono, $hash, $rol);
    if (!$stmt->execute()) error('Error al crear el usuario', 500);

    response(['message' => 'Usuario creado', 'id' => $db->insert_id], 201);
}

// PUT — editar usuario
if ($method === 'PUT') {
    $body     = getBody();
    $id       = $body['id']       ?? null;
    $nombre   = trim($body['nombre']   ?? '');
    $apellido = trim($body['apellido'] ?? '');
    $telefono = trim($body['telefono'] ?? '');
    $correo   = trim($body['correo']   ?? '');
    $rol      = trim($body['rol']      ?? '');

    if (!$id) error('id es requerido');
    if (!in_array($rol, ['admin', 'cliente'])) error('Rol inválido');

    $stmt = $db->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, correo = ?, telefono = ?, rol = ? WHERE id = ?');
    $stmt->bind_param('sssssi', $nombre, $apellido, $correo, $telefono, $rol, $id);
    $stmt->execute();

    response(['message' => 'Usuario actualizado']);
}

// DELETE — eliminar usuario
if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) error('id es requerido');

    $stmt = $db->prepare('DELETE FROM usuarios WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();

    response(['message' => 'Usuario eliminado']);
}

error('Método no permitido', 405);