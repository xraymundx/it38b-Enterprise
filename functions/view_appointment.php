<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Get appointment details by ID with patient and doctor information
 * 
 * @param int $appointment_id The appointment ID to retrieve
 * @return array|false Array with appointment data or false on failure
 */
function get_appointment_by_id($appointment_id)
{
    global $conn;

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
                s.specialization_name
              FROM appointments a 
              JOIN patients p ON a.patient_id = p.patient_id 
              JOIN users pu ON p.user_id = pu.user_id
              JOIN doctors d ON a.doctor_id = d.doctor_id
              JOIN users du ON d.user_id = du.user_id
              JOIN specializations s ON d.specialization_id = s.specialization_id
              WHERE a.appointment_id = ?";

    try {
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            throw new Exception("Failed to get result: " . mysqli_error($conn));
        }

        if (mysqli_num_rows($result) == 0) {
            return false;
        }

        $appointment = mysqli_fetch_assoc($result);

        // Get related medical records (for future implementation)
        $appointment['medical_records'] = get_appointment_medical_records($appointment_id);

        // Get related billing records (for future implementation)
        $appointment['billing_records'] = get_appointment_billing_records($appointment_id);

        return $appointment;
    } catch (Exception $e) {
        error_log("Error in get_appointment_by_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all medical records related to an appointment
 * 
 * @param int $appointment_id The appointment ID
 * @return array Array of medical records
 */
function get_appointment_medical_records($appointment_id)
{
    global $conn;

    $records = [];

    try {
        // Simple query that focuses only on appointment_id
        $query = "SELECT m.*, 
                      DATE_FORMAT(m.record_datetime, '%M %d, %Y') AS formatted_date,
                      DATE_FORMAT(m.record_datetime, '%h:%i %p') AS formatted_time
                  FROM medicalrecords m
                  WHERE m.appointment_id = ?
                  ORDER BY m.record_datetime DESC";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }

        return $records;

    } catch (Exception $e) {
        error_log("Error fetching medical records: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all billing records related to an appointment
 * 
 * @param int $appointment_id The appointment ID
 * @return array Array of billing records
 */
function get_appointment_billing_records($appointment_id)
{
    global $conn;

    $records = [];

    try {
        // Simple query that focuses only on appointment_id
        $query = "SELECT b.*, 
                      DATE_FORMAT(b.billing_date, '%M %d, %Y') AS formatted_date
                  FROM billingrecords b
                  WHERE b.appointment_id = ?
                  ORDER BY b.billing_date DESC";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $appointment_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }

        return $records;

    } catch (Exception $e) {
        error_log("Error fetching billing records: " . $e->getMessage());
        return [];
    }
}

/**
 * Get appointment status class for styling
 * 
 * @param string $status The appointment status
 * @return string CSS class name for the status
 */
function get_appointment_status_class($status)
{
    $status_classes = [
        'Requested' => 'bg-yellow-100 text-yellow-800',
        'Scheduled' => 'bg-blue-100 text-blue-800',
        'Completed' => 'bg-green-100 text-green-800',
        'No Show' => 'bg-red-100 text-red-800',
        'Cancelled' => 'bg-gray-100 text-gray-800'
    ];

    return isset($status_classes[$status]) ? $status_classes[$status] : 'bg-gray-100 text-gray-800';
}

/**
 * Format appointment datetime for display
 * 
 * @param string $datetime The appointment datetime
 * @param string $format The format string (default: datetime)
 * @return string Formatted date/time
 */
function format_appointment_datetime($datetime, $format = 'datetime')
{
    $date = new DateTime($datetime);

    switch ($format) {
        case 'date':
            return $date->format('M d, Y');
        case 'time':
            return $date->format('h:i A');
        case 'datetime':
        default:
            return $date->format('M d, Y h:i A');
    }
}