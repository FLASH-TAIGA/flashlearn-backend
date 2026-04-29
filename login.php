<?php
// 1. Allow the specific origin (your Netlify site)
header("Access-Control-Allow-Origin: https://flash-learning.netlify.app");

// 2. Allow the browser to send specific methods
header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");

// 3. Allow the specific headers your frontend is sending (like Content-Type)
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 4. Handle the "Preflight" OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // If the browser is just "asking" for permission, tell it YES and stop here.
    header("HTTP/1.1 200 OK");
    exit();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

$data     = json_decode(file_get_contents('php://input'), true);
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit();
}

// Check hardcoded admin
if ($email === ADMIN_EMAIL && $password === ADMIN_PASSWORD) {
    echo json_encode([
        'success' => true,
        'user'    => ['id' => 0, 'name' => 'Administrator', 'email' => $email, 'role' => 'admin']
    ]);
    exit();
}

// Check database users
$db   = getDB();
$stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
$user   = $result->fetch_assoc();
$stmt->close();
$db->close();

if ($user && password_verify($password, $user['password'])) {
    unset($user['password']); // never send hash to client
    echo json_encode(['success' => true, 'user' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}
