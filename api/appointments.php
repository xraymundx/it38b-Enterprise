<?php
session_start();
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in and is a nurse
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Not logged in']);
    exit();
}

if ($_SESSION['role'] !== 'nurse') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient role']);
    exit();
}

// Handle GET request - fetch appointments
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    // If specific appointment ID is requested
    if ($id) {
        $query = "SELECT 
                    a.appointment_id,
                    a.patient_id,
                    a.doctor_id,
                    a.appointment_datetime,
                    a.reason_for_visit,
                    a.notes,
                    a.status,
                    a.created_at,
                    a.updated_at,
                    pu.first_name as patient_first_name,
                    pu.last_name as patient_last_name,
                    pu.email as patient_email,
                    du.first_name as doctor_first_name,
                    du.last_name as doctor_last_name,
                    du.email as doctor_email
                  FROM appointments a 
                  JOIN patients p ON a.patient_id = p.patient_id 
                  JOIN users pu ON p.user_id = pu.user_id
                  JOIN doctors d ON a.doctor_id = d.doctor_id
                  JOIN users du ON d.user_id = du.user_id
                  WHERE a.appointment_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $appointment = mysqli_fetch_assoc($result);

        if ($appointment) {
            echo json_encode([
                'success' => true,
                'appointment' => $appointment
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Appointment not found'
            ]);
        }
        exit();
    }

    // For listing appointments
    $query = "SELECT 
                a.appointment_id,
                a.patient_id,
                a.doctor_id,
                a.appointment_datetime,
                a.reason_for_visit,
                a.notes,
                a.status,
                a.created_at,
                a.updated_at,
                pu.first_name as patient_first_name,
                pu.last_name as patient_last_name,
                pu.email as patient_email,
                du.first_name as doctor_first_name,
                du.last_name as doctor_last_name,
                du.email as doctor_email
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.patient_id 
              JOIN users pu ON p.user_id = pu.user_id
              JOIN doctors d ON a.doctor_id = d.doctor_id
              JOIN users du ON d.user_id = du.user_id";

    $params = [];
    $types = "";

    if ($date) {
        $query .= " WHERE DATE(a.appointment_datetime) = ?";
        $params[] = $date;
        $types .= "s";
    }

    if ($status && $status !== 'all') {
        $query .= $date ? " AND" : " WHERE";
        $query .= " a.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $query .= " ORDER BY a.appointment_datetime DESC";

    $stmt = mysqli_prepare($conn, $query);
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch appointments: ' . mysqli_error($conn)
        ]);
        exit();
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format datetime for better readability
        $row['appointment_datetime'] = date('Y-m-d H:i:s', strtotime($row['appointment_datetime']));
        $row['created_at'] = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $row['updated_at'] = date('Y-m-d H:i:s', strtotime($row['updated_at']));
        $appointments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'count' => count($appointments)
    ]);
}

// Handle POST request - create new appointment
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required_fields = ['doctor_id', 'patient_id', 'date', 'time', 'reason'];
    $missing_fields = array_filter($required_fields, function ($field) use ($data) {
        return !isset($data[$field]) || empty($data[$field]);
    });

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Missing required fields',
            'missing_fields' => array_values($missing_fields)
        ]);
        exit();
    }

    $doctor_id = (int) $data['doctor_id'];
    $patient_id = (int) $data['patient_id'];
    $date = $data['date'];
    $time = $data['time'];
    $reason = $data['reason'];
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $status = isset($data['status']) ? $data['status'] : 'Scheduled';

    // Validate status
    $valid_statuses = ['Scheduled', 'Confirmed', 'Completed', 'Cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status value']);
        exit();
    }

    // Combine date and time
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if the time slot is available
        $checkQuery = "SELECT COUNT(*) as count FROM appointments 
                      WHERE doctor_id = ? AND appointment_datetime = ? AND status != 'Cancelled'";
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
                       (patient_id, doctor_id, appointment_datetime, reason_for_visit, notes, status, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param(
            $insertStmt,
            "iissss",
            $patient_id,
            $doctor_id,
            $appointment_datetime,
            $reason,
            $notes,
            $status
        );

        if (!mysqli_stmt_execute($insertStmt)) {
            throw new Exception('Failed to create appointment: ' . mysqli_error($conn));
        }

        $appointment_id = mysqli_insert_id($conn);

        // Commit transaction
        mysqli_commit($conn);

        // Fetch the newly created appointment
        $fetchQuery = "SELECT 
                        a.*, 
                        pu.first_name as patient_first_name,
                        pu.last_name as patient_last_name,
                        du.first_name as doctor_first_name,
                        du.last_name as doctor_last_name
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN users pu ON p.user_id = pu.user_id
                      JOIN doctors d ON a.doctor_id = d.doctor_id
                      JOIN users du ON d.user_id = du.user_id
                      WHERE a.appointment_id = ?";

        $fetchStmt = mysqli_prepare($conn, $fetchQuery);
        mysqli_stmt_bind_param($fetchStmt, "i", $appointment_id);
        mysqli_stmt_execute($fetchStmt);
        $appointment = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));

        echo json_encode([
            'success' => true,
            'message' => 'Appointment scheduled successfully',
            'appointment' => $appointment
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to schedule appointment',
            'message' => $e->getMessage()
        ]);
    }
}

// Handle PUT request - update appointment
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['appointment_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Appointment ID is required']);
        exit();
    }

    $appointment_id = (int) $data['appointment_id'];
    $updates = [];
    $params = [];
    $types = "";

    // Build update query dynamically based on provided fields
    $allowed_fields = [
        'status' => 's',
        'reason_for_visit' => 's',
        'notes' => 's',
        'appointment_datetime' => 's'
    ];

    foreach ($allowed_fields as $field => $type) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field];
            $types .= $type;
        }
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        exit();
    }

    // Add appointment_id to params
    $params[] = $appointment_id;
    $types .= "i";

    $query = "UPDATE appointments SET " . implode(", ", $updates) . ", updated_at = NOW() WHERE appointment_id = ?";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update appointment: ' . mysqli_error($conn)]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Appointment updated successfully'
    ]);
}

// Handle DELETE request - cancel appointment
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($appointment_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid appointment ID']);
        exit();
    }

    $query = "UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE appointment_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to cancel appointment: ' . mysqli_error($conn)]);
        exit();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Appointment cancelled successfully'
    ]);
}

// Handle invalid request method
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}