<?php
// functions/add_patient.php
// Start or resume the session
session_start();


require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form data
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);

    // Generate a username based on first name and last name
    $username = strtolower($firstName . '.' . $lastName);

    // Generate a random password
    $password = bin2hex(random_bytes(5)); // 10 characters
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $errors = [];

    // Validation
    if (empty($firstName))
        $errors[] = "First name is required";
    if (empty($lastName))
        $errors[] = "Last name is required";
    if (empty($dob))
        $errors[] = "Date of birth is required";
    if (empty($email))
        $errors[] = "Email is required";
    if (empty($phone))
        $errors[] = "Phone number is required";

    if (empty($errors)) {
        // Check if email already exists
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            // Email already exists
            header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Email address already registered');
            $checkStmt->close();
            exit();
        } else {
            $checkStmt->close();

            // Begin transaction
            $conn->begin_transaction();

            try {
                // Get patient role ID
                $roleStmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = 'patient'");
                $roleStmt->execute();
                $roleResult = $roleStmt->get_result();
                $roleData = $roleResult->fetch_assoc();
                $roleStmt->close();

                if (!$roleData) {
                    throw new Exception("Patient role not found in database");
                }

                $patientRoleId = $roleData['role_id'];

                // Insert into users table first
                $userStmt = $conn->prepare("INSERT INTO users (username, email, phone_number, password_hash, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $userStmt->bind_param("ssssssi", $username, $email, $phone, $passwordHash, $firstName, $lastName, $patientRoleId);
                $userStmt->execute();
                $userId = $conn->insert_id;
                $userStmt->close();

                // Then insert into patients table
                $patientStmt = $conn->prepare("INSERT INTO patients (user_id, date_of_birth, gender) VALUES (?, ?, ?)");
                $patientStmt->bind_param("iss", $userId, $dob, $gender);
                $patientStmt->execute();
                $patientId = $conn->insert_id;
                $patientStmt->close();

                // Commit the transaction
                $conn->commit();

                // Store credentials in session for display
                $_SESSION['new_patient_credentials'] = [
                    'username' => $username,
                    'password' => $password,
                    'name' => $firstName . ' ' . $lastName
                ];

                // Redirect with success message
                header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&success=Patient+added+successfully');
            } catch (Exception $e) {
                // Rollback the transaction on error
                $conn->rollback();
                header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Failed to add patient: ' . urlencode($e->getMessage()));
            }
        }
    } else {
        // Validation errors
        $errorString = implode('<br>', $errors);
        header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Validation Error:<br>' . urlencode($errorString));
    }
    $conn->close();
    exit();
} else {
    // Not a POST request
    header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients');
    $conn->close();
    exit();
}
?>