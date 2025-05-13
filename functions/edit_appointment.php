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
 * This is a placeholder for future implementation
 * 
 * @param array $data Medical record data
 * @return array Result with success status and messages
 */
function add_medical_record($data)
{
    global $conn;

    // This is a placeholder for future implementation
    // Will validate data and insert into the medicalrecords table

    return [
        'success' => false,
        'error' => 'Medical record functionality is not yet implemented'
    ];

    /* Example of future implementation:
    
    try {
        // Validate required fields
        $required_fields = ['appointment_id', 'doctor_id', 'diagnosis', 'treatment'];
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
        
        // First, check if the appointment exists and is completed
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
        
        $appointment = mysqli_fetch_assoc($result);
        if ($appointment['status'] !== 'Completed') {
            return [
                'success' => false,
                'error' => "Cannot add medical record to an appointment that is not completed"
            ];
        }
        
        // Insert the medical record
        $query = "INSERT INTO medicalrecords 
                 (appointment_id, patient_id, doctor_id, diagnosis, treatment, notes) 
                 VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iiisss", $appointment_id, $patient_id, $doctor_id, $diagnosis, $treatment, $notes);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create medical record: " . mysqli_error($conn));
        }
        
        $record_id = mysqli_insert_id($conn);
        
        return [
            'success' => true,
            'message' => 'Medical record added successfully',
            'record_id' => $record_id
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    */
}

/**
 * Add billing record to an appointment
 * This is a placeholder for future implementation
 * 
 * @param array $data Billing record data
 * @return array Result with success status and messages
 */
function add_billing_record($data)
{
    global $conn;

    // This is a placeholder for future implementation
    // Will validate data and insert into the billingrecords table

    return [
        'success' => false,
        'error' => 'Billing record functionality is not yet implemented'
    ];

    /* Example of future implementation:
    
    try {
        // Validate required fields
        $required_fields = ['appointment_id', 'patient_id', 'amount', 'description'];
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
        $amount = (float) $data['amount'];
        $description = $data['description'];
        $payment_status = isset($data['payment_status']) ? $data['payment_status'] : 'Pending';
        
        // First, check if the appointment exists
        $checkQuery = "SELECT * FROM appointments WHERE appointment_id = ?";
        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "i", $appointment_id);
        mysqli_stmt_execute($checkStmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($checkStmt)) === 0) {
            return [
                'success' => false,
                'error' => "Appointment not found"
            ];
        }
        
        // Insert the billing record
        $query = "INSERT INTO billingrecords 
                 (appointment_id, patient_id, amount, description, payment_status) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "iidss", $appointment_id, $patient_id, $amount, $description, $payment_status);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create billing record: " . mysqli_error($conn));
        }
        
        $bill_id = mysqli_insert_id($conn);
        
        return [
            'success' => true,
            'message' => 'Billing record added successfully',
            'bill_id' => $bill_id
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    */
}