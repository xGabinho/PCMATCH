<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../middleware/auth.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('Método no permitido', 405);
}

$payload = requireAuth();

$db = getDB();
$stmt = $db->prepare('SELECT id, nombre, apellido, correo, telefono, rol, created_at FROM usuarios WHERE id = ?');
$stmt->bind_param('i', $payload['id']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    error('Usuario no encontrado', 404);
}

response($usuario);