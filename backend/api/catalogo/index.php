<?php
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/auth.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'GET')     error('Método no permitido', 405);

requireAuth(['admin', 'bodega']);

$db     = getDB();
$result = $db->query("SELECT id, nombre, categoria FROM productos_catalogo ORDER BY categoria, nombre");

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

response($productos, 200);