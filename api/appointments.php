<?php
session_start();
require_once '../config/config.php'; // Adjust path as needed

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
    $date = isset($_GET['date']) ? $_GET['date'] : null;
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

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "i", $id);
        if (!mysqli_stmt_execute($stmt)) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to execute query: ' . mysqli_error($conn)
            ]);
            exit();
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to get result: ' . mysqli_error($conn)
            ]);
            exit();
        }

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

    // For listing appointments - either by date or all appointments
    if ($date) {
        // Filter by specific date
        $query = "SELECT 
                    a.appointment_id,
                    a.patient_id,
                    a.doctor_id,
                    a.appointment_datetime,
                    a.reason_for_visit,
                    a.notes,
                    a.status,
                    pu.first_name as patient_first_name,
                    pu.last_name as patient_last_name,
                    du.first_name as doctor_first_name,
                    du.last_name as doctor_last_name
                  FROM appointments a 
                  JOIN patients p ON a.patient_id = p.patient_id 
                  JOIN users pu ON p.user_id = pu.user_id
                  JOIN doctors d ON a.doctor_id = d.doctor_id
                  JOIN users du ON d.user_id = du.user_id
                  WHERE DATE(a.appointment_datetime) = ?
                  ORDER BY a.appointment_datetime";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "s", $date);
    } else {
        // Get all appointments
        $query = "SELECT 
                    a.appointment_id,
                    a.patient_id,
                    a.doctor_id,
                    a.appointment_datetime,
                    a.reason_for_visit,
                    a.notes,
                    a.status,
                    pu.first_name as patient_first_name,
                    pu.last_name as patient_last_name,
                    du.first_name as doctor_first_name,
                    du.last_name as doctor_last_name
                  FROM appointments a 
                  JOIN patients p ON a.patient_id = p.patient_id 
                  JOIN users pu ON p.user_id = pu.user_id
                  JOIN doctors d ON a.doctor_id = d.doctor_id
                  JOIN users du ON d.user_id = du.user_id
                  ORDER BY a.appointment_datetime DESC";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit();
        }
    }

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to execute query: ' . mysqli_error($conn)
        ]);
        exit();
    }

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
        $appointments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);
    exit();
}

// Handle POST request - create new appointment
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
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
    $status = 'Scheduled';

    // Combine date and time
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if the doctor is available on this date
        $availabilityQuery = "SELECT is_available, notes FROM doctor_availability_exceptions 
                           WHERE doctor_id = ? AND exception_date = ?";
        $availabilityStmt = mysqli_prepare($conn, $availabilityQuery);
        mysqli_stmt_bind_param($availabilityStmt, "is", $doctor_id, $date);
        mysqli_stmt_execute($availabilityStmt);
        $availabilityResult = mysqli_stmt_get_result($availabilityStmt);

        // If there's an exception for this date
        if (mysqli_num_rows($availabilityResult) > 0) {
            $exception = mysqli_fetch_assoc($availabilityResult);
            // If doctor is marked as unavailable
            if (!$exception['is_available']) {
                throw new Exception('Doctor is not available on this date: ' . ($exception['notes'] ? $exception['notes'] : 'Unavailable'));
            }
        } else {
            // Check regular schedule for this day of week
            $dayOfWeek = date('l', strtotime($date));
            $scheduleQuery = "SELECT is_available FROM doctor_schedule 
                           WHERE doctor_id = ? AND day_of_week = ?";
            $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
            mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
            mysqli_stmt_execute($scheduleStmt);
            $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

            // If the doctor doesn't have a schedule for this day or is not available
            if (mysqli_num_rows($scheduleResult) === 0) {
                throw new Exception('Doctor does not have a schedule for this day');
            }

            $schedule = mysqli_fetch_assoc($scheduleResult);
            if (!$schedule['is_available']) {
                throw new Exception('Doctor is not scheduled to work on this day');
            }
        }

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
                       (patient_id, doctor_id, appointment_datetime, reason_for_visit, notes, status) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);
        mysqli_stmt_bind_param($insertStmt, "iissss", $patient_id, $doctor_id, $appointment_datetime, $reason, $notes, $status);

        if (!mysqli_stmt_execute($insertStmt)) {
            throw new Exception('Failed to create appointment: ' . mysqli_error($conn));
        }

        $appointment_id = mysqli_insert_id($conn);

        // Commit transaction
        mysqli_commit($conn);

        // Return the newly created appointment with names
        $fetchQuery = "SELECT 
                        a.appointment_id, 
                        a.patient_id, 
                        a.doctor_id, 
                        a.appointment_datetime, 
                        a.reason_for_visit, 
                        a.notes, 
                        a.status,
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
        $newAppointment = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));

        echo json_encode([
            'success' => true,
            'message' => 'Appointment scheduled successfully',
            'appointment' => $newAppointment
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to schedule appointment: ' . $e->getMessage()]);
    }
}

// Handle PUT request - update appointment status
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Check if it's a full update or just a status update
    $isFullUpdate = isset($data['appointment_id']) && isset($data['doctor_id']) && isset($data['patient_id'])
        && isset($data['date']) && isset($data['time']) && isset($data['reason']);

    $isStatusUpdate = isset($data['appointment_id']) && isset($data['status']) && !$isFullUpdate;

    if (!$isFullUpdate && !$isStatusUpdate) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields'
        ]);
        exit();
    }

    // Process status update
    if ($isStatusUpdate) {
        $appointment_id = (int) $data['appointment_id'];
        $status = $data['status'];

        // Validate status
        $valid_statuses = ['Requested', 'Scheduled', 'Completed', 'Cancelled', 'No Show'];
        if (!in_array($status, $valid_statuses)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid status value'
            ]);
            exit();
        }

        $query = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
            ]);
            exit();
        }

        mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);

        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'success' => true,
                'message' => 'Appointment status updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update appointment status: ' . mysqli_error($conn)
            ]);
        }
        exit();
    }

    // Process full appointment update
    if ($isFullUpdate) {
        $appointment_id = (int) $data['appointment_id'];
        $doctor_id = (int) $data['doctor_id'];
        $patient_id = (int) $data['patient_id'];
        $date = $data['date'];
        $time = $data['time'];
        $reason = $data['reason'];
        $notes = isset($data['notes']) ? $data['notes'] : '';
        $status = isset($data['status']) ? $data['status'] : 'Scheduled';

        // Combine date and time
        $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // First, check if appointment exists
            $checkQuery = "SELECT * FROM appointments WHERE appointment_id = ?";
            $checkStmt = mysqli_prepare($conn, $checkQuery);

            if (!$checkStmt) {
                throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($checkStmt, "i", $appointment_id);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);

            if (mysqli_num_rows($checkResult) === 0) {
                throw new Exception("Appointment not found");
            }

            // Check if new time slot is available (if doctor or datetime changed)
            $oldAppointment = mysqli_fetch_assoc($checkResult);
            if ($oldAppointment['doctor_id'] != $doctor_id || $oldAppointment['appointment_datetime'] != $appointment_datetime) {
                // Check doctor's availability
                $dayOfWeek = date('l', strtotime($date));

                // Check for exceptions first
                $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                                WHERE doctor_id = ? AND exception_date = ?";
                $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
                if (!$exceptionStmt) {
                    throw new Exception("Failed to prepare exception statement: " . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $date);
                mysqli_stmt_execute($exceptionStmt);
                $exceptionResult = mysqli_stmt_get_result($exceptionStmt);

                if (mysqli_num_rows($exceptionResult) > 0) {
                    $exception = mysqli_fetch_assoc($exceptionResult);
                    if (!$exception['is_available']) {
                        throw new Exception("Doctor is not available on this date: " .
                            ($exception['notes'] ? $exception['notes'] : 'Unavailable'));
                    }
                } else {
                    // Check regular schedule
                    $scheduleQuery = "SELECT * FROM doctor_schedule 
                                    WHERE doctor_id = ? AND day_of_week = ?";
                    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
                    if (!$scheduleStmt) {
                        throw new Exception("Failed to prepare schedule statement: " . mysqli_error($conn));
                    }

                    mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
                    mysqli_stmt_execute($scheduleStmt);
                    $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

                    if (mysqli_num_rows($scheduleResult) === 0) {
                        throw new Exception("Doctor does not have a schedule for this day");
                    }

                    $schedule = mysqli_fetch_assoc($scheduleResult);
                    if (!$schedule['is_available']) {
                        throw new Exception("Doctor is not scheduled to work on this day");
                    }
                }

                // Check for appointment conflicts
                $conflictQuery = "SELECT COUNT(*) as count FROM appointments 
                                WHERE doctor_id = ? 
                                AND appointment_datetime = ? 
                                AND appointment_id != ? 
                                AND status NOT IN ('Cancelled', 'No Show')";
                $conflictStmt = mysqli_prepare($conn, $conflictQuery);
                if (!$conflictStmt) {
                    throw new Exception("Failed to prepare conflict statement: " . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($conflictStmt, "isi", $doctor_id, $appointment_datetime, $appointment_id);
                mysqli_stmt_execute($conflictStmt);
                $conflictResult = mysqli_stmt_get_result($conflictStmt);
                $conflictRow = mysqli_fetch_assoc($conflictResult);

                if ($conflictRow['count'] > 0) {
                    throw new Exception("This time slot is already booked");
                }
            }

            // Update appointment
            $updateQuery = "UPDATE appointments 
                          SET patient_id = ?, 
                              doctor_id = ?, 
                              appointment_datetime = ?, 
                              reason_for_visit = ?, 
                              notes = ?, 
                              status = ?,
                              updated_at = NOW() 
                          WHERE appointment_id = ?";

            $updateStmt = mysqli_prepare($conn, $updateQuery);
            if (!$updateStmt) {
                throw new Exception("Failed to prepare update statement: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param(
                $updateStmt,
                "iissssi",
                $patient_id,
                $doctor_id,
                $appointment_datetime,
                $reason,
                $notes,
                $status,
                $appointment_id
            );

            if (!mysqli_stmt_execute($updateStmt)) {
                throw new Exception("Failed to update appointment: " . mysqli_error($conn));
            }

            // Commit transaction
            mysqli_commit($conn);

            // Get updated appointment data
            $fetchQuery = "SELECT 
                            a.appointment_id,
                            a.patient_id,
                            a.doctor_id,
                            a.appointment_datetime,
                            a.reason_for_visit,
                            a.notes,
                            a.status,
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
            $updatedAppointment = mysqli_fetch_assoc(mysqli_stmt_get_result($fetchStmt));

            echo json_encode([
                'success' => true,
                'message' => 'Appointment updated successfully',
                'appointment' => $updatedAppointment
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            mysqli_rollback($conn);
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to update appointment: ' . $e->getMessage()
            ]);
        }
        exit();
    }
}

// Handle DELETE request - cancel appointment
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $appointment_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($appointment_id <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid appointment ID'
        ]);
        exit();
    }

    // First check if the appointment exists
    $checkQuery = "SELECT * FROM appointments WHERE appointment_id = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);

    if (!$checkStmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
        ]);
        exit();
    }

    mysqli_stmt_bind_param($checkStmt, "i", $appointment_id);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($result) === 0) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Appointment not found'
        ]);
        exit();
    }

    $query = "UPDATE appointments SET status = 'Cancelled', updated_at = NOW() WHERE appointment_id = ?";
    $stmt = mysqli_prepare($conn, $query);

    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to prepare statement: ' . mysqli_error($conn)
        ]);
        exit();
    }

    mysqli_stmt_bind_param($stmt, "i", $appointment_id);

    if (!mysqli_stmt_execute($stmt)) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to cancel appointment: ' . mysqli_error($conn)
        ]);
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