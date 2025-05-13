<?php
session_start();
require_once '../../config/config.php';

// Check if the user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'nurse') {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized access'
    ]);
    exit();
}

// Get the current month in YYYY-MM format
$currentMonth = date('Y-m');

// Query to get appointment statistics for the current month
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Scheduled' OR status = 'Requested' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
FROM appointments 
WHERE DATE_FORMAT(appointment_datetime, '%Y-%m') = ?";

$statsStmt = mysqli_prepare($conn, $statsQuery);

if (!$statsStmt) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . mysqli_error($conn)
    ]);
    exit();
}

mysqli_stmt_bind_param($statsStmt, "s", $currentMonth);

if (!mysqli_stmt_execute($statsStmt)) {
    echo json_encode([
        'success' => false,
        'error' => 'Query error: ' . mysqli_error($conn)
    ]);
    exit();
}

$statsResult = mysqli_stmt_get_result($statsStmt);

if (!$statsResult) {
    echo json_encode([
        'success' => false,
        'error' => 'Result error: ' . mysqli_error($conn)
    ]);
    exit();
}

$stats = mysqli_fetch_assoc($statsResult);

// Ensure values are numeric and not null
$stats['total'] = intval($stats['total']) ?? 0;
$stats['pending'] = intval($stats['pending']) ?? 0;
$stats['completed'] = intval($stats['completed']) ?? 0;

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'stats' => $stats
]);
exit();