<?php
require_once __DIR__ . '/../config/config.php';

// Check if ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $patientId = intval($_GET['id']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get the user_id for this patient
        $getUserIdQuery = "SELECT user_id FROM patients WHERE patient_id = ?";
        $userIdStmt = $conn->prepare($getUserIdQuery);
        $userIdStmt->bind_param("i", $patientId);
        $userIdStmt->execute();
        $userIdResult = $userIdStmt->get_result();
        $userData = $userIdResult->fetch_assoc();
        $userIdStmt->close();

        if (!$userData) {
            throw new Exception("Patient record not found");
        }

        $userId = $userData['user_id'];

        // Delete patient description if it exists
        $descStmt = $conn->prepare("DELETE FROM patient_descriptions WHERE patient_id = ?");
        $descStmt->bind_param("i", $patientId);
        $descStmt->execute();
        $descStmt->close();

        // Delete appointments related to this patient
        $apptStmt = $conn->prepare("DELETE FROM appointments WHERE patient_id = ?");
        $apptStmt->bind_param("i", $patientId);
        $apptStmt->execute();
        $apptStmt->close();

        // Delete medical records related to this patient
        $medStmt = $conn->prepare("DELETE FROM medicalrecords WHERE patient_id = ?");
        $medStmt->bind_param("i", $patientId);
        $medStmt->execute();
        $medStmt->close();

        // Delete billing records related to this patient
        $billStmt = $conn->prepare("DELETE FROM billingrecords WHERE patient_id = ?");
        $billStmt->bind_param("i", $patientId);
        $billStmt->execute();
        $billStmt->close();

        // Delete from patients table
        $patientStmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
        $patientStmt->bind_param("i", $patientId);
        $patientStmt->execute();
        $patientStmt->close();

        // Finally delete from users table
        $userStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $userStmt->close();

        // Commit transaction
        $conn->commit();

        // Redirect with success message
        header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&success=Patient deleted successfully');
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Error deleting patient: ' . urlencode($e->getMessage()));
    }
} else {
    // Invalid ID
    header('Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Invalid patient ID');
}

$conn->close();
exit();
?>