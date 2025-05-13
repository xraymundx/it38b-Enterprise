<?php
session_start();
require_once '../config/config.php';

// This example shows how to insert a new doctor into the database
// This would typically be used by an administrator

// Check authentication (assuming admin role is required)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Administrative access required']);
    exit();
}

// Get JSON data from POST request
$json_data = file_get_contents('php://input');
$doctorData = json_decode($json_data, true);

// If no data was received or not in POST request, use example data for demonstration
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$doctorData) {
    // Example form data (for demonstration only)
    $doctorData = [
        // User data
        'username' => 'drjohnsmith',
        'email' => 'john.smith@clinic.com',
        'password' => 'SecurePass123!', // Would be hashed before storage
        'first_name' => 'John',
        'last_name' => 'Smith',
        'phone_number' => '123-456-7890',

        // Doctor specific data
        'specialization_id' => 3, // Cardiology
        'address' => '123 Medical Center Drive, Cityville, ST 12345'
    ];
}

// Validate required fields
$required_fields = ['username', 'email', 'password', 'first_name', 'last_name', 'specialization_id'];
foreach ($required_fields as $field) {
    if (!isset($doctorData[$field]) || empty($doctorData[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Missing required field: $field"]);
        exit();
    }
}

// Always hash passwords before storage
$password_hash = password_hash($doctorData['password'], PASSWORD_DEFAULT);

// Start a transaction to ensure both user and doctor records are created or none are
mysqli_begin_transaction($conn);

try {
    // First, get the role_id for 'doctor'
    $roleQuery = "SELECT role_id FROM roles WHERE role_name = 'doctor'";
    $roleResult = mysqli_query($conn, $roleQuery);

    if (!$roleResult || mysqli_num_rows($roleResult) === 0) {
        throw new Exception("Doctor role not found in the database.");
    }

    $roleRow = mysqli_fetch_assoc($roleResult);
    $doctorRoleId = $roleRow['role_id'];

    // Check if specialization exists
    $specQuery = "SELECT specialization_id FROM specializations WHERE specialization_id = ?";
    $specStmt = mysqli_prepare($conn, $specQuery);
    mysqli_stmt_bind_param($specStmt, "i", $doctorData['specialization_id']);
    mysqli_stmt_execute($specStmt);
    $specResult = mysqli_stmt_get_result($specStmt);

    if (mysqli_num_rows($specResult) === 0) {
        throw new Exception("Selected specialization does not exist.");
    }

    // Check if username or email already exists
    $checkQuery = "SELECT user_id FROM users WHERE username = ? OR email = ?";
    $checkStmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($checkStmt, "ss", $doctorData['username'], $doctorData['email']);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);

    if (mysqli_num_rows($checkResult) > 0) {
        throw new Exception("Username or email already exists.");
    }

    // 1. Insert into users table first
    $userQuery = "INSERT INTO users (username, email, phone_number, password_hash, first_name, last_name, role_id) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";

    $userStmt = mysqli_prepare($conn, $userQuery);
    mysqli_stmt_bind_param(
        $userStmt,
        "ssssssi",
        $doctorData['username'],
        $doctorData['email'],
        $doctorData['phone_number'],
        $password_hash,
        $doctorData['first_name'],
        $doctorData['last_name'],
        $doctorRoleId
    );

    if (!mysqli_stmt_execute($userStmt)) {
        throw new Exception("Error creating user account: " . mysqli_error($conn));
    }

    // Get the newly created user_id
    $userId = mysqli_insert_id($conn);

    // 2. Now insert into doctors table
    $doctorQuery = "INSERT INTO doctors (user_id, specialization_id, address) VALUES (?, ?, ?)";
    $doctorStmt = mysqli_prepare($conn, $doctorQuery);

    // Use empty string if address is not provided
    $address = isset($doctorData['address']) ? $doctorData['address'] : '';

    mysqli_stmt_bind_param(
        $doctorStmt,
        "iis",
        $userId,
        $doctorData['specialization_id'],
        $address
    );

    if (!mysqli_stmt_execute($doctorStmt)) {
        throw new Exception("Error creating doctor record: " . mysqli_error($conn));
    }

    $doctorId = mysqli_insert_id($conn);

    // 3. Optionally, set up default schedule for the doctor
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $start_time = '09:00:00';
    $end_time = '17:00:00';

    $scheduleQuery = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time, is_available) 
                      VALUES (?, ?, ?, ?, 1)";
    $scheduleStmt = mysqli_prepare($conn, $scheduleQuery);

    foreach ($days as $day) {
        mysqli_stmt_bind_param($scheduleStmt, "isss", $doctorId, $day, $start_time, $end_time);
        mysqli_stmt_execute($scheduleStmt);
    }

    // If everything succeeded, commit the transaction
    mysqli_commit($conn);

    // Return success response with the created doctor information
    $response = [
        'success' => true,
        'message' => 'Doctor added successfully',
        'doctor' => [
            'doctor_id' => $doctorId,
            'user_id' => $userId,
            'name' => $doctorData['first_name'] . ' ' . $doctorData['last_name'],
            'email' => $doctorData['email'],
            'specialization_id' => $doctorData['specialization_id']
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    // If anything fails, roll back the transaction
    mysqli_rollback($conn);

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}