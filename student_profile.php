<?php
// GET  /api/student_profile.php?user_id=X  — get student profile
// POST /api/student_profile.php             — save/update student profile (multipart for photo)
require_once 'config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid  = intval($_GET['user_id'] ?? 0);
    $stmt = $db->prepare(
        'SELECT sp.*, u.name, u.email FROM student_profiles sp
         JOIN users u ON sp.user_id = u.id WHERE sp.user_id = ?'
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => true, 'profile' => $row]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid           = intval($_POST['user_id']     ?? 0);
    $dob           = trim($_POST['date_of_birth'] ?? '');
    $subjects      = trim($_POST['subjects']      ?? '');
    $bio           = trim($_POST['bio']           ?? '');
    $photo_filename = null;

    if (!$uid) {
        echo json_encode(['success' => false, 'message' => 'Missing user_id']);
        $db->close(); exit();
    }

    // Handle optional photo upload
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if (in_array($_FILES['photo']['type'], $allowed)) {
            $ext      = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'student_' . $uid . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../image/' . $filename);
            $photo_filename = $filename;
        }
    }

    if ($photo_filename) {
        $stmt = $db->prepare(
            'INSERT INTO student_profiles (user_id, date_of_birth, subjects, bio, photo_filename)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
             date_of_birth=VALUES(date_of_birth), subjects=VALUES(subjects),
             bio=VALUES(bio), photo_filename=VALUES(photo_filename)'
        );
        $stmt->bind_param('issss', $uid, $dob, $subjects, $bio, $photo_filename);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO student_profiles (user_id, date_of_birth, subjects, bio)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE
             date_of_birth=VALUES(date_of_birth), subjects=VALUES(subjects), bio=VALUES(bio)'
        );
        $stmt->bind_param('isss', $uid, $dob, $subjects, $bio);
    }

    $ok = $stmt->execute();
    $stmt->close();
    $db->close();
    echo json_encode(['success' => $ok]);
}
