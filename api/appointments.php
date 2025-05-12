<?php
session_start();
require_once '../config/config.php'; // Adjust path as needed

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Not logged in']);
    exit();
}

if ($_SESSION['role'] !== 'nurse') { // Example: Only nurses can access patients
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient role']);
    exit();
}
// Handle GET request - fetch appointments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    $query = "SELECT a.*, p.first_name, p.last_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.patient_id 
              JOIN doctors d ON a.doctor_id = d.doctor_id
              WHERE DATE(a.appointment_datetime) = ?
              ORDER BY a.appointment_datetime";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch appointments']);
        exit();
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    echo json_encode(['appointments' => $appointments]);
}

// Handle POST request - create new appointment
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['doctor_id']) || !isset($data['patient_id']) || !isset($data['date']) || !isset($data['time']) || !isset($data['reason'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $doctor_id = (int) $data['doctor_id'];
    $patient_id = (int) $data['patient_id'];
    $date = $data['date'];
    $time = $data['time'];
    $reason = $data['reason'];
    $notes = isset($data['notes']) ? $data['notes'] : '';

    // Combine date and time
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if the time slot is available
        $checkQuery = "SELECT COUNT(*) as count FROM appointments 
                      WHERE doctor_id = ? AND appointment_datetime = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "is", $doctor_id, $appointment_datetime);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);
        $checkRow = mysqli_fetch_assoc($checkResult);

        if ($checkRow['count'] > 0) {
            throw new Exception('This time slot is already booked');
        }

        // Insert new appointment
        $insertQuery = "INSERT INTO appointments 
                       (patient_id, doctor_id, appointment_datetime, reason_for_visit, notes, status) 
                       VALUES (?, ?, ?, ?, ?, 'Scheduled')";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, "iisss", $patient_id, $doctor_id, $appointment_datetime, $reason, $notes);
        mysqli_stmt_execute($insertStmt);

        // Commit transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Appointment scheduled successfully']);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to schedule appointment: ' . $e->getMessage()]);
    }
}

// Handle invalid request method
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}