<?php

session_start(); // Ensure session is started
require_once __DIR__ . '/../../config/config.php';

// Define allowed roles
$allowed_roles = ['nurse', 'patient', 'admin', 'doctor'];

// Check authentication and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get user ID and role from session
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$response = ['success' => false];

try {
    switch ($action) {
        case 'list':
            // Patients should only see their own billing records
            $patient_where = '';
            if ($user_role === 'patient') {
                $patient_where = "AND b.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)";
            }

            $query = "
                SELECT b.*,
                       DATE_FORMAT(b.billing_date, '%M %d, %Y') as formatted_date,
                       FORMAT(b.amount, 2) as formatted_amount,
                       a.appointment_datetime,
                       DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as appointment_date,
                       u.first_name as doctor_first_name,
                       u.last_name as doctor_last_name
                FROM billingrecords b
                LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN users u ON d.user_id = u.user_id
                WHERE 1=1 -- Base condition
                $patient_where
                ORDER BY b.billing_date DESC
            ";

            $stmt = mysqli_prepare($conn, $query);
            if ($user_role === 'patient') {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $billing_records = mysqli_fetch_all($result, MYSQLI_ASSOC);

            $response = [
                'success' => true,
                'billing_records' => $billing_records
            ];
            break;

        case 'view':
            if (!isset($_GET['id'])) {
                throw new Exception('Billing ID is required');
            }
            $billing_id = $_GET['id'];

            // Patients should only view their own billing records
            $patient_where = '';
            if ($user_role === 'patient') {
                $patient_where = "AND b.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)";
            }

            $query = "
                SELECT b.*,
                       DATE_FORMAT(b.billing_date, '%M %d, %Y') as formatted_date,
                       FORMAT(b.amount, 2) as formatted_amount,
                       a.appointment_datetime,
                       DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') as appointment_date,
                       u.first_name as doctor_first_name,
                       u.last_name as doctor_last_name
                FROM billingrecords b
                LEFT JOIN appointments a ON b.appointment_id = a.appointment_id
                LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN users u ON d.user_id = u.user_id
                WHERE b.bill_id = ?
                $patient_where
            ";

            $stmt = mysqli_prepare($conn, $query);
            if ($user_role === 'patient') {
                mysqli_stmt_bind_param($stmt, "ii", $billing_id, $user_id);
            } else {
                mysqli_stmt_bind_param($stmt, "i", $billing_id);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $billing = mysqli_fetch_assoc($result);

            if (!$billing) {
                throw new Exception('Billing record not found');
            }

            $response = [
                'success' => true,
                'billing' => $billing
            ];
            break;

        case 'pay':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['billing_id']) || !isset($data['payment_method'])) {
                throw new Exception('Missing required fields');
            }

            $billing_id = $data['billing_id'];
            $payment_method = $data['payment_method'];

            // Patients should only pay their own unpaid bills
            $patient_where = '';
            if ($user_role === 'patient') {
                $patient_where = "AND b.patient_id = (SELECT patient_id FROM patients WHERE user_id = ?)";
            }

            $query = "
                SELECT b.*
                FROM billingrecords b
                WHERE b.bill_id = ?
                $patient_where
                AND b.payment_status != 'Paid'
            ";

            $stmt = mysqli_prepare($conn, $query);
            if ($user_role === 'patient') {
                mysqli_stmt_bind_param($stmt, "ii", $billing_id, $user_id);
            } else {
                mysqli_stmt_bind_param($stmt, "i", $billing_id);
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $billing = mysqli_fetch_assoc($result);

            if (!$billing) {
                throw new Exception('Invalid billing record or already paid');
            }

            // Process payment
            $update_query = "
                UPDATE billingrecords
                SET payment_status = 'Paid',
                    payment_method = ?,
                    payment_date = NOW()
                WHERE bill_id = ?
            ";

            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $payment_method, $billing_id);

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to process payment');
            }

            $response = [
                'success' => true,
                'message' => 'Payment processed successfully'
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);