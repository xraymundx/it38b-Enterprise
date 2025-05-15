<?php
session_start();
require_once '../../config/config.php';

// Check if user is logged in and has the required role
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'nurse' && $_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'patient' && $_SESSION['role'] !== 'doctor')
) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Insufficient privileges']);
    exit();
}

// Handle GET request - fetch doctor's available time slots for a date
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    if ($doctor_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid doctor ID']);
        exit();
    }

    // Get day of week for the selected date
    $dayOfWeek = date('l', strtotime($date));

    // Check if there's an exception for this date
    $exceptionQuery = "SELECT * FROM doctor_availability_exceptions
                            WHERE doctor_id = ? AND exception_date = ?";
    $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
    mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($exceptionStmt);
    $exceptionResult = mysqli_stmt_get_result($exceptionStmt);

    // If there's an exception
    if (mysqli_num_rows($exceptionResult) > 0) {
        $exception = mysqli_fetch_assoc($exceptionResult);

        // If doctor is not available on this date
        if (!$exception['is_available']) {
            echo json_encode([
                'success' => true,
                'available' => false,
                'timeSlots' => [],
                'notes' => $exception['notes'],
                'is_exception' => true
            ]);
            exit();
        }

        // If doctor has specific hours for this exception
        if ($exception['start_time'] && $exception['end_time']) {
            // Generate time slots based on exception hours
            $timeSlots = generateTimeSlots($exception['start_time'], $exception['end_time']);

            echo json_encode([
                'success' => true,
                'available' => true,
                'timeSlots' => $timeSlots,
                'start_time' => $exception['start_time'],
                'end_time' => $exception['end_time'],
                'notes' => $exception['notes'],
                'is_exception' => true
            ]);
            exit();
        }
    }

    // If no exception or exception with default hours, check regular schedule
    $scheduleQuery = "SELECT * FROM doctor_schedule
                            WHERE doctor_id = ? AND day_of_week = ?";
    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
    mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
    mysqli_stmt_execute($scheduleStmt);
    $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

    if (mysqli_num_rows($scheduleResult) > 0) {
        $schedule = mysqli_fetch_assoc($scheduleResult);

        // If doctor is not available on this day of week
        if (!$schedule['is_available']) {
            echo json_encode([
                'success' => true,
                'available' => false,
                'timeSlots' => [],
                'is_exception' => false
            ]);
            exit();
        }

        // Generate time slots based on schedule
        $timeSlots = generateTimeSlots($schedule['start_time'], $schedule['end_time']);

        // Check for existing appointments to exclude those time slots
        $appointments = getExistingAppointments($conn, $doctor_id, $date);
        $availableSlots = filterOutBookedSlots($timeSlots, $appointments);

        echo json_encode([
            'success' => true,
            'available' => true,
            'timeSlots' => $availableSlots,
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'is_exception' => false
        ]);
    } else {
        // No schedule found for this day
        echo json_encode([
            'success' => true,
            'available' => false,
            'timeSlots' => [],
            'error' => 'No schedule found for this day of week',
            'is_exception' => false
        ]);
    }
}

// Handle POST request - update doctor's availability for a specific date
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['doctor_id']) || !isset($data['date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit();
    }

    $doctor_id = (int) $data['doctor_id'];
    $date = $data['date'];
    $timeSlots = isset($data['timeSlots']) ? $data['timeSlots'] : [];
    $notes = isset($data['notes']) ? $data['notes'] : '';

    // Check if we're removing all time slots (marking as unavailable)
    $is_available = !empty($timeSlots) ? 1 : 0;

    try {
        // Check if an exception already exists for this date
        $checkQuery = "SELECT id FROM doctor_availability_exceptions
                            WHERE doctor_id = ? AND exception_date = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "is", $doctor_id, $date);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($checkResult) > 0) {
            // Update existing exception
            $exception = mysqli_fetch_assoc($checkResult);
            $id = $exception['id'];

            $updateQuery = "UPDATE doctor_availability_exceptions
                                SET is_available = ?, notes = ?
                                WHERE id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "isi", $is_available, $notes, $id);

            if (!mysqli_stmt_execute($updateStmt)) {
                throw new Exception("Failed to update exception: " . mysqli_error($conn));
            }
        } else {
            // Insert new exception
            $insertQuery = "INSERT INTO doctor_availability_exceptions
                                (doctor_id, exception_date, is_available, notes)
                                VALUES (?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertQuery);
            mysqli_stmt_bind_param($insertStmt, "isis", $doctor_id, $date, $is_available, $notes);

            if (!mysqli_stmt_execute($insertStmt)) {
                throw new Exception("Failed to create exception: " . mysqli_error($conn));
            }
        }

        echo json_encode([
            'success' => true,
            'message' => $is_available ? 'Doctor availability updated successfully' : 'Doctor marked as unavailable for this date',
            'is_available' => $is_available
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Handle DELETE request - remove a specific exception
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';

    if ($doctor_id <= 0 || empty($date)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid doctor ID or date']);
        exit();
    }

    try {
        $deleteQuery = "DELETE FROM doctor_availability_exceptions
                            WHERE doctor_id = ? AND exception_date = ?";
        $deleteStmt = mysqli_prepare($conn, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "is", $doctor_id, $date);

        if (mysqli_stmt_execute($deleteStmt) && mysqli_affected_rows($conn) > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Exception removed successfully, reverting to regular schedule'
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'No exception found for this date'
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Helper functions

// Generate time slots at hourly intervals between start and end times
function generateTimeSlots($start_time, $end_time)
{
    $slots = [];
    $current = strtotime($start_time);
    $end = strtotime($end_time);

    while ($current < $end) {
        $slots[] = date('h:i A', $current);
        $current = strtotime('+1 hour', $current);
    }

    return $slots;
}

// Get existing appointments for a doctor on a specific date
function getExistingAppointments($conn, $doctor_id, $date)
{
    $appointmentsQuery = "SELECT appointment_datetime
                                FROM appointments
                                WHERE doctor_id = ?
                                AND DATE(appointment_datetime) = ?
                                AND status NOT IN ('Cancelled', 'No Show')";
    $appointmentsStmt = mysqli_prepare($conn, $appointmentsQuery);
    mysqli_stmt_bind_param($appointmentsStmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($appointmentsStmt);
    $appointmentsResult = mysqli_stmt_get_result($appointmentsStmt);

    $appointments = [];
    while ($row = mysqli_fetch_assoc($appointmentsResult)) {
        $appointments[] = date('h:i A', strtotime($row['appointment_datetime']));
    }

    return $appointments;
}

// Filter out booked slots from available time slots
function filterOutBookedSlots($allSlots, $bookedSlots)
{
    return array_values(array_diff($allSlots, $bookedSlots));
}