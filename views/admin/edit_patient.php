<?php
// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is an admin or receptionist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || ($_SESSION['role'] !== 'administrator' && $_SESSION['role'] !== 'receptionist')) {
    header('Location: /login.php');
    exit();
}

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established properly. Please check your configuration.");
}

// Get the patient ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ?page=patients');
    exit();
}
$patientId = intval($_GET['id']);

// Fetch patient details and related information
$patientQuery = "SELECT
                    p.patient_id,
                    p.user_id,
                    u.username,
                    u.email,
                    u.first_name,
                    u.last_name,
                    u.phone_number AS contact_number,
                    p.date_of_birth,
                    p.gender,
                    pd.* -- Fetch all columns from patient_descriptions
                FROM patients p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                WHERE p.patient_id = $patientId";
$patientResult = mysqli_query($conn, $patientQuery);
$patient = mysqli_fetch_assoc($patientResult);

if (!$patient) {
    header('Location: ?page=patients&error=Patient not found');
    exit();
}

// Handle form submission for updating patient details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_patient'])) {
    $userId = intval($_POST['user_id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $dob = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $medicalRecordNumber = mysqli_real_escape_string($conn, $_POST['medical_record_number']);
    $insuranceProvider = mysqli_real_escape_string($conn, $_POST['insurance_provider']);
    $insurancePolicyNumber = mysqli_real_escape_string($conn, $_POST['insurance_policy_number']);
    $emergencyContactName = mysqli_real_escape_string($conn, $_POST['emergency_contact_name']);
    $emergencyContactPhone = mysqli_real_escape_string($conn, $_POST['emergency_contact_phone']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    // Validate inputs (add more robust validation as needed)
    if (empty($username) || empty($email) || empty($firstName) || empty($lastName) || empty($contactNumber) || empty($dob) || empty($gender)) {
        $errorMessage = "Basic patient information is required.";
    } else {
        // Begin transaction for data integrity
        mysqli_begin_transaction($conn);

        // Update user information
        $updateUserQuery = "UPDATE users SET
                            username = '$username',
                            email = '$email',
                            first_name = '$firstName',
                            last_name = '$lastName',
                            phone_number = '$contactNumber'
                            WHERE user_id = $userId";

        if (mysqli_query($conn, $updateUserQuery)) {
            // Update patient information
            $updatePatientQuery = "UPDATE patients SET
                                   date_of_birth = '$dob',
                                   gender = '$gender'
                                   WHERE patient_id = $patientId";

            if (mysqli_query($conn, $updatePatientQuery)) {
                // Update or insert patient description
                $checkDescriptionQuery = "SELECT id FROM patient_descriptions WHERE patient_id = $patientId";
                $descriptionResult = mysqli_query($conn, $checkDescriptionQuery);

                if (mysqli_num_rows($descriptionResult) > 0) {
                    $updateDescriptionQuery = "UPDATE patient_descriptions SET
                                               description = '$notes',
                                               address = '$address',
                                               gender = '$gender',
                                               medical_record_number = '$medicalRecordNumber',
                                               insurance_provider = '$insuranceProvider',
                                               insurance_policy_number = '$insurancePolicyNumber',
                                               emergency_contact_name = '$emergencyContactName',
                                               emergency_contact_phone = '$emergencyContactPhone',
                                               notes = '$notes',
                                               updated_at = CURRENT_TIMESTAMP
                                               WHERE patient_id = $patientId";
                    if (mysqli_query($conn, $updateDescriptionQuery)) {
                        mysqli_commit($conn);
                        $successMessage = "Patient information updated successfully!";
                        // Refresh patient data after update
                        $patientResult = mysqli_query($conn, $patientQuery);
                        $patient = mysqli_fetch_assoc($patientResult);
                    } else {
                        mysqli_rollback($conn);
                        $errorMessage = "Error updating patient description: " . mysqli_error($conn);
                    }
                } else {
                    $insertDescriptionQuery = "INSERT INTO patient_descriptions (patient_id, description, address, gender, medical_record_number, insurance_provider, insurance_policy_number, emergency_contact_name, emergency_contact_phone, notes)
                                               VALUES ($patientId, '$notes', '$address', '$gender', '$medicalRecordNumber', '$insuranceProvider', '$insurancePolicyNumber', '$emergencyContactName', '$emergencyContactPhone', '$notes')";
                    if (mysqli_query($conn, $insertDescriptionQuery)) {
                        mysqli_commit($conn);
                        $successMessage = "Patient information updated successfully!";
                        // Refresh patient data after update
                        $patientResult = mysqli_query($conn, $patientQuery);
                        $patient = mysqli_fetch_assoc($patientResult);
                    } else {
                        mysqli_rollback($conn);
                        $errorMessage = "Error inserting patient description: " . mysqli_error($conn);
                    }
                }
            } else {
                mysqli_rollback($conn);
                $errorMessage = "Error updating patient details: " . mysqli_error($conn);
            }
        } else {
            mysqli_rollback($conn);
            $errorMessage = "Error updating user information: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Patient</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Patient Details</h1>

        <?php if (isset($successMessage)): ?>
            <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-200 border-red-600 text-red-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form class="space-y-4" method="post" action="">
                <input type="hidden" name="update_patient" value="1">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($patient['user_id']); ?>">

                <div>
                    <label for="username"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
                    <input type="text" name="username" id="username"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['username']); ?>" required>
                </div>
                <div>
                    <label for="email"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                    <input type="email" name="email" id="email"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['email']); ?>" required>
                </div>
                <div>
                    <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First
                        Name</label>
                    <input type="text" name="first_name" id="first_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['first_name']); ?>" required>
                </div>
                <div>
                    <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last
                        Name</label>
                    <input type="text" name="last_name" id="last_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['last_name']); ?>" required>
                </div>
                <div>
                    <label for="contact_number"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact Number</label>
                    <input type="tel" name="contact_number" id="contact_number"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['contact_number']); ?>" required>
                </div>
                <div>
                    <label for="date_of_birth" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date
                        of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" required>
                </div>
                <div>
                    <label for="gender"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Gender</label>
                    <select name="gender" id="gender"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                        <option value="">Select Gender</option>
                        <option value="Male" <?php if ($patient['gender'] === 'Male')
                            echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if ($patient['gender'] === 'Female')
                            echo 'selected'; ?>>Female
                        </option>
                        <option value="Other" <?php if ($patient['gender'] === 'Other')
                            echo 'selected'; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="address"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address</label>
                    <textarea name="address" id="address" rows="3"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="medical_record_number"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Medical Record
                        Number</label>
                    <input type="text" name="medical_record_number" id="medical_record_number"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['medical_record_number'] ?? ''); ?>">
                </div>
                <div>
                    <label for="insurance_provider"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Insurance Provider</label>
                    <input type="text" name="insurance_provider" id="insurance_provider"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['insurance_provider'] ?? ''); ?>">
                </div>
                <div>
                    <label for="insurance_policy_number"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Insurance Policy
                        Number</label>
                    <input type="text" name="insurance_policy_number" id="insurance_policy_number"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['insurance_policy_number'] ?? ''); ?>">
                </div>
                <div>
                    <label for="emergency_contact_name"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Emergency Contact
                        Name</label>
                    <input type="text" name="emergency_contact_name" id="emergency_contact_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                </div>
                <div>
                    <label for="emergency_contact_phone"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Emergency Contact
                        Phone</label>
                    <input type="tel" name="emergency_contact_phone" id="emergency_contact_phone"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>">
                </div>
                <div>
                    <label for="notes"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"><?php echo htmlspecialchars($patient['notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Update Patient
                </button>
            </form>
            <div class="mt-4">
                <a href="?page=patients" class="text-gray-600 hover:text-gray-800">Back to Patients</a>
            </div>
        </div>
    </div>
</body>

</html>