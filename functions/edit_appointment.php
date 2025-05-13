<?php
require_once __DIR__ . '/../config/config.php';

/**
 * Update an existing appointment
 * 
 * @param array $data Appointment data to update
 * @return array Result with success status and messages
 */
function update_appointment($data)
{
    global $conn;

    // Validate required fields
    $required_fields = ['appointment_id', 'patient_id', 'doctor_id', 'date', 'time', 'reason', 'status'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return [
                'success' => false,
                'error' => "Missing required field: $field"
            ];
        }
    }

    $appointment_id = (int) $data['appointment_id'];
    $patient_id = (int) $data['patient_id'];
    $doctor_id = (int) $data['doctor_id'];
    $date = $data['date'];
    $time = $data['time'];
    $reason = $data['reason'];
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $status = $data['status'];

    // Combine date and time
    $appointment_datetime = date('Y-m-d H:i:s', strtotime("$date $time"));

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Check if appointment exists
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

        // Check if new time slot is available (if datetime changed)
        $oldAppointment = mysqli_fetch_assoc($checkResult);
        if ($oldAppointment['appointment_datetime'] != $appointment_datetime) {
            $checkTimeQuery = "SELECT COUNT(*) as count FROM appointments 
                              WHERE doctor_id = ? 
                              AND appointment_datetime = ? 
                              AND appointment_id != ? 
                              AND status NOT IN ('Cancelled', 'No Show')";

            $checkTimeStmt = mysqli_prepare($conn, $checkTimeQuery);

            if (!$checkTimeStmt) {
                throw new Exception("Failed to prepare time check statement: " . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($checkTimeStmt, "isi", $doctor_id, $appointment_datetime, $appointment_id);
            mysqli_stmt_execute($checkTimeStmt);
            $timeResult = mysqli_stmt_get_result($checkTimeStmt);
            $timeRow = mysqli_fetch_assoc($timeResult);

            if ($timeRow['count'] > 0) {
                throw new Exception("This time slot is already booked");
            }

            // Verify doctor availability for the new date
            $newDate = date('Y-m-d', strtotime($date));
            $dayOfWeek = date('l', strtotime($newDate));

            // Check exceptions first
            $exceptionQuery = "SELECT * FROM doctor_availability_exceptions 
                             WHERE doctor_id = ? AND exception_date = ?";
            $exceptionStmt = mysqli_prepare($conn, $exceptionQuery);
            mysqli_stmt_bind_param($exceptionStmt, "is", $doctor_id, $newDate);
            mysqli_stmt_execute($exceptionStmt);
            $exceptionResult = mysqli_stmt_get_result($exceptionStmt);

            if (mysqli_num_rows($exceptionResult) > 0) {
                $exception = mysqli_fetch_assoc($exceptionResult);
                if (!$exception['is_available']) {
                    throw new Exception("Doctor is not available on this date");
                }
            } else {
                // Check regular schedule
                $scheduleQuery = "SELECT * FROM doctor_schedule 
                                WHERE doctor_id = ? AND day_of_week = ?";
                $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);
                mysqli_stmt_bind_param($scheduleStmt, "is", $doctor_id, $dayOfWeek);
                mysqli_stmt_execute($scheduleStmt);
                $scheduleResult = mysqli_stmt_get_result($scheduleStmt);

                if (mysqli_num_rows($scheduleResult) === 0 || !mysqli_fetch_assoc($scheduleResult)['is_available']) {
                    throw new Exception("Doctor is not scheduled to work on this day");
                }
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

        return [
            'success' => true,
            'message' => 'Appointment updated successfully',
            'appointment_id' => $appointment_id
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
 * Add medical record to an appointment
 * 
 * @param array $data Medical record data
 * @return array Result with success status and messages
 */
function add_medical_record($data)
{
    global $conn;

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Validate required fields
        $required_fields = ['appointment_id', 'patient_id', 'doctor_id', 'diagnosis', 'treatment'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        $appointment_id = (int) $data['appointment_id'];
        $patient_id = (int) $data['patient_id'];
        $doctor_id = (int) $data['doctor_id'];
        $diagnosis = $data['diagnosis'];
        $treatment = $data['treatment'];
        $notes = isset($data['notes']) ? $data['notes'] : '';
        $prescribed_medications = isset($data['prescribed_medications']) ? $data['prescribed_medications'] : '';
        $test_results = isset($data['test_results']) ? $data['test_results'] : '';

        // First, check if the appointment exists
        $checkQuery = "SELECT status FROM appointments WHERE appointment_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $appointment_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($result) === 0) {
            return [
                'success' => false,
                'error' => "Appointment not found"
            ];
        }

        // Insert the medical record
        $current_date = date('Y-m-d H:i:s');
        $query = "INSERT INTO medicalrecords 
                 (appointment_id, patient_id, doctor_id, diagnosis, treatment, notes, prescribed_medications, test_results, record_datetime) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiissssss", $appointment_id, $patient_id, $doctor_id, $diagnosis, $treatment, $notes, $prescribed_medications, $test_results, $current_date);

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_error($conn);
            error_log("Error adding medical record: " . $error);
            throw new Exception("Failed to create medical record: " . $error);
        }

        $record_id = mysqli_insert_id($conn);
        error_log("Medical record added successfully. ID: " . $record_id . ", Appointment ID: " . $appointment_id);

        // If appointment status is not Completed, update it
        $appointmentStatus = mysqli_fetch_assoc($result)['status'];
        if ($appointmentStatus !== 'Completed') {
            $updateQuery = "UPDATE appointments SET status = 'Completed' WHERE appointment_id = ?";
            $updateStmt = mysqli_prepare($conn, $updateQuery);
            mysqli_stmt_bind_param($updateStmt, "i", $appointment_id);

            if (!mysqli_stmt_execute($updateStmt)) {
                throw new Exception("Failed to update appointment status: " . mysqli_error($conn));
            }
        }

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Medical record added successfully',
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
 * Add billing record to an appointment
 * 
 * @param array $data Billing record data
 * @return array Result with success status and messages
 */
function add_billing_record($data)
{
    global $conn;

    try {
        // Start transaction
        mysqli_begin_transaction($conn);

        // Validate required fields
        $required_fields = ['appointment_id', 'patient_id', 'description', 'amount'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Missing required field: $field"
                ];
            }
        }

        $appointment_id = (int) $data['appointment_id'];
        $patient_id = (int) $data['patient_id'];
        $description = $data['description'];
        $amount = (float) $data['amount'];
        $payment_status = isset($data['payment_status']) ? $data['payment_status'] : 'Pending';
        $payment_method = isset($data['payment_method']) ? $data['payment_method'] : null;
        $invoice_number = isset($data['invoice_number']) ? $data['invoice_number'] : null;
        $notes = isset($data['notes']) ? $data['notes'] : null;
        $record_id = isset($data['record_id']) ? (int) $data['record_id'] : null;

        // First, check if the appointment exists
        $checkQuery = "SELECT appointment_id FROM appointments WHERE appointment_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $appointment_id);
        mysqli_stmt_execute($checkStmt);
        $result = mysqli_stmt_get_result($checkStmt);

        if (mysqli_num_rows($result) === 0) {
            return [
                'success' => false,
                'error' => "Appointment not found"
            ];
        }

        // Insert the billing record
        $current_date = date('Y-m-d H:i:s');
        $query = "INSERT INTO billingrecords 
                 (appointment_id, patient_id, record_id, description, amount, payment_status, payment_method, invoice_number, notes, billing_date) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiisdsssss", $appointment_id, $patient_id, $record_id, $description, $amount, $payment_status, $payment_method, $invoice_number, $notes, $current_date);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create billing record: " . mysqli_error($conn));
        }

        $billing_id = mysqli_insert_id($conn);

        // Commit transaction
        mysqli_commit($conn);

        return [
            'success' => true,
            'message' => 'Billing record added successfully',
            'billing_id' => $billing_id
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