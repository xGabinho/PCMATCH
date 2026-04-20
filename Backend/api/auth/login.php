<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/jwt.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Método no permitido', 405);
}

$body = getBody();
$correo   = trim($body['correo'] ?? '');
$password = trim($body['password'] ?? '');

if (!$correo || !$password) {
    error('Correo y contraseña son requeridos');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    error('Correo inválido');
}

$db = getDB();

// 1. Buscar en usuarios (admin / cliente)
$stmt = $db->prepare('SELECT id, nombre, apellido, correo, telefono, password, rol, estado FROM usuarios WHERE correo = ?');
$stmt->bind_param('s', $correo);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($usuario && password_verify($password, $usuario['password'])) {
    if ($usuario['estado'] == 0) {
        error('Tu cuenta está desactivada. Contacta al administrador.', 403);
    }

    $token = jwt_generate([
        'id'     => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'correo' => $usuario['correo'],
        'rol'    => $usuario['rol'],
    ]);
    unset($usuario['password']);
    unset($usuario['estado']);
    response(['token' => $token, 'usuario' => $usuario]);
}

// 2. Buscar en bodegas
$stmt = $db->prepare('SELECT id, nombre, correo, telefono, password, activa FROM bodegas WHERE correo = ?');
$stmt->bind_param('s', $correo);
$stmt->execute();
$bodega = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($bodega && password_verify($password, $bodega['password'])) {
    if (!$bodega['activa']) {
        error('Esta bodega está desactivada', 403);
    }

    $token = jwt_generate([
        'id'     => $bodega['id'],
        'nombre' => $bodega['nombre'],
        'correo' => $bodega['correo'],
        'rol'    => 'bodega',
    ]);
    unset($bodega['password']);
    response(['token' => $token, 'usuario' => array_merge($bodega, ['rol' => 'bodega'])]);
}

// 3. Ninguno encontrado
error('Correo o contraseña incorrectos', 401);