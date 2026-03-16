<?php
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/helpers.php';

function requireAuth($rolesPermitidos = []) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        error('Token no proporcionado', 401);
    }

    $token = substr($authHeader, 7);
    $payload = jwt_verify($token);

    if (!$payload) {
        error('Token inválido o expirado', 401);
    }

    // Si se especifican roles, verificar que el usuario tenga uno permitido
    if (!empty($rolesPermitidos) && !in_array($payload['rol'], $rolesPermitidos)) {
        error('No tienes permiso para realizar esta acción', 403);
    }

    return $payload;  // Retorna datos del usuario: id, nombre, correo, rol
}