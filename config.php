<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

define('DB_HOST', 'tramway.proxy.rlwy.net');
define('DB_USER', 'root');
define('DB_PASS', 'OEiWzABkKVCoefbpSbJKxVixMdANqKAT');
define('DB_NAME', 'railway');
define('DB_PORT', 18495);

define('ADMIN_EMAIL',    'admin@flashlearn.edu');
define('ADMIN_PASSWORD', 'Admin@FL2026');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'DB error: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}