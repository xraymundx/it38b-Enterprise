<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - Not logged in']);
    exit();
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    // If a specific doctor ID is requested
    if ($id) {
        $query = "SELECT 
                    d.doctor_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone_number,
                    s.specialization_id,
                    s.specialization_name,
                    d.address,
                    d.created_at,
                    d.updated_at
                  FROM doctors d
                  JOIN users u ON d.user_id = u.user_id
                  JOIN specializations s ON d.specialization_id = s.specialization_id
                  WHERE d.doctor_id = ?";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch doctor: ' . mysqli_error($conn)
            ]);
            exit();
        }

        $doctor = mysqli_fetch_assoc($result);

        if (!$doctor) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Doctor not found'
            ]);
            exit();
        }

        echo json_encode([
            'success' => true,
            'doctor' => $doctor
        ]);
        exit();
    }

    // Fetch all doctors
    $query = "SELECT 
                d.doctor_id,
                u.user_id,
                u.first_name,
                u.last_name,
                u.email,
                u.phone_number,
                s.specialization_id,
                s.specialization_name,
                d.address,
                d.created_at
              FROM doctors d
              JOIN users u ON d.user_id = u.user_id
              JOIN specializations s ON d.specialization_id = s.specialization_id
              ORDER BY u.last_name, u.first_name";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch doctors: ' . mysqli_error($conn)
        ]);
        exit();
    }

    $doctors = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Format doctor name
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];
        $doctors[] = $row;
    }

    echo json_encode([
        'success' => true,
        'doctors' => $doctors
    ]);
}

// Handle DELETE request
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Only administrators can delete doctors
    if ($_SESSION['role'] !== 'administrator') {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden - Insufficient privileges']);
        exit();
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid doctor ID']);
        exit();
    }

    // Start a transaction
    mysqli_begin_transaction($conn);

    try {
        // Get user_id associated with doctor
        $userQuery = "SELECT user_id FROM doctors WHERE doctor_id = ?";
        $userStmt = mysqli_prepare($conn, $userQuery);
        mysqli_stmt_bind_param($userStmt, "i", $id);
        mysqli_stmt_execute($userStmt);
        $userResult = mysqli_stmt_get_result($userStmt);

        if (mysqli_num_rows($userResult) === 0) {
            throw new Exception("Doctor not found");
        }

        $userRow = mysqli_fetch_assoc($userResult);
        $userId = $userRow['user_id'];

        // Delete from doctors table first (cascading delete will remove schedule)
        $doctorQuery = "DELETE FROM doctors WHERE doctor_id = ?";
        $doctorStmt = mysqli_prepare($conn, $doctorQuery);
        mysqli_stmt_bind_param($doctorStmt, "i", $id);

        if (!mysqli_stmt_execute($doctorStmt)) {
            throw new Exception("Failed to delete doctor: " . mysqli_error($conn));
        }

        // Then delete from users table
        $userDelQuery = "DELETE FROM users WHERE user_id = ?";
        $userDelStmt = mysqli_prepare($conn, $userDelQuery);
        mysqli_stmt_bind_param($userDelStmt, "i", $userId);

        if (!mysqli_stmt_execute($userDelStmt)) {
            throw new Exception("Failed to delete user account: " . mysqli_error($conn));
        }

        // Commit the transaction
        mysqli_commit($conn);

        echo json_encode([
            'success' => true,
            'message' => 'Doctor deleted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        mysqli_rollback($conn);

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

// Handle invalid request method
else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}