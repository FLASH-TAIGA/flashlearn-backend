<?php
// GET /api/earnings.php?tutor_id=X  — tutor earnings breakdown
// GET /api/earnings.php?admin=1     — platform revenue summary
require_once 'config.php';
$db = getDB();

if (isset($_GET['tutor_id'])) {
    $tid = intval($_GET['tutor_id']);

    // Total & by status
    $stmt = $db->prepare(
        'SELECT
            COUNT(*) AS total_sessions,
            SUM(tutor_earnings) AS total_earned,
            SUM(CASE WHEN status="completed" THEN tutor_earnings ELSE 0 END) AS confirmed_earned,
            SUM(CASE WHEN status="pending" OR status="confirmed" THEN tutor_earnings ELSE 0 END) AS pending_earned,
            SUM(platform_fee) AS total_fee_paid
         FROM sessions WHERE tutor_id = ?'
    );
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $summary = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Per-session breakdown (last 20)
    $stmt = $db->prepare(
        'SELECT s.id, s.subject, s.session_date, s.session_time, s.amount_paid,
                s.platform_fee, s.tutor_earnings, s.status, u.name AS student_name
         FROM sessions s
         JOIN users u ON s.student_id = u.id
         WHERE s.tutor_id = ?
         ORDER BY s.session_date DESC, s.session_time DESC LIMIT 20'
    );
    $stmt->bind_param('i', $tid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $db->close();

    echo json_encode(['success' => true, 'summary' => $summary, 'sessions' => $rows]);

} elseif (isset($_GET['admin']) && $_GET['admin'] === '1') {
    // Platform-wide revenue
    $result = $db->query(
        'SELECT
            COUNT(*) AS total_sessions,
            SUM(amount_paid) AS gross_revenue,
            SUM(platform_fee) AS platform_revenue,
            SUM(tutor_earnings) AS tutor_payouts,
            SUM(CASE WHEN status="completed" THEN platform_fee ELSE 0 END) AS confirmed_revenue,
            SUM(CASE WHEN status="pending" OR status="confirmed" THEN platform_fee ELSE 0 END) AS pending_revenue
         FROM sessions'
    );
    $summary = $result->fetch_assoc();

    // Per-tutor breakdown
    $result2 = $db->query(
        'SELECT u.name AS tutor_name,
                COUNT(s.id) AS sessions,
                SUM(s.amount_paid) AS gross,
                SUM(s.platform_fee) AS fee,
                SUM(s.tutor_earnings) AS payout
         FROM sessions s
         JOIN users u ON s.tutor_id = u.id
         GROUP BY s.tutor_id
         ORDER BY gross DESC'
    );
    $byTutor = $result2->fetch_all(MYSQLI_ASSOC);
    $db->close();

    echo json_encode(['success' => true, 'summary' => $summary, 'by_tutor' => $byTutor]);
} else {
    $db->close();
    echo json_encode(['success' => false, 'message' => 'Missing parameter']);
}
