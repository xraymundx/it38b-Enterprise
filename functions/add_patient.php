<?php
// functions/add_patient.php

require_once '../config/config.php'; // Adjust the path to config.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form data (as before)
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);

    $errors = [];

    // (Validation checks as before)

    if (empty($errors)) {
        // Check if the email already exists
        $checkStmt = $conn->prepare("SELECT id FROM patients WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // Email already exists, add an error
            header('Location: ../?page=patients&error=Error: This email address is already registered.');
            $checkStmt->close();
            exit();
        } else {
            $checkStmt->close();

            // Prepare and execute the SQL statement to insert the new patient
            $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, email, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $firstName, $lastName, $dob, $email, $phone);

            if ($stmt->execute()) {
                // Redirect back to the patients page with success message
                header('Location: ../?page=patients&success=Patient added successfully');
            } else {
                // Redirect back with error message
                header('Location: ../?page=patients&error=Failed to add patient: ' . $stmt->error);
            }
            $stmt->close();
        }
    } else {
        // Redirect back with validation errors
        $errorString = implode('<br>', $errors);
        header('Location: ../?page=patients&error=Validation Error:<br>' . urlencode($errorString));
    }
    $conn->close(); // Close the connection before exiting
    exit();

} else {
    // If the request method is not POST, redirect back to the patients page
    $conn->close(); // Close the connection before exiting
    header('Location: ../?page=patients');
    exit();
}
?>