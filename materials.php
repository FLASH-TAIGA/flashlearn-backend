<?php
// GET  /api/materials.php          — list all materials
// POST /api/materials.php          — add or delete a material
require_once 'config.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $db->query('SELECT * FROM materials ORDER BY created_at DESC');
    $rows   = $result->fetch_all(MYSQLI_ASSOC);
    $db->close();
    echo json_encode(['success' => true, 'materials' => $rows]);

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle multipart (file upload) vs JSON (delete)
    if (!empty($_FILES['file'])) {
        // File upload from tutor dashboard
        $title       = trim($_POST['title']       ?? '');
        $subject     = trim($_POST['subject']     ?? '');
        $type        = trim($_POST['type']        ?? 'Study Notes');
        $uploaded_by = trim($_POST['uploaded_by'] ?? 'Tutor');

        $allowed_types = ['application/pdf'];
        $file = $_FILES['file'];

        if (!in_array($file['type'], $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Only PDF files are allowed.']);
            $db->close(); exit();
        }

        $upload_dir = __DIR__ . '/../image/';
        $filename   = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $dest       = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt = $db->prepare(
                'INSERT INTO materials (title, subject, type, filename, uploaded_by) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssss', $title, $subject, $type, $filename, $uploaded_by);
            $ok = $stmt->execute();
            $stmt->close();
            $db->close();
            echo json_encode(['success' => $ok, 'filename' => $filename]);
        } else {
            $db->close();
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
        }

    } else {
        // JSON action (delete)
        $data   = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';

        if ($action === 'delete') {
            $id   = intval($data['id'] ?? 0);
            $stmt = $db->prepare('DELETE FROM materials WHERE id = ?');
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
}
