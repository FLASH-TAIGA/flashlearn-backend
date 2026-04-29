<?php
// GET  /api/messages.php?sender_id=X&receiver_id=Y  — get conversation
// GET  /api/messages.php?user_id=X                  — get all conversations for user
// POST /api/messages.php                             — send message
// POST action=mark_read                              — mark messages as read
require_once 'config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['sender_id']) && isset($_GET['receiver_id'])) {
        // Full conversation between two users
        $a    = intval($_GET['sender_id']);
        $b    = intval($_GET['receiver_id']);
        $stmt = $db->prepare(
            'SELECT m.*, u.name AS sender_name FROM messages m
             JOIN users u ON m.sender_id = u.id
             WHERE (m.sender_id=? AND m.receiver_id=?)
                OR (m.sender_id=? AND m.receiver_id=?)
             ORDER BY m.created_at ASC'
        );
        $stmt->bind_param('iiii', $a, $b, $b, $a);
        $stmt->execute();
        $msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Mark as read
        $stmt2 = $db->prepare(
            'UPDATE messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0'
        );
        $stmt2->bind_param('ii', $b, $a);
        $stmt2->execute();
        $stmt2->close();

        $db->close();
        echo json_encode(['success' => true, 'messages' => $msgs]);

    } elseif (isset($_GET['user_id'])) {
        // All unique conversations for this user (latest message per contact)
        $uid  = intval($_GET['user_id']);
        $stmt = $db->prepare(
            'SELECT m.*, u.name AS other_name, u.role AS other_role,
                    (SELECT COUNT(*) FROM messages m2
                     WHERE m2.sender_id = m.sender_id AND m2.receiver_id = ? AND m2.is_read = 0) AS unread
             FROM messages m
             JOIN users u ON u.id = CASE WHEN m.sender_id=? THEN m.receiver_id ELSE m.sender_id END
             WHERE m.sender_id=? OR m.receiver_id=?
             GROUP BY CASE WHEN m.sender_id=? THEN m.receiver_id ELSE m.sender_id END
             ORDER BY m.created_at DESC'
        );
        $stmt->bind_param('iiiii', $uid, $uid, $uid, $uid, $uid);
        $stmt->execute();
        $convos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db->close();
        echo json_encode(['success' => true, 'conversations' => $convos]);

    } elseif (isset($_GET['all']) && $_GET['all'] === '1') {
        // Admin: all messages
        $result = $db->query(
            'SELECT m.*, s.name AS sender_name, r.name AS receiver_name
             FROM messages m
             JOIN users s ON m.sender_id   = s.id
             JOIN users r ON m.receiver_id = r.id
             ORDER BY m.created_at DESC LIMIT 200'
        );
        $msgs = $result->fetch_all(MYSQLI_ASSOC);
        $db->close();
        echo json_encode(['success' => true, 'messages' => $msgs]);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data       = json_decode(file_get_contents('php://input'), true);
    $action     = $data['action'] ?? 'send';

    if ($action === 'send') {
        $sender_id   = intval($data['sender_id']   ?? 0);
        $receiver_id = intval($data['receiver_id'] ?? 0);
        $message     = trim($data['message']       ?? '');

        if (!$sender_id || !$receiver_id || !$message) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            $db->close(); exit();
        }

        $stmt = $db->prepare(
            'INSERT INTO messages (sender_id, receiver_id, message) VALUES (?,?,?)'
        );
        $stmt->bind_param('iis', $sender_id, $receiver_id, $message);
        $ok = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok, 'id' => $id]);

    } elseif ($action === 'delete') {
        // Admin delete message
        $id   = intval($data['id'] ?? 0);
        $stmt = $db->prepare('DELETE FROM messages WHERE id=?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok]);
    }
}
