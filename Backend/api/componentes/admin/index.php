<?php
require_once '../../../config/database.php';
require_once '../../../config/helpers.php';
require_once '../../../middleware/auth.php';

setCORS();
requireAuth(['admin']);

$db = getDB();

$stmt = $db->prepare('
    SELECT c.id, p.nombre, p.categoria, c.especificacion, c.gama,
           c.precio, c.stock, b.nombre AS bodega_nombre
    FROM componentes c
    JOIN productos_catalogo p ON c.producto_id = p.id
    JOIN bodegas b ON c.bodega_id = b.id
    ORDER BY p.categoria ASC, p.nombre ASC
');
$stmt->execute();
$componentes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

response(['componentes' => $componentes]);