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
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$response = ['success' => false];

try {
    switch ($action) {
        case 'list':
            // Get medical records
            $query = "
                SELECT mr.*, 
                       u.first_name as doctor_first_name, 
                       u.last_name as doctor_last_name,
                       s.specialization_name,
                       DATE_FORMAT(mr.record_datetime, '%M %d, %Y') as formatted_date,
                       DATE_FORMAT(mr.record_datetime, '%h:%i %p') as formatted_time,
                       SUBSTRING(mr.diagnosis, 1, 100) as short_diagnosis
                FROM medicalrecords mr
                JOIN doctors d ON mr.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                JOIN specializations s ON d.specialization_id = s.specialization_id
                WHERE mr.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)
                ORDER BY mr.record_datetime DESC";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $records = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $response = [
                'success' => true,
                'records' => $records
            ];
            break;

        case 'view':
            // Get a specific medical record
            $record_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

            if (!$record_id) {
                throw new Exception('Invalid record ID');
            }

            // Verify ownership and get record details
            $query = "
                SELECT mr.*, 
                       u.first_name as doctor_first_name, 
                       u.last_name as doctor_last_name,
                       s.specialization_name,
                       DATE_FORMAT(mr.record_datetime, '%M %d, %Y') as formatted_date,
                       DATE_FORMAT(mr.record_datetime, '%h:%i %p') as formatted_time,
                       a.appointment_id,
                       DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as appointment_date,
                       mr.prescribed_medications,
                       mr.test_results
                FROM medicalrecords mr
                JOIN doctors d ON mr.doctor_id = d.doctor_id
                JOIN users u ON d.user_id = u.user_id
                JOIN specializations s ON d.specialization_id = s.specialization_id
                LEFT JOIN appointments a ON mr.appointment_id = a.appointment_id
                WHERE mr.record_id = ? 
                AND mr.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)";

            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ii", $record_id, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $record = mysqli_fetch_assoc($result);

            if (!$record) {
                throw new Exception('Record not found or unauthorized access');
            }

            $response = [
                'success' => true,
                'record' => $record
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Return JSON response
echo json_encode($response);