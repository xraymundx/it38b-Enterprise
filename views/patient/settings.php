<?php
require_once __DIR__ . '../../config/config.php';
require_once __DIR__ . '../../functions/auth_patient.php';

// Ensure patient is logged in
if (!is_patient_logged_in()) {
    header('Location: /it38b-Enterprise/login.php'); // Adjust login path as needed
    exit();
}

// Fetch patient data
$userId = $_SESSION['user_id'];
$patient = get_patient_details($userId); // You'll need to create this function

if (!$patient) {
    // Handle case where patient data is not found
    echo '<div class="p-4"><div class="bg-white rounded-lg shadow p-6"><p class="text-red-500">Patient data not found.</p></div></div>';
    exit();
}

// Handle form submission (if any)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updateSuccess = update_patient_profile($_POST, $patient['patient_id']); // You'll need to create this function

    if ($updateSuccess) {
        $successMessage = "Profile updated successfully!";
        // Optionally, refresh patient data here
        $patient = get_patient_details($userId);
    } else {
        $errorMessage = "Error updating profile. Please try again.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-md p-6 max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Patient Settings</h1>

            <?php if (isset($successMessage)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4"
                    role="alert">
                    <strong class="font-bold">Success!</strong>
                    <span class="block sm:inline"><?php echo $successMessage; ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"><?php echo $errorMessage; ?></span>
                </div>
            <?php endif; ?>

            <form method="post" class="grid grid-cols-1 gap-6">
                <div>
                    <label for="date_of_birth" class="block text-gray-700 text-sm font-bold mb-2">Date of Birth:</label>
                    <input type="date" id="date_of_birth" name="date_of_birth"
                        value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="gender" class="block text-gray-700 text-sm font-bold mb-2">Gender:</label>
                    <select id="gender" name="gender"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        <option value="">Select Gender</option>
                        <option value="Male" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Description:</label>
                    <textarea id="description" name="description"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($patient['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="address" class="block text-gray-700 text-sm font-bold mb-2">Address:</label>
                    <textarea id="address" name="address"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label for="medical_record_number" class="block text-gray-700 text-sm font-bold mb-2">Medical Record
                        Number:</label>
                    <input type="text" id="medical_record_number" name="medical_record_number"
                        value="<?php echo htmlspecialchars($patient['medical_record_number'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="insurance_provider" class="block text-gray-700 text-sm font-bold mb-2">Insurance
                        Provider:</label>
                    <input type="text" id="insurance_provider" name="insurance_provider"
                        value="<?php echo htmlspecialchars($patient['insurance_provider'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="insurance_policy_number" class="block text-gray-700 text-sm font-bold mb-2">Insurance
                        Policy Number:</label>
                    <input type="text" id="insurance_policy_number" name="insurance_policy_number"
                        value="<?php echo htmlspecialchars($patient['insurance_policy_number'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="emergency_contact_name" class="block text-gray-700 text-sm font-bold mb-2">Emergency
                        Contact Name:</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name"
                        value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="emergency_contact_phone" class="block text-gray-700 text-sm font-bold mb-2">Emergency
                        Contact Phone:</label>
                    <input type="text" id="emergency_contact_phone" name="emergency_contact_phone"
                        value="<?php echo htmlspecialchars($patient['emergency_contact_phone'] ?? ''); ?>"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                </div>

                <div>
                    <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">Notes:</label>
                    <textarea id="notes" name="notes"
                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($patient['notes'] ?? ''); ?></textarea>
                </div>

                <div>
                    <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Save Changes
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <a href="/it38b-Enterprise/routes/dashboard_router.php?page=dashboard"
                    class="text-blue-500 hover:underline">Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>

</html>