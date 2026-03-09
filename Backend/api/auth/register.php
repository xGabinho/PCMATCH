<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/jwt.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error('Método no permitido', 405);
}

$body     = getBody();
$nombre   = trim($body['nombre']   ?? '');
$apellido = trim($body['apellido'] ?? '');
$correo   = trim($body['correo']   ?? '');
$telefono = trim($body['telefono'] ?? '');
$password = trim($body['password'] ?? '');

// Validaciones
if (!$nombre || !$correo || !$password) {
    error('Nombre, correo y contraseña son requeridos');
}

if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    error('Correo inválido');
}

if (strlen($password) < 8) {
    error('La contraseña debe tener al menos 8 caracteres');
}

$db = getDB();

// Verificar que el correo no esté registrado
$stmt = $db->prepare('SELECT id FROM usuarios WHERE correo = ?');
$stmt->bind_param('s', $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    error('El correo ya está registrado');
}
$stmt->close();

// Insertar usuario
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare('INSERT INTO usuarios (nombre, apellido, correo, telefono, password, rol) VALUES (?, ?, ?, ?, ?, "cliente")');
$stmt->bind_param('sssss', $nombre, $apellido, $correo, $telefono, $hash);

if (!$stmt->execute()) {
    error('Error al registrar el usuario', 500);
}

$nuevoId = $stmt->insert_id;
$stmt->close();

// Generar token directo al registrarse (inicio de sesión automático)
$token = jwt_generate([
    'id'     => $nuevoId,
    'nombre' => $nombre,
    'correo' => $correo,
    'rol'    => 'cliente',
]);

response([
    'token'   => $token,
    'usuario' => [
        'id'       => $nuevoId,
        'nombre'   => $nombre,
        'apellido' => $apellido,
        'correo'   => $correo,
        'telefono' => $telefono,
        'rol'      => 'cliente',
    ],
], 201);