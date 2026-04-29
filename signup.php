<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required']);
    exit();
}

$data     = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data.']);
    exit();
}

$name     = trim($data['name']     ?? '');
$email    = trim($data['email']    ?? '');
$password = trim($data['password'] ?? '');
$role     = trim($data['role']     ?? 'student');

// Validate
if (!$name || !$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
    exit();
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit();
}
if (!in_array($role, ['student', 'tutor'])) {
    $role = 'student';
}

$db = getDB();

// Check duplicate email
$stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    exit();
}
$stmt->close();

// Insert new user with hashed password
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $db->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $name, $email, $hash, $role);

if ($stmt->execute()) {
    $newId = $stmt->insert_id;
    $stmt->close();

    // Auto-create a basic tutor profile so they're visible immediately
    if ($role === 'tutor') {
        $stmt2 = $db->prepare(
            'INSERT IGNORE INTO tutor_profiles (user_id, qualification, subjects, hourly_rate, is_approved)
             VALUES (?, "Bachelor", "General", 5000, 0)'
        );
        $stmt2->bind_param('i', $newId);
        $stmt2->execute();
        $stmt2->close();
    }

    $db->close();
    echo json_encode([
        'success' => true,
        'user'    => ['id' => $newId, 'name' => $name, 'email' => $email, 'role' => $role]
    ]);
} else {
    $stmt->close();
    $db->close();
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}
