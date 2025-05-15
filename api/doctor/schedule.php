<?php
// Assuming you have your database connection established in config.php
require_once __DIR__ . '/../../config/config.php';

// Set Content-Type to application/json
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (
        isset($data['doctor_id']) &&
        isset($data['day_of_week']) &&
        isset($data['start_time']) &&
        isset($data['end_time']) &&
        isset($data['is_available'])
    ) {
        $doctorId = mysqli_real_escape_string($conn, $data['doctor_id']);
        $dayOfWeek = mysqli_real_escape_string($conn, $data['day_of_week']);
        $startTime = mysqli_real_escape_string($conn, $data['start_time']);
        $endTime = mysqli_real_escape_string($conn, $data['end_time']);
        $isAvailable = intval($data['is_available']);

        error_log("schedule.php: Received doctor_id: " . $doctorId . ", day_of_week: " . $dayOfWeek . ", start_time: " . $startTime . ", end_time: " . $endTime . ", is_available: " . $isAvailable);

        // Check if the doctor_id exists in the doctors table
        $checkDoctorSql = "SELECT doctor_id FROM doctors WHERE doctor_id = ?";
        $checkDoctorStmt = mysqli_prepare($conn, $checkDoctorSql);
        mysqli_stmt_bind_param($checkDoctorStmt, "i", $doctorId);
        mysqli_stmt_execute($checkDoctorStmt);
        $checkDoctorResult = mysqli_stmt_get_result($checkDoctorStmt);

        if (mysqli_num_rows($checkDoctorResult) == 0) {
            error_log("schedule.php: Error - doctor_id " . $doctorId . " does not exist in the doctors table.");
            echo json_encode(['success' => false, 'error' => 'Invalid doctor ID.']);
            mysqli_stmt_close($checkDoctorStmt);
            mysqli_close($conn);
            exit();
        }
        mysqli_stmt_close($checkDoctorStmt);

        // Check if a schedule already exists for this doctor and day
        $checkSql = "SELECT id FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?";
        $checkStmt = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($checkStmt, "is", $doctorId, $dayOfWeek);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing record
            $updateSql = "UPDATE doctor_schedule SET start_time = ?, end_time = ?, is_available = ?, updated_at = NOW() WHERE doctor_id = ? AND day_of_week = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ssiis", $startTime, $endTime, $isAvailable, $doctorId, $dayOfWeek);

            if (mysqli_stmt_execute($updateStmt)) {
                echo json_encode(['success' => true, 'message' => 'Weekly schedule updated successfully.']);
            } else {
                error_log("schedule.php: Error updating weekly schedule: " . mysqli_error($conn) . " - SQL: " . $updateSql);
                echo json_encode(['success' => false, 'error' => 'Error updating weekly schedule: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($updateStmt);
        } else {
            // Insert new record
            $insertSql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, is_available, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            $insertStmt = mysqli_prepare($conn, $insertSql);
            mysqli_stmt_bind_param($insertStmt, "isssi", $doctorId, $dayOfWeek, $startTime, $endTime, $isAvailable);

            if (mysqli_stmt_execute($insertStmt)) {
                echo json_encode(['success' => true, 'message' => 'Weekly schedule saved successfully.']);
            } else {
                error_log("schedule.php: Error saving weekly schedule: " . mysqli_error($conn) . " - SQL: " . $insertSql);
                echo json_encode(['success' => false, 'error' => 'Error saving weekly schedule: ' . mysqli_error($conn)]);
            }
            mysqli_stmt_close($insertStmt);
        }
        mysqli_stmt_close($checkStmt);
    } else {
        error_log("schedule.php: Missing required parameters.");
        echo json_encode(['success' => false, 'error' => 'Missing required parameters.']);
    }
} else {
    error_log("schedule.php: Invalid request method. Only POST is allowed.");
    echo json_encode(['success' => false, 'error' => 'Invalid request method. Only POST is allowed.']);
}

mysqli_close($conn);
?>