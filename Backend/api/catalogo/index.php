<?php
require_once '../../config/database.php';
require_once '../../config/helpers.php';

setCORS();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error('Método no permitido', 405);
}

$db = getDB();

$categoria = $_GET['categoria'] ?? null;

if ($categoria) {
    $stmt = $db->prepare('SELECT id, nombre, categoria FROM productos_catalogo WHERE categoria = ? ORDER BY nombre ASC');
    $stmt->bind_param('s', $categoria);
} else {
    $stmt = $db->prepare('SELECT id, nombre, categoria FROM productos_catalogo ORDER BY categoria ASC, nombre ASC');
}

$stmt->execute();
$result = $stmt->get_result();
$productos = $result->fetch_all(MYSQLI_ASSOC);

response(['productos' => $productos]);