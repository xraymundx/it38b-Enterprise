<?php
session_start();
require_once '../config/config.php'; // Adjust path as needed

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Not logged in']);
    exit();
}

if ($_SESSION['role'] !== 'nurse') { // Example: Only nurses can access patients
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden - Insufficient role']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get all patients with their user information
    $query = "SELECT p.patient_id, u.first_name, u.last_name, u.email, u.phone_number, p.date_of_birth, p.gender
    FROM patients p
    JOIN users u ON p.user_id_fk = u.user_id
    ORDER BY u.last_name, u.first_name";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch patients']);
        exit();
    }

    $patients = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $patients[] = $row;
    }

    echo json_encode(['patients' => $patients]);
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}