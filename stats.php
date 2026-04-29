<?php
// GET /api/stats.php — public platform stats
require_once 'config.php';
$db = getDB();

$students = $db->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$tutors   = $db->query("SELECT COUNT(*) FROM users WHERE role='tutor'")->fetch_row()[0];
$sessions = $db->query("SELECT COUNT(*) FROM sessions")->fetch_row()[0];

// Count distinct subjects across all approved tutor profiles
$result   = $db->query("SELECT subjects FROM tutor_profiles WHERE is_approved=1 AND subjects != ''");
$subjectSet = [];
while ($row = $result->fetch_assoc()) {
    foreach (explode(',', $row['subjects']) as $s) {
        $s = trim($s);
        if ($s) $subjectSet[$s] = true;
    }
}
$subjectCount = count($subjectSet);

$db->close();
echo json_encode([
    'success'  => true,
    'students' => (int)$students,
    'tutors'   => (int)$tutors,
    'sessions' => (int)$sessions,
    'subjects' => $subjectCount,
]);
