<?php
require_once 'config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['student_id'])) {
        $sid  = intval($_GET['student_id']);
        $stmt = $db->prepare(
            'SELECT s.*, u.name AS student_name, t.name AS tutor_name FROM sessions s
             JOIN users u ON s.student_id = u.id
             LEFT JOIN users t ON s.tutor_id = t.id
             WHERE s.student_id=? ORDER BY s.session_date DESC, s.session_time DESC'
        );
        $stmt->bind_param('i', $sid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } elseif (isset($_GET['tutor_id'])) {
        $tid  = intval($_GET['tutor_id']);
        $stmt = $db->prepare(
            'SELECT s.*, u.name AS student_name, t.name AS tutor_name FROM sessions s
             JOIN users u ON s.student_id = u.id
             LEFT JOIN users t ON s.tutor_id = t.id
             WHERE s.tutor_id=? ORDER BY s.session_date DESC, s.session_time DESC'
        );
        $stmt->bind_param('i', $tid);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $result = $db->query(
            'SELECT s.*, u.name AS student_name, t.name AS tutor_name FROM sessions s
             JOIN users u ON s.student_id = u.id
             LEFT JOIN users t ON s.tutor_id = t.id
             ORDER BY s.session_date DESC, s.session_time DESC'
        );
        $rows = $result->fetch_all(MYSQLI_ASSOC);
    }
    $db->close();
    echo json_encode(['success' => true, 'sessions' => $rows]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'book';

    if ($action === 'book') {
        $student_id       = intval($data['student_id']       ?? 0);
        $tutor_id         = intval($data['tutor_id']         ?? 0);
        $tutor_name       = trim($data['tutor_name']         ?? '');
        $subject          = trim($data['subject']            ?? '');
        $session_date     = trim($data['session_date']       ?? '');
        $session_time     = trim($data['session_time']       ?? '');
        $mode             = in_array($data['mode']??'', ['online','onsite']) ? $data['mode'] : 'online';
        $online_platform  = $data['online_platform']         ?? null;
        $platform_link    = trim($data['platform_link']      ?? '');
        $spot             = trim($data['spot']               ?? '');
        $amount_paid      = intval($data['amount_paid']      ?? 5000);
        $pay_method       = in_array($data['pay_method']??'', ['mtn','orange']) ? $data['pay_method'] : 'mtn';

        // 15% platform fee, 85% tutor earnings
        $platform_fee    = (int)round($amount_paid * 0.15);
        $tutor_earnings  = $amount_paid - $platform_fee;

        if (!$student_id || !$tutor_id || !$subject || !$session_date || !$session_time) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields.']);
            $db->close(); exit();
        }

        $stmt = $db->prepare(
            'INSERT INTO sessions
             (student_id, tutor_id, tutor_name, subject, session_date, session_time,
              mode, online_platform, platform_link, spot, amount_paid, platform_fee, tutor_earnings, pay_method)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        );
        $stmt->bind_param('iissssssssiiis',
            $student_id, $tutor_id, $tutor_name, $subject,
            $session_date, $session_time, $mode,
            $online_platform, $platform_link, $spot,
            $amount_paid, $platform_fee, $tutor_earnings, $pay_method
        );
        $ok  = $stmt->execute();
        $nid = $stmt->insert_id;
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok, 'session_id' => $nid, 'platform_fee' => $platform_fee, 'tutor_earnings' => $tutor_earnings]);

    } elseif ($action === 'update_status') {
        $id      = intval($data['id']     ?? 0);
        $status  = $data['status']        ?? 'pending';
        $allowed = ['pending','confirmed','completed','cancelled'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
            $db->close(); exit();
        }
        // Optionally attach platform link when confirming
        $link = trim($data['platform_link'] ?? '');
        if ($link) {
            $stmt = $db->prepare('UPDATE sessions SET status=?, platform_link=? WHERE id=?');
            $stmt->bind_param('ssi', $status, $link, $id);
        } else {
            $stmt = $db->prepare('UPDATE sessions SET status=? WHERE id=?');
            $stmt->bind_param('si', $status, $id);
        }
        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok]);
    }
}
