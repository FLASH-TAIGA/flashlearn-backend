<?php
// Flash Learning — Database Configuration
error_reporting(0);
ini_set('display_errors', 0);

// ── CORS — must be first, before any output ────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ── Database credentials ───────────────────────────────────
// Automatically uses Railway env vars when deployed,
// falls back to XAMPP localhost for local development.

$db_host = getenv('MYSQLHOST')     ?: 'localhost';
$db_user = getenv('MYSQLUSER')     ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_name = getenv('MYSQLDATABASE') ?: 'flashlearn';
$db_port = (int)(getenv('MYSQLPORT') ?: 3306);

define('DB_HOST', $db_host);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_NAME', $db_name);
define('DB_PORT', $db_port);

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
