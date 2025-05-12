<?php
session_start();
require('../../config/config.php');

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

// Handle GET request - fetch doctor availability
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    if (!$doctor_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Doctor ID is required']);
        exit();
    }

    // Get doctor's regular schedule for the day of week
    $dayOfWeek = date('l', strtotime($date));
    $scheduleQuery = "SELECT * FROM doctor_schedule 
                     WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1";
    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
    mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
    mysqli_stmt_execute($scheduleStmt);
    $scheduleResult = mysqli_stmt_get_result($scheduleStmt);
    $schedule = mysqli_fetch_assoc($scheduleResult);

    // Get any exceptions for this date
    $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                      WHERE doctor_id = ? AND exception_date = ?";
    $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
    mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($exceptionStmt);
    $exceptionResult = mysqli_stmt_get_result($exceptionStmt);
    $exception = mysqli_fetch_assoc($exceptionResult);

    // If there's an exception that makes the doctor unavailable, return empty slots
    if ($exception && !$exception['is_available']) {
        echo json_encode(['timeSlots' => []]);
        exit();
    }

    // If there's an exception with specific times, use those
    if ($exception && $exception['is_available'] && $exception['start_time'] && $exception['end_time']) {
        $startTime = strtotime($exception['start_time']);
        $endTime = strtotime($exception['end_time']);
    }
    // Otherwise use the regular schedule
    else if ($schedule) {
        $startTime = strtotime($schedule['start_time']);
        $endTime = strtotime($schedule['end_time']);
    } else {
        echo json_encode(['timeSlots' => []]);
        exit();
    }

    // Generate time slots
    $timeSlots = [];
    for ($time = $startTime; $time <= $endTime; $time += 3600) {
        $timeStr = date('h:i A', $time);
        $timeSlots[] = $timeStr;
    }

    echo json_encode(['timeSlots' => $timeSlots]);
}

// Handle POST request - save doctor availability
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['doctor_id']) || !isset($data['date']) || !isset($data['timeSlots'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $doctor_id = (int) $data['doctor_id'];
    $date = $data['date'];
    $timeSlots = $data['timeSlots'];

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete any existing exceptions for this date
        $deleteQuery = "DELETE FROM doctor_availability_exceptions 
                       WHERE doctor_id = ? AND exception_date = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "is", $doctor_id, $date);
        mysqli_stmt_execute($deleteStmt);

        // If there are time slots, create an exception
        if (!empty($timeSlots)) {
            $startTime = date('H:i:s', strtotime($timeSlots[0]));
            $endTime = date('H:i:s', strtotime(end($timeSlots)) + 3600); // Add one hour to last slot

            $insertQuery = "INSERT INTO doctor_availability_exceptions 
                           (doctor_id, exception_date, start_time, end_time, is_available) 
                           VALUES (?, ?, ?, ?, 1)";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, "isss", $doctor_id, $date, $startTime, $endTime);
            mysqli_stmt_execute($insertStmt);
        }

        // Commit transaction
        mysqli_commit($conn);
        echo json_encode(['success' => true, 'message' => 'Doctor availability saved successfully']);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save doctor availability: ' . $e->getMessage()]);
    }
}

// Handle invalid request method
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}