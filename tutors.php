<?php
// GET /api/tutors.php           — approved tutors for booking
// GET /api/tutors.php?all=1     — all tutors (admin)
// GET /api/tutors.php?chat=1    — all tutors for messaging (no approval filter)
require_once 'config.php';

$db = getDB();

if (isset($_GET['chat']) && $_GET['chat'] === '1') {
    // For messaging: return ALL tutors regardless of approval or profile
    $result = $db->query(
        'SELECT u.id, u.name, u.email,
                COALESCE(tp.qualification, "") AS qualification,
                COALESCE(tp.subjects, "") AS subjects,
                COALESCE(tp.bio, "") AS bio,
                COALESCE(tp.hourly_rate, 5000) AS hourly_rate,
                tp.photo_filename,
                COALESCE(tp.is_approved, 0) AS is_approved
         FROM users u
         LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
         WHERE u.role = "tutor"
         ORDER BY u.name ASC'
    );
} elseif (isset($_GET['all']) && $_GET['all'] === '1') {
    // Admin: all tutors
    $result = $db->query(
        'SELECT u.id, u.name, u.email,
                COALESCE(tp.qualification, "") AS qualification,
                COALESCE(tp.subjects, "") AS subjects,
                COALESCE(tp.bio, "") AS bio,
                COALESCE(tp.hourly_rate, 5000) AS hourly_rate,
                tp.photo_filename,
                COALESCE(tp.is_approved, 0) AS is_approved
         FROM users u
         LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
         WHERE u.role = "tutor"
         ORDER BY tp.hourly_rate DESC, u.name ASC'
    );
} else {
    // Students booking: approved tutors first, then pending (so new tutors are visible)
    $result = $db->query(
        'SELECT u.id, u.name, u.email,
                COALESCE(tp.qualification, "Bachelor") AS qualification,
                COALESCE(tp.subjects, "General") AS subjects,
                COALESCE(tp.bio, "") AS bio,
                COALESCE(tp.hourly_rate, 5000) AS hourly_rate,
                tp.photo_filename,
                COALESCE(tp.is_approved, 0) AS is_approved
         FROM users u
         LEFT JOIN tutor_profiles tp ON u.id = tp.user_id
         WHERE u.role = "tutor"
         ORDER BY tp.is_approved DESC, tp.hourly_rate DESC, u.name ASC'
    );
}

$tutors = [];
while ($row = $result->fetch_assoc()) {
    $tutors[] = $row;
}
$db->close();
echo json_encode(['success' => true, 'tutors' => $tutors]);
