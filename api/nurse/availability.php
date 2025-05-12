<?php
require 'config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $date = mysqli_real_escape_string($conn, $data['date']);
    $timeSlots = $data['timeSlots'];
    $doctorId = mysqli_real_escape_string($conn, $data['doctorId']); // Get from dropdown selection

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete existing slots for this date
        $deleteQuery = "DELETE FROM doctor_availability WHERE doctor_id = $doctorId AND date = '$date'";
        mysqli_query($conn, $deleteQuery);

        // Insert new slots
        foreach ($timeSlots as $slot) {
            $escapedSlot = mysqli_real_escape_string($conn, $slot);
            $insertQuery = "INSERT INTO doctor_availability (doctor_id, date, time_slot) VALUES ($doctorId, '$date', '$escapedSlot')";
            mysqli_query($conn, $insertQuery);
        }

        mysqli_commit($conn);
        http_response_code(200);
        echo json_encode(['message' => 'Success']);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}