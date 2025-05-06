<?php
// Include database connection
require_once '../config/config.php'; // Adjust the path if needed

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $patientId = intval($_GET['id']);

    // Process the deletion
    $query = "DELETE FROM patients WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patientId);

    if ($stmt->execute()) {
        // Deletion successful
        $stmt->close();
        $conn->close();
        header("Location: /it38b-Enterprise/index.php?page=patients&success=Patient deleted successfully");
        exit();
    } else {
        $stmt->close();
        $conn->close();
        // Error during deletion
        header("Location: /it38b-Enterprise/index.php?page=patients&error=Error deleting patient");
        exit();
    }
} else {
    $conn->close();
    // Invalid or missing ID
    header("Location: /it38b-Enterprise/index.php?page=patients&error=Invalid patient ID for deletion");
    exit();
}
?>