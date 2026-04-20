<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/helpers.php';
require_once __DIR__ . '/../../middleware/auth.php';

setCORS();

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// GET — listar cotizaciones
if ($method === 'GET') {
    $user = requireAuth(['cliente', 'admin']);

    if ($user['rol'] === 'admin') {
        $stmt = $db->prepare('
            SELECT c.id, c.perfil, c.total, c.created_at,
                   u.nombre, u.apellido, u.correo,
                   COUNT(ci.id) AS total_items
            FROM cotizaciones c
            JOIN usuarios u ON c.usuario_id = u.id
            LEFT JOIN cotizacion_items ci ON ci.cotizacion_id = c.id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ');
        $stmt->execute();
    } else {
        $stmt = $db->prepare('
            SELECT c.id, c.perfil, c.total, c.created_at,
                   COUNT(ci.id) AS total_items
            FROM cotizaciones c
            LEFT JOIN cotizacion_items ci ON ci.cotizacion_id = c.id
            WHERE c.usuario_id = ?
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ');
        $stmt->bind_param('i', $user['id']);
        $stmt->execute();
    }

    $cotizaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    response(['cotizaciones' => $cotizaciones]);
}

// POST — crear cotización
if ($method === 'POST') {
    $user   = requireAuth(['cliente']);
    $body   = getBody();
    $items  = $body['items']  ?? [];
    $total  = $body['total']  ?? 0;
    $perfil = trim($body['perfil'] ?? '');

    if (empty($items)) error('Debes seleccionar al menos un componente');

    // Insertar cotización
    $stmt = $db->prepare('INSERT INTO cotizaciones (usuario_id, perfil, total) VALUES (?, ?, ?)');
    $stmt->bind_param('isd', $user['id'], $perfil, $total);
    if (!$stmt->execute()) error('Error al crear la cotización', 500);

    $cotizacionId = $db->insert_id;

    // Insertar items
    $stmtItem = $db->prepare('
        INSERT INTO cotizacion_items (cotizacion_id, componente_id, cantidad, precio_unitario)
        VALUES (?, ?, ?, ?)
    ');
    foreach ($items as $item) {
        $compId   = $item['componente_id'];
        $cantidad = $item['cantidad'] ?? 1;
        $precio   = $item['precio'];
        $stmtItem->bind_param('iiid', $cotizacionId, $compId, $cantidad, $precio);
        $stmtItem->execute();
    }

    response(['message' => 'Cotización guardada', 'id' => $cotizacionId], 201);
}

// DELETE — eliminar cotización
if ($method === 'DELETE') {
    $user = requireAuth(['cliente', 'admin']);
    $id   = $_GET['id'] ?? null;
    if (!$id) error('id es requerido');

    // Verificar pertenencia si es cliente
    if ($user['rol'] === 'cliente') {
        $check = $db->prepare('SELECT id FROM cotizaciones WHERE id = ? AND usuario_id = ?');
        $check->bind_param('ii', $id, $user['id']);
        $check->execute();
        if (!$check->get_result()->fetch_assoc()) error('No tienes permiso para eliminar esta cotización', 403);
    }

    $stmtItems = $db->prepare('DELETE FROM cotizacion_items WHERE cotizacion_id = ?');
    $stmtItems->bind_param('i', $id);
    $stmtItems->execute();

    $stmtCot = $db->prepare('DELETE FROM cotizaciones WHERE id = ?');
    $stmtCot->bind_param('i', $id);
    $stmtCot->execute();

    response(['message' => 'Cotización eliminada']);
}

error('Método no permitido', 405);