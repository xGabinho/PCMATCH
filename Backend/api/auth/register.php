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

// Validar nombre (mínimo 2 caracteres, solo letras y espacios)
// Validar nombre
if (!$nombre || strlen($nombre) < 2) {
    error('El nombre debe tener al menos 2 caracteres');
}
if (str_contains($nombre, ' ')) {
    error('El nombre no puede contener espacios');
}
if (!preg_match('/^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ]+$/u', $nombre)) {
    error('El nombre solo puede contener letras');
}

// Validar apellido
if (!$apellido || strlen($apellido) < 2) {
    error('El apellido debe tener al menos 2 caracteres');
}
if (str_contains($apellido, ' ')) {
    error('El apellido no puede contener espacios');
}
if (!preg_match('/^[a-záéíóúñüA-ZÁÉÍÓÚÑÜ]+$/u', $apellido)) {
    error('El apellido solo puede contener letras');
}

// Validar correo con dominio real (debe tener dominio y extensión)
if (!$correo) {
    error('El correo es requerido');
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    error('El correo no es válido');
}
// Verificar que el dominio tenga extensión real (ej: .com, .co, .net)
$dominioPartes = explode('@', $correo);
$dominio = $dominioPartes[1] ?? '';
if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $dominio)) {
    error('El correo debe tener un dominio válido (ej: gmail.com)');
}

// Validar teléfono colombiano: +57 seguido de 10 dígitos que empiecen en 3
if (!$telefono) {
    error('El teléfono es requerido');
}
$telefonoLimpio = preg_replace('/\s+/', '', $telefono);
if (!preg_match('/^\+573[0-9]{9}$/', $telefonoLimpio)) {
    error('El teléfono debe ser un número colombiano válido (+57 3XX XXX XXXX)');
}

// Validar contraseña
if (!$password || strlen($password) < 8) {
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
$stmt->bind_param('sssss', $nombre, $apellido, $correo, $telefonoLimpio, $hash);

if (!$stmt->execute()) {
    error('Error al registrar el usuario', 500);
}

$nuevoId = $stmt->insert_id;
$stmt->close();

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
        'telefono' => $telefonoLimpio,
        'rol'      => 'cliente',
    ],
], 201);