<?php
function getDB() {
    $conn = new mysqli('localhost', 'root', '', 'pcmatch');
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit();
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
