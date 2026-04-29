<?php
// GET  /api/users.php         — list all users (admin only)
// POST /api/users.php         — delete a user  (admin only)
require_once 'config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $db->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
    $users  = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $db->close();
    echo json_encode(['success' => true, 'users' => $users]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    if ($action === 'delete') {
        $id   = intval($data['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok   = $stmt->execute();
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok]);
    } else {
        $db->close();
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
}
