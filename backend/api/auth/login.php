<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/jwt.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')     error('Método no permitido', 405);

$body     = getBody();
$correo   = trim($body['correo']   ?? '');
$password = trim($body['password'] ?? '');

if (!$correo || !$password) error('Correo y contraseña son requeridos', 400);

$db = getDB();

// Buscar en tabla usuarios
$stmt = $db->prepare("SELECT id, nombre, correo, password, rol FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['password'])) {
    $token = jwt_generate([
        'id'     => $user['id'],
        'nombre' => $user['nombre'],
        'correo' => $user['correo'],
        'rol'    => $user['rol'],
    ]);
    response([
        'token'   => $token,
        'usuario' => [
            'id'     => $user['id'],
            'nombre' => $user['nombre'],
            'correo' => $user['correo'],
            'rol'    => $user['rol'],
        ]
    ]);
}

// Buscar en tabla bodegas
$stmt = $db->prepare("SELECT id, nombre, correo, password FROM bodegas WHERE correo = ? AND activa = 1");
$stmt->bind_param("s", $correo);
$stmt->execute();
$bodega = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($bodega && password_verify($password, $bodega['password'])) {
    $token = jwt_generate([
        'id'     => $bodega['id'],
        'nombre' => $bodega['nombre'],
        'correo' => $bodega['correo'],
        'rol'    => 'bodega',
    ]);
    response([
        'token'   => $token,
        'usuario' => [
            'id'     => $bodega['id'],
            'nombre' => $bodega['nombre'],
            'correo' => $bodega['correo'],
            'rol'    => 'bodega',
        ]
    ]);
}

error('Correo o contraseña incorrectos', 401);