<?php
define('JWT_SECRET', 'pcmatch_secret_key_2024');

function jwt_generate($payload) {
    $header        = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + (8 * 3600);
    $body           = base64url_encode(json_encode($payload));
    $signature      = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$signature";
}

function jwt_verify($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $body, $signature] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));

    if (!hash_equals($expected, $signature)) return null;

    $payload = json_decode(base64url_decode($body), true);
    if (!$payload || $payload['exp'] < time()) return null;

    return $payload;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}
