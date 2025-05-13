<?php
// Include database connection
require_once '../config/config.php'; // Adjust the path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $patientId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING);
    $dob = filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);

    // Patient description fields
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $medicalRecordNumber = filter_input(INPUT_POST, 'medical_record_number', FILTER_SANITIZE_STRING);
    $insuranceProvider = filter_input(INPUT_POST, 'insurance_provider', FILTER_SANITIZE_STRING);
    $insurancePolicyNumber = filter_input(INPUT_POST, 'insurance_policy_number', FILTER_SANITIZE_STRING);
    $emergencyContactName = filter_input(INPUT_POST, 'emergency_contact_name', FILTER_SANITIZE_STRING);
    $emergencyContactPhone = filter_input(INPUT_POST, 'emergency_contact_phone', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Validation
    $errors = [];
    if (empty($firstName))
        $errors[] = "First name is required.";
    if (empty($lastName))
        $errors[] = "Last name is required.";
    if (empty($dob))
        $errors[] = "Date of birth is required.";
    if (empty($email))
        $errors[] = "Email is required.";
    if (!$email)
        $errors[] = "Invalid email format.";
    if (empty($phone))
        $errors[] = "Phone number is required.";
    if (empty($gender))
        $errors[] = "Gender is required.";

    if (empty($errors) && $patientId) {
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

            // Update the patients table
            $patientQuery = "UPDATE patients SET date_of_birth = ?, gender = ? WHERE patient_id = ?";
            $patientStmt = $conn->prepare($patientQuery);
            $patientStmt->bind_param("ssi", $dob, $gender, $patientId);
            $patientStmt->execute();
            $patientStmt->close();

            // Update the users table
            $userQuery = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ? WHERE user_id = ?";
            $userStmt = $conn->prepare($userQuery);
            $userStmt->bind_param("ssssi", $firstName, $lastName, $email, $phone, $userId);
            $userStmt->execute();
            $userStmt->close();

            // Check if patient_descriptions record exists
            $checkDescQuery = "SELECT id FROM patient_descriptions WHERE patient_id = ?";
            $checkDescStmt = $conn->prepare($checkDescQuery);
            $checkDescStmt->bind_param("i", $patientId);
            $checkDescStmt->execute();
            $checkDescResult = $checkDescStmt->get_result();
            $descriptionExists = $checkDescResult->num_rows > 0;
            $checkDescStmt->close();

            if ($descriptionExists) {
                // Update existing patient_descriptions record
                $updateDescQuery = "UPDATE patient_descriptions SET 
                    description = ?, 
                    address = ?, 
                    medical_record_number = ?, 
                    insurance_provider = ?, 
                    insurance_policy_number = ?, 
                    emergency_contact_name = ?, 
                    emergency_contact_phone = ?, 
                    notes = ? 
                    WHERE patient_id = ?";

                $updateDescStmt = $conn->prepare($updateDescQuery);
                $updateDescStmt->bind_param(
                    "ssssssssi",
                    $description,
                    $address,
                    $medicalRecordNumber,
                    $insuranceProvider,
                    $insurancePolicyNumber,
                    $emergencyContactName,
                    $emergencyContactPhone,
                    $notes,
                    $patientId
                );
                $updateDescStmt->execute();
                $updateDescStmt->close();
            } else {
                // Insert new patient_descriptions record
                $insertDescQuery = "INSERT INTO patient_descriptions (
                    patient_id, 
                    description, 
                    address, 
                    medical_record_number, 
                    insurance_provider, 
                    insurance_policy_number, 
                    emergency_contact_name, 
                    emergency_contact_phone, 
                    notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $insertDescStmt = $conn->prepare($insertDescQuery);
                $insertDescStmt->bind_param(
                    "issssssss",
                    $patientId,
                    $description,
                    $address,
                    $medicalRecordNumber,
                    $insuranceProvider,
                    $insurancePolicyNumber,
                    $emergencyContactName,
                    $emergencyContactPhone,
                    $notes
                );
                $insertDescStmt->execute();
                $insertDescStmt->close();
            }

            // Commit transaction
            $conn->commit();

            // Redirect with success message
            header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&success=Patient+updated+successfully");
            exit();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Error+updating+patient:+" . urlencode($e->getMessage()));
            exit();
        }
    } else {
        // Validation errors
        $errorMessage = implode("<br>", $errors);
        header("Location: /it38b-Enterprise/functions/edit_patient.php?id=" . $patientId . "&error=" . urlencode($errorMessage));
        exit();
    }
} else {
    // Display edit form
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $patientId = intval($_GET['id']);

        // Get patient data from database
        $query = "SELECT p.patient_id, p.date_of_birth, p.gender, u.user_id, u.first_name, u.last_name, u.email, u.phone_number,
                 pd.description, pd.address, pd.medical_record_number, pd.insurance_provider, pd.insurance_policy_number,
                 pd.emergency_contact_name, pd.emergency_contact_phone, pd.notes
                 FROM patients p
                 JOIN users u ON p.user_id = u.user_id
                 LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                 WHERE p.patient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $patient = $result->fetch_assoc();
        $stmt->close();

        if ($patient) {
            // Show edit form with patient data
            ?>
            <!DOCTYPE html>
            <html lang="en">

            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Edit Patient</title>
                <style>
                    body {
                        font-family: sans-serif;
                        background-color: #f4f6f8;
                        margin: 0;
                        padding: 20px;
                        box-sizing: border-box;
                    }

                    .container {
                        max-width: 800px;
                        margin: 0 auto;
                        background-color: #fff;
                        padding: 30px;
                        border-radius: 8px;
                        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
                    }

                    h1 {
                        color: #333;
                        margin-bottom: 20px;
                    }

                    .form-group {
                        margin-bottom: 15px;
                    }

                    .form-row {
                        display: flex;
                        flex-wrap: wrap;
                        margin: 0 -10px;
                    }

                    .form-col {
                        flex: 1;
                        padding: 0 10px;
                        min-width: 200px;
                    }

                    .section-title {
                        margin-top: 20px;
                        margin-bottom: 15px;
                        padding-bottom: 8px;
                        border-bottom: 1px solid #eee;
                        color: #333;
                    }

                    label {
                        display: block;
                        margin-bottom: 5px;
                        color: #555;
                        font-weight: bold;
                    }

                    input[type="text"],
                    input[type="email"],
                    input[type="date"],
                    select,
                    textarea {
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        box-sizing: border-box;
                        margin-bottom: 10px;
                    }

                    textarea {
                        min-height: 100px;
                        resize: vertical;
                    }

                    .actions {
                        margin-top: 20px;
                        text-align: right;
                    }

                    .actions button {
                        padding: 10px 15px;
                        border: none;
                        border-radius: 4px;
                        cursor: pointer;
                        font-size: 1em;
                        transition: background-color 0.3s ease;
                    }

                    .actions button.save {
                        background-color: #5cb85c;
                        color: white;
                        margin-left: 10px;
                    }

                    .actions button.save:hover {
                        background-color: #4cae4c;
                    }

                    .actions button.cancel {
                        background-color: #f0ad4e;
                        color: white;
                    }

                    .actions button.cancel:hover {
                        background-color: #eea236;
                    }

                    .error-message {
                        color: red;
                        margin-top: 10px;
                        margin-bottom: 15px;
                        padding: 10px;
                        background-color: #ffeeee;
                        border-radius: 4px;
                    }
                </style>
            </head>

            <body>
                <div class="container">
                    <h1>Edit Patient</h1>
                    <?php if (isset($_GET['error'])): ?>
                        <div class="error-message"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($patient['patient_id']); ?>">

                        <h3 class="section-title">Personal Information</h3>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="first_name">First Name:</label>
                                    <input type="text" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="last_name">Last Name:</label>
                                    <input type="text" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="dob">Date of Birth:</label>
                                    <input type="date" id="dob" name="dob"
                                        value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="gender">Gender:</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo ($patient['gender'] === 'Male') ? 'selected' : ''; ?>>Male
                                        </option>
                                        <option value="Female" <?php echo ($patient['gender'] === 'Female') ? 'selected' : ''; ?>>
                                            Female</option>
                                        <option value="Other" <?php echo ($patient['gender'] === 'Other') ? 'selected' : ''; ?>>Other
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h3 class="section-title">Contact Information</h3>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="email">Email:</label>
                                    <input type="email" id="email" name="email"
                                        value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="phone">Phone:</label>
                                    <input type="text" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($patient['phone_number']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="address">Address:</label>
                            <textarea id="address"
                                name="address"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                        </div>

                        <h3 class="section-title">Medical Information</h3>
                        <div class="form-group">
                            <label for="description">Medical Description:</label>
                            <textarea id="description"
                                name="description"><?php echo htmlspecialchars($patient['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="medical_record_number">Medical Record Number:</label>
                                    <input type="text" id="medical_record_number" name="medical_record_number"
                                        value="<?php echo htmlspecialchars($patient['medical_record_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <h3 class="section-title">Insurance Information</h3>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="insurance_provider">Insurance Provider:</label>
                                    <input type="text" id="insurance_provider" name="insurance_provider"
                                        value="<?php echo htmlspecialchars($patient['insurance_provider'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="insurance_policy_number">Insurance Policy Number:</label>
                                    <input type="text" id="insurance_policy_number" name="insurance_policy_number"
                                        value="<?php echo htmlspecialchars($patient['insurance_policy_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <h3 class="section-title">Emergency Contact</h3>
                        <div class="form-row">
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="emergency_contact_name">Emergency Contact Name:</label>
                                    <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-col">
                                <div class="form-group">
                                    <label for="emergency_contact_phone">Emergency Contact Phone:</label>
                                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone"
                                        value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">Additional Notes:</label>
                            <textarea id="notes" name="notes"><?php echo htmlspecialchars($patient['notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="actions">
                            <button type="button" class="cancel"
                                onclick="window.location.href='/it38b-Enterprise/routes/dashboard_router.php?page=patients'">Cancel</button>
                            <button type="submit" class="save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </body>

            </html>
            <?php
        } else {
            // Patient not found
            header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Patient+not+found");
            exit();
        }
    } else {
        // Invalid or missing ID
        header("Location: /it38b-Enterprise/routes/dashboard_router.php?page=patients&error=Invalid+patient+ID");
        exit();
    }
}

$conn->close();
?>