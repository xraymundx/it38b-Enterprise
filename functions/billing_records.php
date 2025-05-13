<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Get all billing records with patient information
 * 
 * @return array Array of billing records
 */
function get_all_billing_records()
{
    global $conn;

    $records = [];

    try {
        $query = "SELECT b.*, 
                     CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                     DATE_FORMAT(b.billing_date, '%M %d, %Y') AS formatted_date
                  FROM billingrecords b
                  JOIN patients p ON b.patient_id = p.patient_id
                  JOIN users pu ON p.user_id = pu.user_id
                  ORDER BY b.billing_date DESC";

        $result = mysqli_query($conn, $query);

        if (!$result) {
            throw new Exception("Error fetching billing records: " . mysqli_error($conn));
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
 * Get billing record by ID
 * 
 * @param int $bill_id The billing record ID to retrieve
 * @return array|false Array with billing data or false on failure
 */
function get_billing_record_by_id($bill_id)
{
    global $conn;

    try {
        $query = "SELECT b.*,
                     CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                     pu.email AS patient_email,
                     DATE_FORMAT(b.billing_date, '%M %d, %Y') AS formatted_date
                  FROM billingrecords b
                  JOIN patients p ON b.patient_id = p.patient_id
                  JOIN users pu ON p.user_id = pu.user_id
                  WHERE b.bill_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $bill_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 0) {
            return false;
        }

        $record = mysqli_fetch_assoc($result);

        // Get related appointment if exists
        if ($record['appointment_id']) {
            $apptQuery = "SELECT a.*, 
                           CONCAT(pu.first_name, ' ', pu.last_name) AS patient_name,
                           CONCAT(du.first_name, ' ', du.last_name) AS doctor_name,
                           DATE_FORMAT(a.appointment_datetime, '%M %d, %Y') AS formatted_date,
                           DATE_FORMAT(a.appointment_datetime, '%h:%i %p') AS formatted_time
                        FROM appointments a
                        JOIN patients p ON a.patient_id = p.patient_id
                        JOIN users pu ON p.user_id = pu.user_id
                        JOIN doctors d ON a.doctor_id = d.doctor_id
                        JOIN users du ON d.user_id = du.user_id
                        WHERE a.appointment_id = ?";

            $apptStmt = mysqli_prepare($conn, $apptQuery);
            mysqli_stmt_bind_param($apptStmt, "i", $record['appointment_id']);
            mysqli_stmt_execute($apptStmt);
            $apptResult = mysqli_stmt_get_result($apptStmt);

            if (mysqli_num_rows($apptResult) > 0) {
                $record['appointment'] = mysqli_fetch_assoc($apptResult);
            }
        }

        // Get related medical record if exists
        if ($record['record_id']) {
            $mrQuery = "SELECT m.*, 
                        CONCAT(du.first_name, ' ', du.last_name) AS doctor_name,
                        DATE_FORMAT(m.record_datetime, '%M %d, %Y') AS formatted_date
                     FROM medicalrecords m
                     JOIN doctors d ON m.doctor_id = d.doctor_id
                     JOIN users du ON d.user_id = du.user_id
                     WHERE m.record_id = ?";

            $mrStmt = mysqli_prepare($conn, $mrQuery);
            mysqli_stmt_bind_param($mrStmt, "i", $record['record_id']);
            mysqli_stmt_execute($mrStmt);
            $mrResult = mysqli_stmt_get_result($mrStmt);

            if (mysqli_num_rows($mrResult) > 0) {
                $record['medical_record'] = mysqli_fetch_assoc($mrResult);
            }
        }

        return $record;

    } catch (Exception $e) {
        error_log("Error in get_billing_record_by_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Update billing record
 * 
 * @param array $data Billing record data to update
 * @return array Result with success status and messages
 */
function update_billing_record($data)
{
    global $conn;

    // Validate required fields
    $required_fields = ['bill_id', 'description', 'amount', 'payment_status'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'error' => "Missing required field: $field"
            ];
        }
    }

    $bill_id = (int) $data['bill_id'];
    $description = $data['description'];
    $amount = (float) $data['amount'];
    $payment_status = $data['payment_status'];
    $payment_method = isset($data['payment_method']) ? $data['payment_method'] : null;
    $payment_date = isset($data['payment_date']) && !empty($data['payment_date']) ? $data['payment_date'] : null;
    $invoice_number = isset($data['invoice_number']) ? $data['invoice_number'] : null;
    $notes = isset($data['notes']) ? $data['notes'] : null;

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Check if record exists
        $checkQuery = "SELECT * FROM billingrecords WHERE bill_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $bill_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($result) === 0) {
            return [
                'success' => false,
                'error' => "Billing record not found"
            ];
        }

        // Update the record
        $query = "UPDATE billingrecords 
                 SET description = ?, 
                     amount = ?, 
                     payment_status = ?,
                     payment_method = ?,
                     payment_date = ?,
                     invoice_number = ?,
                     notes = ?
                 WHERE bill_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sdsssssi", $description, $amount, $payment_status, $payment_method, $payment_date, $invoice_number, $notes, $bill_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to update billing record: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Billing record updated successfully',
            'bill_id' => $bill_id
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
 * Delete billing record
 * 
 * @param int $bill_id The billing record ID to delete
 * @return array Result with success status and messages
 */
function delete_billing_record($bill_id)
{
    global $conn;

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Delete the record
        $query = "DELETE FROM billingrecords WHERE bill_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $bill_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to delete billing record: " . mysqli_error($conn));
        }

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Billing record deleted successfully'
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