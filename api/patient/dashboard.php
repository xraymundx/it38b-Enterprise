<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get database connection
require_once __DIR__ . '/../../config/config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'];

try {
    // Get upcoming appointments
    $appointments_query = "
        SELECT a.*, 
               u.first_name as doctor_first_name, 
               u.last_name as doctor_last_name,
               DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as formatted_date,
               DATE_FORMAT(a.appointment_datetime, '%h:%i %p') as formatted_time
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)
        AND a.appointment_datetime >= NOW()
        ORDER BY a.appointment_datetime ASC
        LIMIT 5";

    $stmt = mysqli_prepare($conn, $appointments_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $appointments_result = mysqli_stmt_get_result($stmt);
    $upcoming_appointments = mysqli_fetch_all($appointments_result, MYSQLI_ASSOC);

    // Get recent medical records
    $records_query = "
        SELECT mr.*, 
               u.first_name as doctor_first_name, 
               u.last_name as doctor_last_name,
               DATE_FORMAT(mr.record_datetime, '%M %d, %Y') as formatted_date,
               SUBSTRING(mr.diagnosis, 1, 100) as short_diagnosis
        FROM medicalrecords mr
        JOIN doctors d ON mr.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE mr.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)
        ORDER BY mr.record_datetime DESC
        LIMIT 5";

    $stmt = mysqli_prepare($conn, $records_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $records_result = mysqli_stmt_get_result($stmt);
    $recent_records = mysqli_fetch_all($records_result, MYSQLI_ASSOC);

    // Get recent billing
    $billing_query = "
        SELECT b.*,
               DATE_FORMAT(b.billing_date, '%M %d, %Y') as formatted_date,
               FORMAT(b.amount, 2) as formatted_amount
        FROM billingrecords b
        WHERE b.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)
        ORDER BY b.billing_date DESC
        LIMIT 5";

    $stmt = mysqli_prepare($conn, $billing_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $billing_result = mysqli_stmt_get_result($stmt);
    $recent_billing = mysqli_fetch_all($billing_result, MYSQLI_ASSOC);

    // Return the data as JSON
    echo json_encode([
        'upcoming_appointments' => $upcoming_appointments,
        'recent_records' => $recent_records,
        'recent_billing' => $recent_billing
    ]);

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to fetch dashboard data: ' . $e->getMessage()]);
}