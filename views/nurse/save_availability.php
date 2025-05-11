<?php
require 'config/config.php';
require 'config/functions.php';

if (isset($_POST['doctor_id_to_save']) && isset($_POST['selected_date'])) {
    $doctor_id_to_save = (int) $_POST['doctor_id_to_save'];
    $selected_date_to_save = $_POST['selected_date'];
    $new_availability = $_POST['availability'] ?? [];

    // Clear existing doctor availability for the selected date and doctor
    $stmt_delete = $conn->prepare("DELETE FROM doctor_availability WHERE doctor_id = ? AND availability_date = ?");
    $stmt_delete->bind_param("is", $doctor_id_to_save, $selected_date_to_save);
    $stmt_delete->execute();
    $stmt_delete->close();

    // Insert the new doctor availability
    if (!empty($new_availability)) {
        $stmt_insert = $conn->prepare("INSERT INTO doctor_availability (doctor_id, availability_date, start_time) VALUES (?, ?, ?)");
        foreach ($new_availability as $time) {
            $formatted_time = $time . ':00';
            $stmt_insert->bind_param("iss", $doctor_id_to_save, $selected_date_to_save, $formatted_time);
            $stmt_insert->execute();
        }
        $stmt_insert->close();
        echo "<p class='success-message'>Doctor availability saved successfully for doctor ID " . $doctor_id_to_save . " on " . date('Y-m-d', strtotime($selected_date_to_save)) . "</p>";
    } else {
        echo "<p class='success-message'>Doctor availability cleared for doctor ID " . $doctor_id_to_save . " on " . date('Y-m-d', strtotime($selected_date_to_save)) . "</p>";
    }
} else {
    echo "<p class='error-message'>Invalid request to save availability.</p>";
}
?>