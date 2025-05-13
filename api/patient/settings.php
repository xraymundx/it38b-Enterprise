<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get database connection
require_once __DIR__ . '/../../config/config.php';
global $conn; // Use $conn as in the billing API

// Get user ID from session
$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'view'; // Default to 'view' for settings
$response = ['success' => false];

try {
    switch ($action) {
        case 'view':
            $query = "
                SELECT
                    p.patient_id, p.user_id, p.date_of_birth, p.gender,
                    pd.description, pd.address, pd.medical_record_number,
                    pd.insurance_provider, pd.insurance_policy_number,
                    pd.emergency_contact_name, pd.emergency_contact_phone,
                    pd.notes
                FROM patients p
                LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                WHERE p.user_id = ?
            ";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $patientData = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                if ($patientData) {
                    $response = ['success' => true, 'patient' => $patientData];
                } else {
                    throw new Exception('Patient settings not found');
                }
            } else {
                throw new Exception('Error preparing statement: ' . mysqli_error($conn));
            }
            break;

        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $patientId = null;
            $patientIdQuery = "SELECT patient_id FROM patients WHERE user_id = ?";
            $stmtId = mysqli_prepare($conn, $patientIdQuery);
            if ($stmtId) {
                mysqli_stmt_bind_param($stmtId, "i", $user_id);
                mysqli_stmt_execute($stmtId);
                $resultId = mysqli_stmt_get_result($stmtId);
                $rowId = mysqli_fetch_assoc($resultId);
                mysqli_stmt_close($stmtId);
                if ($rowId) {
                    $patientId = $rowId['patient_id'];
                } else {
                    throw new Exception('Patient ID not found for this user');
                }
            } else {
                throw new Exception('Error preparing statement (get patient ID): ' . mysqli_error($conn));
            }

            if ($patientId) {
                $postData = $_POST;

                // Update patients table
                $sqlPatient = "
                    UPDATE patients
                    SET date_of_birth = ?,
                        gender = ?
                    WHERE patient_id = ?
                ";
                $stmtPatient = mysqli_prepare($conn, $sqlPatient);
                if ($stmtPatient) {
                    mysqli_stmt_bind_param($stmtPatient, "ssi", $postData['date_of_birth'], $postData['gender'], $patientId);
                    if (!mysqli_stmt_execute($stmtPatient)) {
                        throw new Exception('Error updating patients table: ' . mysqli_stmt_error($stmtPatient));
                    }
                    mysqli_stmt_close($stmtPatient);
                } else {
                    throw new Exception('Error preparing statement (patients update): ' . mysqli_error($conn));
                }

                // Update/Insert into patient_descriptions table
                $sqlCheck = "SELECT COUNT(*) FROM patient_descriptions WHERE patient_id = ?";
                $stmtCheck = mysqli_prepare($conn, $sqlCheck);
                if ($stmtCheck) {
                    mysqli_stmt_bind_param($stmtCheck, "i", $patientId);
                    mysqli_stmt_execute($stmtCheck);
                    $resultCheck = mysqli_stmt_get_result($stmtCheck);
                    $descriptionExists = mysqli_fetch_row($resultCheck)[0];
                    mysqli_stmt_close($stmtCheck);

                    if ($descriptionExists) {
                        $sqlDescription = "
                            UPDATE patient_descriptions
                            SET description = ?, address = ?, gender = ?, medical_record_number = ?,
                                insurance_provider = ?, insurance_policy_number = ?,
                                emergency_contact_name = ?, emergency_contact_phone = ?, notes = ?
                            WHERE patient_id = ?
                        ";
                        $stmtDescription = mysqli_prepare($conn, $sqlDescription);
                        if ($stmtDescription) {
                            mysqli_stmt_bind_param(
                                $stmtDescription,
                                "sssssssssi",
                                $postData['description'],
                                $postData['address'],
                                $postData['gender'],
                                $postData['medical_record_number'],
                                $postData['insurance_provider'],
                                $postData['insurance_policy_number'],
                                $postData['emergency_contact_name'],
                                $postData['emergency_contact_phone'],
                                $postData['notes'],
                                $patientId
                            );
                            if (!mysqli_stmt_execute($stmtDescription)) {
                                throw new Exception('Error updating patient_descriptions: ' . mysqli_stmt_error($stmtDescription));
                            }
                            mysqli_stmt_close($stmtDescription);
                        } else {
                            throw new Exception('Error preparing statement (update descriptions): ' . mysqli_error($conn));
                        }
                    } else {
                        $sqlInsertDescription = "
                            INSERT INTO patient_descriptions (
                                patient_id, description, address, gender, medical_record_number,
                                insurance_provider, insurance_policy_number, emergency_contact_name,
                                emergency_contact_phone, notes
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ";
                        $stmtInsertDescription = mysqli_prepare($conn, $sqlInsertDescription);
                        if ($stmtInsertDescription) {
                            mysqli_stmt_bind_param(
                                $stmtInsertDescription,
                                "isssssssss",
                                $patientId,
                                $postData['description'],
                                $postData['address'],
                                $postData['gender'],
                                $postData['medical_record_number'],
                                $postData['insurance_provider'],
                                $postData['insurance_policy_number'],
                                $postData['emergency_contact_name'],
                                $postData['emergency_contact_phone'],
                                $postData['notes']
                            );
                            if (!mysqli_stmt_execute($stmtInsertDescription)) {
                                throw new Exception('Error inserting into patient_descriptions: ' . mysqli_stmt_error($stmtInsertDescription));
                            }
                            mysqli_stmt_close($stmtInsertDescription);
                        } else {
                            throw new Exception('Error preparing statement (insert descriptions): ' . mysqli_error($conn));
                        }
                    }
                    $response = ['success' => true, 'message' => 'Profile updated successfully'];
                }
            }
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response);