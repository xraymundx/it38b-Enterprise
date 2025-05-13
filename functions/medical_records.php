<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Get all medical records with patient and doctor information
 * 
 * @return array Array of medical records
 */
function get_all_medical_records()
{
    global $conn;

    $records = [];

    try {
        $query = "SELECT m.*, 
                     CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                     CONCAT(du.first_name, ' ', du.last_name) AS doctor_name,
                     DATE_FORMAT(m.record_datetime, '%M %d, %Y') AS formatted_date
                  FROM medicalrecords m
                  JOIN patients p ON m.patient_id = p.patient_id
                  JOIN users pu ON p.user_id = pu.user_id
                  JOIN doctors d ON m.doctor_id = d.doctor_id
                  JOIN users du ON d.user_id = du.user_id
                  ORDER BY m.record_datetime DESC";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            throw new Exception("Error fetching medical records: " . mysqli_error($conn));
        }

        while ($row = mysqli_fetch_assoc($result)) {
            $records[] = $row;
        }

        return $records;

    } catch (Exception $e) {
        error_log($e->getMessage());
        return [];
    }
}

/**
 * Get medical record by ID
 * 
 * @param int $record_id The record ID to retrieve
 * @return array|false Array with record data or false on failure
 */
function get_medical_record_by_id($record_id)
{
    global $conn;

    try {
        $query = "SELECT m.*,
                     CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                     CONCAT(du.first_name, ' ', du.last_name) AS doctor_name,
                     s.specialization_name,
                     DATE_FORMAT(m.record_datetime, '%M %d, %Y') AS formatted_date,
                     DATE_FORMAT(m.record_datetime, '%h:%i %p') AS formatted_time
                  FROM medicalrecords m
                  JOIN patients p ON m.patient_id = p.patient_id
                  JOIN users pu ON p.user_id = pu.user_id
                  JOIN doctors d ON m.doctor_id = d.doctor_id
                  JOIN users du ON d.user_id = du.user_id
                  JOIN specializations s ON d.specialization_id = s.specialization_id
                  WHERE m.record_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $record_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 0) {
            return false;
        }

        $record = mysqli_fetch_assoc($result);

        // Get related appointment if exists
        if ($record['appointment_id']) {
            $apptQuery = "SELECT a.*, 
                           DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') AS formatted_date,
                           DATE_FORMAT(a.appointment_datetime, '%h:%i %p') AS formatted_time
                        FROM appointments a
                        WHERE a.appointment_id = ?";

            $apptStmt = mysqli_prepare($conn, $apptQuery);
            mysqli_stmt_bind_param($apptStmt, "i", $record['appointment_id']);
            mysqli_stmt_execute($apptStmt);
            $apptResult = mysqli_stmt_get_result($apptStmt);

            if (mysqli_num_rows($apptResult) > 0) {
                $record['appointment'] = mysqli_fetch_assoc($apptResult);
            }
        }

        return $record;

    } catch (Exception $e) {
        error_log("Error in get_medical_record_by_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Update medical record
 * 
 * @param array $data Medical record data to update
 * @return array Result with success status and messages
 */
function update_medical_record($data)
{
    global $conn;

    // Validate required fields
    $required_fields = ['record_id', 'diagnosis', 'treatment'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'error' => "Missing required field: $field"
            ];
        }
    }

    $record_id = (int) $data['record_id'];
    $diagnosis = $data['diagnosis'];
    $treatment = $data['treatment'];
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $prescribed_medications = isset($data['prescribed_medications']) ? $data['prescribed_medications'] : '';
    $test_results = isset($data['test_results']) ? $data['test_results'] : '';

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Check if record exists
        $checkQuery = "SELECT * FROM medicalrecords WHERE record_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $record_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($result) === 0) {
            return [
                'success' => false,
                'error' => "Medical record not found"
            ];
        }

        // Update the record
        $query = "UPDATE medicalrecords 
                 SET diagnosis = ?, 
                     treatment = ?, 
                     notes = ?,
                     prescribed_medications = ?,
                     test_results = ?
                 WHERE record_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssi", $diagnosis, $treatment, $notes, $prescribed_medications, $test_results, $record_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update medical record: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Medical record updated successfully',
            'record_id' => $record_id
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
 * Delete medical record
 * 
 * @param int $record_id The record ID to delete
 * @return array Result with success status and messages
 */
function delete_medical_record($record_id)
{
    global $conn;

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Check if billing records reference this medical record
        $checkQuery = "SELECT COUNT(*) as count FROM billingrecords WHERE record_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $record_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);
        $row = mysqli_fetch_assoc($result);

        if ($row['count'] > 0) {
            return [
                'success' => false,
                'error' => "Cannot delete this medical record because it is referenced by one or more billing records."
            ];
        }

        // Delete the record
        $query = "DELETE FROM medicalrecords WHERE record_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $record_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete medical record: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Medical record deleted successfully'
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