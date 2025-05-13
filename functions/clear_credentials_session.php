<?php
// Start or resume the session
session_start();

// Clear the new patient credentials from the session if they exist
if (isset($_SESSION['new_patient_credentials'])) {
    unset($_SESSION['new_patient_credentials']);
}

// Return a success response
header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>