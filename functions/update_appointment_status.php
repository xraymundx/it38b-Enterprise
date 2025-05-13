<?php
require_once __DIR__ . '/../config/config.php';

// Check if required parameters are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status'])) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Invalid+parameters");
    exit();
}

$appointmentId = intval($_GET['id']);
$newStatus = $_GET['status'];

// Validate status
$validStatuses = ['Requested', 'Scheduled', 'Completed', 'No Show', 'Cancelled'];
if (!in_array($newStatus, $validStatuses)) {
    header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$appointmentId}&error=Invalid+status");
    exit();
}

// Check if appointment exists
$checkQuery = "SELECT * FROM appointments WHERE appointment_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $appointmentId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    header("Location: /it38b-Enterprise/views/nurse/appointments_all.php?error=Appointment+not+found");
    exit();
}

// Update appointment status
$updateQuery = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?";
$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("si", $newStatus, $appointmentId);

if ($updateStmt->execute()) {
    // Success - redirect to view page
    header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$appointmentId}&success=Status+updated+to+{$newStatus}");
    exit();
} else {
    // Error - redirect with error message
    $error = urlencode($conn->error);
    header("Location: /it38b-Enterprise/views/nurse/appointment_view.php?id={$appointmentId}&error=Failed+to+update+status:+{$error}");
    exit();
}