<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Create a new appointment
 * 
 * @param array $data Appointment data
 * @return array Result with success status and messages
 */
function create_appointment($data)
{
    global $conn;

    // Validate required fields
    $required_fields = ['patient_id', 'doctor_id', 'date', 'time', 'reason'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'error' => "Missing required field: $field"
            ];
        }
    }

    $patient_id = (int) $data['patient_id'];
    $doctor_id = (int) $data['doctor_id'];
    $date = $data['date'];
    $time = $data['time'];
    $reason = $data['reason'];
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $status = isset($data['status']) ? $data['status'] : 'Scheduled'; // Default status

    // Combine date and time
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Validate patient exists
        $patientQuery = "SELECT * FROM patients WHERE patient_id = ?";
        $patientStmt = mysqli_prepare($conn, $patientQuery);
        mysqli_stmt_bind_param($patientStmt, "i", $patient_id);
        mysqli_stmt_execute($patientStmt);

        if (mysqli_num_rows(mysqli_stmt_get_result($patientStmt)) === 0) {
            throw new Exception("Patient not found");
        }

        // Validate doctor exists
        $doctorQuery = "SELECT * FROM doctors WHERE doctor_id = ?";
        $doctorStmt = mysqli_prepare($conn, $doctorQuery);
        mysqli_stmt_bind_param($doctorStmt, "i", $doctor_id);
        mysqli_stmt_execute($doctorStmt);

        if (mysqli_num_rows(mysqli_stmt_get_result($doctorStmt)) === 0) {
            throw new Exception("Doctor not found");
        }

        // Check doctor's availability on this date
        $appointmentDate = date('Y-m-d', strtotime($date));
        $dayOfWeek = date('l', strtotime($appointmentDate));

        // Check for exceptions first
        $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                         WHERE doctor_id = ? AND exception_date = ?";
        $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
        mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $appointmentDate);
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

            // Check if appointment time is within doctor's working hours
            $appointmentTime = date('H:i:s', strtotime($time));
            $startTime = $schedule['start_time'];
            $endTime = $schedule['end_time'];

            if ($appointmentTime < $startTime || $appointmentTime > $endTime) {
                throw new Exception("Appointment time is outside doctor's working hours ($startTime - $endTime)");
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
            throw new Exception("This time slot is already booked");
        }

        // Insert new appointment
        $insertQuery = "INSERT INTO appointments 
                       (patient_id, doctor_id, appointment_datetime, reason_for_visit, notes, status) 
                       VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = mysqli_prepare($conn, $insertQuery);

        if (!$insertStmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
        }

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
            throw new Exception("Failed to create appointment: " . mysqli_error($conn));
        }

        $appointment_id = mysqli_insert_id($conn);

        // Commit transaction
        mysqli_commit($conn);

        // Get the appointment details with patient and doctor names for return
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
        $appointmentResult = mysqli_stmt_get_result($fetchStmt);
        $appointment = mysqli_fetch_assoc($appointmentResult);

        return [
            'success' => true,
            'message' => 'Appointment scheduled successfully',
            'appointment' => $appointment
        ];

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get available appointment slots for a doctor on a specific date
 * 
 * @param int $doctor_id The doctor ID
 * @param string $date The date to check in Y-m-d format
 * @return array Available time slots or error
 */
function get_available_appointment_slots($doctor_id, $date)
{
    global $conn;

    try {
        // Validate params
        if (empty($doctor_id) || empty($date)) {
            throw new Exception("Doctor ID and date are required");
        }

        // Format date
        $formatted_date = date('Y-m-d', strtotime($date));
        $dayOfWeek = date('l', strtotime($formatted_date));

        // Check if doctor has an exception for this date
        $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                          WHERE doctor_id = ? AND exception_date = ?";
        $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
        mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $formatted_date);
        mysqli_stmt_execute($exceptionStmt);
        $exceptionResult = mysqli_stmt_get_result($exceptionStmt);

        if (mysqli_num_rows($exceptionResult) > 0) {
            $exception = mysqli_fetch_assoc($exceptionResult);

            // If doctor is marked as unavailable for this date
            if (!$exception['is_available']) {
                return [
                    'success' => false,
                    'error' => "Doctor is not available on this date: " .
                        ($exception['notes'] ? $exception['notes'] : 'Unavailable')
                ];
            }

            // If doctor has custom hours for this exception
            if ($exception['start_time'] && $exception['end_time']) {
                $startTime = strtotime($exception['start_time']);
                $endTime = strtotime($exception['end_time']);
            }
        }

        // If no exception with custom hours, get regular schedule
        if (!isset($startTime) || !isset($endTime)) {
            $scheduleQuery = "SELECT * FROM doctor_schedule 
                             WHERE doctor_id = ? AND day_of_week = ?";
            $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
            mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
            mysqli_stmt_execute($scheduleStmt);
            $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

            if (mysqli_num_rows($scheduleResult) === 0) {
                return [
                    'success' => false,
                    'error' => "Doctor does not have a schedule for this day"
                ];
            }

            $schedule = mysqli_fetch_assoc($scheduleResult);
            if (!$schedule['is_available']) {
                return [
                    'success' => false,
                    'error' => "Doctor is not scheduled to work on this day"
                ];
            }

            $startTime = strtotime($schedule['start_time']);
            $endTime = strtotime($schedule['end_time']);
        }

        // Generate time slots at 30-minute or hourly intervals
        $slots = [];
        $interval = 30 * 60; // 30 minutes in seconds

        for ($time = $startTime; $time < $endTime; $time += $interval) {
            $slots[] = date('H:i:s', $time);
        }

        // Get booked slots for this doctor and date
        $bookedQuery = "SELECT TIME(appointment_datetime) as time
                      FROM appointments 
                      WHERE doctor_id = ? 
                      AND DATE(appointment_datetime) = ?
                      AND status NOT IN ('Cancelled', 'No Show')";
        $bookedStmt = mysqli_prepare($conn, $bookedQuery);
        mysqli_stmt_bind_param($bookedStmt, "is", $doctor_id, $formatted_date);
        mysqli_stmt_execute($bookedStmt);
        $bookedResult = mysqli_stmt_get_result($bookedStmt);

        $bookedSlots = [];
        while ($row = mysqli_fetch_assoc($bookedResult)) {
            $bookedSlots[] = $row['time'];
        }

        // Filter out booked slots
        $availableSlots = array_filter($slots, function ($slot) use ($bookedSlots) {
            return !in_array($slot, $bookedSlots);
        });

        // Format slots for display (HH:MM AM/PM)
        $formattedSlots = [];
        foreach ($availableSlots as $slot) {
            $formattedSlots[] = date('h:i A', strtotime($slot));
        }

        return [
            'success' => true,
            'slots' => $formattedSlots
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}