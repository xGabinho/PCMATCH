<?php
require_once '../../../config/database.php';
require_once '../../../config/helpers.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('Método no permitido', 405);
}

$db = getDB();

$categoria = $_GET['categoria'] ?? null;

if ($categoria) {
    $stmt = $db->prepare('
        SELECT c.id, p.nombre, p.categoria, c.especificacion,
               c.gama, c.precio, c.stock, b.nombre AS bodega
        FROM componentes c
        JOIN productos_catalogo p ON c.producto_id = p.id
        JOIN bodegas b ON c.bodega_id = b.id
        WHERE b.activa = 1 AND c.stock > 0 AND p.categoria = ?
        ORDER BY p.nombre ASC
    ');
    $stmt->bind_param('s', $categoria);
} else {
    $stmt = $db->prepare('
        SELECT c.id, p.nombre, p.categoria, c.especificacion,
               c.gama, c.precio, c.stock, b.nombre AS bodega
        FROM componentes c
        JOIN productos_catalogo p ON c.producto_id = p.id
        JOIN bodegas b ON c.bodega_id = b.id
        WHERE b.activa = 1 AND c.stock > 0
        ORDER BY p.categoria ASC, p.nombre ASC
    ');
}

$stmt->execute();
$componentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

response(['componentes' => $componentes]);