<?php
require_once __DIR__ . '/../config/config.php';

// Check if required parameters are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Invalid+appointment+ID");
    exit();
}

$appointmentId = intval($_GET['id']);

// Check if appointment exists before deletion
$checkQuery = "SELECT appointment_id FROM appointments WHERE appointment_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $appointmentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Appointment+not+found");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete the appointment
    $deleteQuery = "DELETE FROM appointments WHERE appointment_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $appointmentId);

    if (!$deleteStmt->execute()) {
        throw new Exception("Failed to delete appointment: " . $conn->error);
    }

    // Check if any rows were affected
    if ($deleteStmt->affected_rows === 0) {
        throw new Exception("No appointment was deleted. It may have been removed already.");
    }

    // Commit the transaction
    $conn->commit();

    // Redirect back to appointments list with success message
    header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=appointments&status=all&success=Appointment+successfully+deleted");
    exit();

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();

    // Redirect with error message
    $errorMessage = urlencode($e->getMessage());
    header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=appointments&status=all&error={$errorMessage}");
    exit();
}