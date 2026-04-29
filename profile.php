<?php
// GET  /api/profile.php?user_id=X   — get tutor profile
// POST /api/profile.php              — save/update tutor profile (multipart for photo)
require_once 'config.php';

$db = getDB();

// Qualification → hourly rate mapping (XAF)
$RATES = [
    'Certificate' => 3000,
    'Diploma'     => 4000,
    'Bachelor'    => 5000,
    'Master'      => 7000,
    'PhD'         => 10000,
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid  = intval($_GET['user_id'] ?? 0);
    $stmt = $db->prepare(
        'SELECT tp.*, u.name, u.email FROM tutor_profiles tp
         JOIN users u ON tp.user_id = u.id WHERE tp.user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'profile' => $row]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both JSON and multipart
    if (!empty($_POST)) {
        $uid           = intval($_POST['user_id']       ?? 0);
        $dob           = trim($_POST['date_of_birth']   ?? '');
        $qualification = trim($_POST['qualification']   ?? 'Bachelor');
        $subjects      = trim($_POST['subjects']        ?? '');
        $bio           = trim($_POST['bio']             ?? '');
        $hourly_rate   = $RATES[$qualification] ?? 5000;
        $photo_filename = null;

        // Handle optional photo upload
        if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (in_array($_FILES['photo']['type'], $allowed)) {
                $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                $filename = 'tutor_' . $uid . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../image/' . $filename);
                $photo_filename = $filename;
            }
        }

        // Upsert profile
        if ($photo_filename) {
            $stmt = $db->prepare(
                'INSERT INTO tutor_profiles (user_id, date_of_birth, qualification, subjects, bio, hourly_rate, photo_filename)
                 VALUES (?,?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                 date_of_birth=VALUES(date_of_birth), qualification=VALUES(qualification),
                 subjects=VALUES(subjects), bio=VALUES(bio),
                 hourly_rate=VALUES(hourly_rate), photo_filename=VALUES(photo_filename)'
            );
            $stmt->bind_param('issssis', $uid, $dob, $qualification, $subjects, $bio, $hourly_rate, $photo_filename);
        } else {
            $stmt = $db->prepare(
                'INSERT INTO tutor_profiles (user_id, date_of_birth, qualification, subjects, bio, hourly_rate)
                 VALUES (?,?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                 date_of_birth=VALUES(date_of_birth), qualification=VALUES(qualification),
                 subjects=VALUES(subjects), bio=VALUES(bio), hourly_rate=VALUES(hourly_rate)'
            );
            $stmt->bind_param('issssi', $uid, $dob, $qualification, $subjects, $bio, $hourly_rate);
        }

        $ok = $stmt->execute();
        $stmt->close();
        $db->close();
        echo json_encode(['success' => $ok, 'hourly_rate' => $hourly_rate]);

    } else {
        // JSON — admin approve/rate override
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if ($action === 'approve') {
            $uid  = intval($data['user_id'] ?? 0);
            $val  = intval($data['approved'] ?? 1);
            $stmt = $db->prepare('UPDATE tutor_profiles SET is_approved=? WHERE user_id=?');
            $stmt->bind_param('ii', $val, $uid);
            $ok = $stmt->execute();
            $stmt->close();
            $db->close();
            echo json_encode(['success' => $ok]);

        } elseif ($action === 'set_rate') {
            $uid  = intval($data['user_id']    ?? 0);
            $rate = intval($data['hourly_rate'] ?? 5000);
            $rate = max(3000, min(10000, $rate));
            $stmt = $db->prepare('UPDATE tutor_profiles SET hourly_rate=? WHERE user_id=?');
            $stmt->bind_param('ii', $rate, $uid);
            $ok = $stmt->execute();
            $stmt->close();
            $db->close();
            echo json_encode(['success' => $ok]);
        } else {
            $db->close();
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    }
}
