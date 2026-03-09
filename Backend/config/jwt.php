<?php
define('JWT_SECRET', 'pcmatch_secret_key_2026');  // Cambia esto por algo seguro
define('JWT_EXPIRY', 60 * 60 * 8);                // 8 horas

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
}

function jwt_generate($payload) {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_EXPIRY;
    $payload = base64url_encode(json_encode($payload));

    $signature = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));

    return "$header.$payload.$signature";
}

function jwt_verify($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;

    [$header, $payload, $signature] = $parts;

    // Verificar firma
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $signature)) return false;

    // Decodificar payload
    $data = json_decode(base64url_decode($payload), true);

    // Verificar expiración
    if (!$data || $data['exp'] < time()) return false;

    return $data;
}