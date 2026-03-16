<?php
require_once __DIR__ . '/../config/jwt.php';

function requireAuth($roles = []) {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!str_starts_with($auth, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['error' => 'Token requerido']);
        exit();
    }

    $token   = substr($auth, 7);
    $payload = jwt_verify($token);

    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token inválido o expirado']);
        exit();
    }

    if (!empty($roles) && !in_array($payload['rol'], $roles)) {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado']);
        exit();
    }

    return $payload;
}
