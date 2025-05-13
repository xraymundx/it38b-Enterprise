<?php
session_start();
require_once '../config/config.php';

// This example demonstrates how to work with doctor schedules
// Including setting up regular schedules and exceptions

// Check authentication (assuming admin or nurse role is required)
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    ($_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'nurse')
) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient privileges']);
    exit();
}

// Example: Set up a doctor's weekly schedule
function setupWeeklySchedule($conn, $doctor_id)
{
    // First, clear any existing schedule for this doctor
    $clearQuery = "DELETE FROM doctor_schedule WHERE doctor_id = ?";
    $clearStmt = mysqli_prepare($conn, $clearQuery);
    mysqli_stmt_bind_param($clearStmt, "i", $doctor_id);
    mysqli_stmt_execute($clearStmt);

    // Example schedule data - could come from a form
    $scheduleData = [
        'Monday' => ['09:00:00', '17:00:00', 1],
        'Tuesday' => ['09:00:00', '17:00:00', 1],
        'Wednesday' => ['10:00:00', '18:00:00', 1],
        'Thursday' => ['09:00:00', '17:00:00', 1],
        'Friday' => ['09:00:00', '15:00:00', 1],
        'Saturday' => ['10:00:00', '14:00:00', 0], // Not available
        'Sunday' => ['00:00:00', '00:00:00', 0]    // Not available
    ];

    $insertQuery = "INSERT INTO doctor_schedule 
                   (doctor_id, day_of_week, start_time, end_time, is_available) 
                   VALUES (?, ?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);

    foreach ($scheduleData as $day => $schedule) {
        $start_time = $schedule[0];
        $end_time = $schedule[1];
        $is_available = $schedule[2];

        mysqli_stmt_bind_param($insertStmt, "isssi", $doctor_id, $day, $start_time, $end_time, $is_available);

        if (!mysqli_stmt_execute($insertStmt)) {
            return [
                'success' => false,
                'error' => "Failed to set schedule for $day: " . mysqli_error($conn)
            ];
        }
    }

    return [
        'success' => true,
        'message' => 'Weekly schedule set successfully'
    ];
}

// Example: Add an availability exception (vacation, day off, etc.)
function addAvailabilityException($conn, $doctor_id, $date, $is_available, $notes = '')
{
    // Check if an exception already exists for this date
    $checkQuery = "SELECT id FROM doctor_availability_exceptions 
                  WHERE doctor_id = ? AND exception_date = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    // If exception exists, update it
    if (mysqli_num_rows($checkResult) > 0) {
        $row = mysqli_fetch_assoc($checkResult);
        $exceptionId = $row['id'];

        $updateQuery = "UPDATE doctor_availability_exceptions 
                       SET is_available = ?, notes = ? 
                       WHERE id = ?";
        $updateStmt = mysqli_prepare($conn, $updateQuery);
        mysqli_stmt_bind_param($updateStmt, "isi", $is_available, $notes, $exceptionId);

        if (!mysqli_stmt_execute($updateStmt)) {
            return [
                'success' => false,
                'error' => 'Failed to update exception: ' . mysqli_error($conn)
            ];
        }

        return [
            'success' => true,
            'message' => 'Exception updated successfully'
        ];
    }

    // Otherwise, insert a new exception
    $insertQuery = "INSERT INTO doctor_availability_exceptions 
                   (doctor_id, exception_date, is_available, notes) 
                   VALUES (?, ?, ?, ?)";
    $insertStmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($insertStmt, "isis", $doctor_id, $date, $is_available, $notes);

    if (!mysqli_stmt_execute($insertStmt)) {
        return [
            'success' => false,
            'error' => 'Failed to add exception: ' . mysqli_error($conn)
        ];
    }

    return [
        'success' => true,
        'message' => 'Exception added successfully',
        'exception_id' => mysqli_insert_id($conn)
    ];
}

// Example: Get doctor's availability for a specific date
function getDoctorAvailabilityForDate($conn, $doctor_id, $date)
{
    // First get the day of week for the date
    $dayOfWeek = date('l', strtotime($date)); // Returns Monday, Tuesday, etc.

    // Check if there's an exception for this date
    $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                      WHERE doctor_id = ? AND exception_date = ?";
    $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
    mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($exceptionStmt);
    $exceptionResult = mysqli_stmt_get_result($exceptionStmt);

    // If there's an exception, use that availability
    if (mysqli_num_rows($exceptionResult) > 0) {
        $exception = mysqli_fetch_assoc($exceptionResult);

        return [
            'date' => $date,
            'is_available' => (bool) $exception['is_available'],
            'start_time' => $exception['start_time'],
            'end_time' => $exception['end_time'],
            'notes' => $exception['notes'],
            'is_exception' => true
        ];
    }

    // Otherwise, use the regular schedule for that day
    $scheduleQuery = "SELECT * FROM doctor_schedule 
                     WHERE doctor_id = ? AND day_of_week = ?";
    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
    mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
    mysqli_stmt_execute($scheduleStmt);
    $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

    if (mysqli_num_rows($scheduleResult) > 0) {
        $schedule = mysqli_fetch_assoc($scheduleResult);

        return [
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'is_available' => (bool) $schedule['is_available'],
            'start_time' => $schedule['start_time'],
            'end_time' => $schedule['end_time'],
            'is_exception' => false
        ];
    }

    // If no schedule found
    return [
        'date' => $date,
        'is_available' => false,
        'error' => 'No schedule found for this day',
        'is_exception' => false
    ];
}

// Example: Get doctor's appointments for a date
function getDoctorAppointmentsForDate($conn, $doctor_id, $date)
{
    $query = "SELECT 
                a.appointment_id,
                a.patient_id,
                a.appointment_datetime,
                a.reason_for_visit,
                a.status,
                u.first_name,
                u.last_name
              FROM appointments a
              JOIN patients p ON a.patient_id = p.patient_id
              JOIN users u ON p.user_id = u.user_id
              WHERE a.doctor_id = ? 
              AND DATE(a.appointment_datetime) = ?
              ORDER BY a.appointment_datetime";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        return [
            'success' => false,
            'error' => 'Failed to fetch appointments: ' . mysqli_error($conn)
        ];
    }

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }

    return [
        'success' => true,
        'date' => $date,
        'appointments' => $appointments
    ];
}

// Example: Check if a doctor is available for a specific time slot
function isDoctorAvailableForTimeSlot($conn, $doctor_id, $date, $time)
{
    // Convert to datetime format
    $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // First check the daily schedule
    $availability = getDoctorAvailabilityForDate($conn, $doctor_id, $date);

    if (!isset($availability['is_available']) || !$availability['is_available']) {
        return [
            'available' => false,
            'reason' => 'Doctor is not scheduled to work on this day'
        ];
    }

    $timeOnly = date('H:i:s', strtotime($time));

    // Check if time is within working hours
    if ($timeOnly < $availability['start_time'] || $timeOnly > $availability['end_time']) {
        return [
            'available' => false,
            'reason' => 'Time is outside doctor\'s working hours'
        ];
    }

    // Check for existing appointments at the same time
    $query = "SELECT COUNT(*) as count FROM appointments 
              WHERE doctor_id = ? 
              AND appointment_datetime = ? 
              AND status != 'Cancelled'";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $datetime);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row['count'] > 0) {
        return [
            'available' => false,
            'reason' => 'Doctor already has an appointment at this time'
        ];
    }

    // If all checks pass, doctor is available
    return [
        'available' => true
    ];
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    $doctor_id = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    if ($doctor_id <= 0) {
        echo json_encode(['error' => 'Invalid doctor ID']);
        exit();
    }

    switch ($action) {
        case 'check_availability':
            $time = isset($_GET['time']) ? $_GET['time'] : '12:00:00';
            $result = isDoctorAvailableForTimeSlot($conn, $doctor_id, $date, $time);
            echo json_encode($result);
            break;

        case 'get_schedule':
            $result = getDoctorAvailabilityForDate($conn, $doctor_id, $date);
            echo json_encode($result);
            break;

        case 'get_appointments':
            $result = getDoctorAppointmentsForDate($conn, $doctor_id, $date);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
}
// Example for setting up a schedule or adding an exception
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $doctor_id = isset($_POST['doctor_id']) ? (int) $_POST['doctor_id'] : 0;

    if ($doctor_id <= 0) {
        echo json_encode(['error' => 'Invalid doctor ID']);
        exit();
    }

    switch ($action) {
        case 'setup_schedule':
            $result = setupWeeklySchedule($conn, $doctor_id);
            echo json_encode($result);
            break;

        case 'add_exception':
            $date = isset($_POST['date']) ? $_POST['date'] : '';
            $is_available = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 0;
            $notes = isset($_POST['notes']) ? $_POST['notes'] : '';

            if (empty($date)) {
                echo json_encode(['error' => 'Date is required']);
                exit();
            }

            $result = addAvailabilityException($conn, $doctor_id, $date, $is_available, $notes);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}