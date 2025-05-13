<?php
require_once __DIR__ . '/../config/config.php';

// Function to get appointments for a specific month
function getAppointmentsForMonth($year, $month)
{
    global $conn;

    try {
        $query = "SELECT 
                    a.id,
                    a.appointment_datetime,
                    a.status,
                    p.first_name as patient_first_name,
                    p.last_name as patient_last_name,
                    d.first_name as doctor_first_name,
                    d.last_name as doctor_last_name,
                    a.reason_for_visit
                 FROM appointments a
                 JOIN patients p ON a.patient_id = p.patient_id
                 JOIN doctors d ON a.doctor_id = d.doctor_id
                 WHERE YEAR(a.appointment_datetime) = ?
                 AND MONTH(a.appointment_datetime) = ?
                 ORDER BY a.appointment_datetime ASC";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "ii", $year, $month);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }

        $result = mysqli_stmt_get_result($stmt);
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }

        $appointments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $date = date('Y-m-d', strtotime($row['appointment_datetime']));
            if (!isset($appointments[$date])) {
                $appointments[$date] = [];
            }
            $appointments[$date][] = [
                'id' => $row['id'],
                'time' => date('H:i', strtotime($row['appointment_datetime'])),
                'patient_name' => $row['patient_first_name'] . ' ' . $row['patient_last_name'],
                'doctor_name' => $row['doctor_first_name'] . ' ' . $row['doctor_last_name'],
                'reason' => $row['reason_for_visit'],
                'status' => $row['status']
            ];
        }

        mysqli_stmt_close($stmt);
        return $appointments;
    } catch (Exception $e) {
        error_log("Error fetching appointments for month: " . $e->getMessage());
        return false;
    }
}

// Function to get appointments for a specific date
function getAppointmentsForDate($date)
{
    global $conn;

    try {
        $query = "SELECT
                    a.id,
                    a.appointment_datetime,
                    a.status,
                    p.first_name as patient_first_name,
                    p.last_name as patient_last_name,
                    d.first_name as doctor_first_name,
                    d.last_name as doctor_last_name,
                    a.reason_for_visit,
                    TIME(a.appointment_datetime) as time -- Include the time
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN doctors d ON a.doctor_id = d.doctor_id
                WHERE DATE(a.appointment_datetime) = ?
                ORDER BY a.appointment_datetime ASC";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "s", $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $appointments = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $appointments[] = [
                'id' => $row['id'],
                'time' => $row['time'], // Use the time directly from the query
                'patient_name' => $row['patient_first_name'] . ' ' . $row['patient_last_name'],
                'doctor_name' => $row['doctor_first_name'] . ' ' . $row['doctor_last_name'],
                'reason' => $row['reason_for_visit'],
                'status' => $row['status']
            ];
        }

        mysqli_stmt_close($stmt);
        return $appointments;
    } catch (Exception $e) {
        error_log("Error fetching appointments for date: " . $e->getMessage());
        return false;
    }
}

// Function to get appointment statistics
function getAppointmentCalendarStats()
{
    global $conn;

    try {
        $query = "SELECT
COUNT(*) as total,
SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM appointments
WHERE appointment_datetime >= CURDATE()";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $stats = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        return $stats;
    } catch (Exception $e) {
        error_log("Error fetching appointment statistics: " . $e->getMessage());
        return false;
    }
}

// Function to check if a time slot is available
function isTimeSlotAvailable($doctor_id, $date, $time)
{
    global $conn;

    try {
        $datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

        $query = "SELECT COUNT(*) as count
FROM appointments
WHERE doctor_id = ?
AND appointment_datetime = ?
AND status != 'cancelled'";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "is", $doctor_id, $datetime);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        return $row['count'] === 0;
    } catch (Exception $e) {
        error_log("Error checking time slot availability: " . $e->getMessage());
        return false;
    }
}

// Function to get available time slots for a date
function getAvailableTimeSlots($doctor_id, $date)
{
    global $conn;

    try {
        // Get doctor's working hours
        $query = "SELECT working_hours_start, working_hours_end
FROM doctors
WHERE doctor_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $doctor_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $doctor = mysqli_fetch_assoc($result);

        if (!$doctor) {
            throw new Exception("Doctor not found");
        }

        // Get booked appointments for the date
        $query = "SELECT TIME(appointment_datetime) as time
FROM appointments
WHERE doctor_id = ?
AND DATE(appointment_datetime) = ?
AND status != 'cancelled'";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "is", $doctor_id, $date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $booked_times = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $booked_times[] = $row['time'];
        }

        // Generate available time slots
        $start = strtotime($doctor['working_hours_start']);
        $end = strtotime($doctor['working_hours_end']);
        $interval = 30 * 60; // 30 minutes in seconds

        $available_slots = [];
        for ($time = $start; $time < $end; $time += $interval) {
            $time_slot = date('H:i:s', $time);
            if (
                !in_array(
                    $time_slot,
                    $booked_times
                )
            ) {
                $available_slots[] = $time_slot;
            }
        }
        return $available_slots;
    } catch (Exception $e) {
        error_log("Error getting available time slots: " . $e->getMessage());
        return false;
    }
}