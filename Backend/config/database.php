<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'pcmatch');
define('DB_USER', 'root');
define('DB_PASS', '');       // En XAMPP por defecto está vacío

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Error de conexión a la base de datos']);
        exit;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}