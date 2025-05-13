<?php

// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is an administrator
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header('Location: /login.php');
    exit();
}

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established properly. Please check your configuration.");
}

// Initialize variable for feedback
$errorMessage = '';
$successMessage = '';
$specializationData = null;

// --- Fetch Specialization Data for Editing ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editSpecializationId = intval($_GET['id']);
    $editSpecializationQuery = "SELECT specialization_id, specialization_name
                                FROM specializations
                                WHERE specialization_id = $editSpecializationId";
    $editSpecializationResult = mysqli_query($conn, $editSpecializationQuery);
    $specializationData = mysqli_fetch_assoc($editSpecializationResult);

    if (!$specializationData) {
        $errorMessage = "Specialization not found.";
    }
} else {
    $errorMessage = "Invalid or missing Specialization ID.";
}

// --- Handle Form Submission for Editing Specialization ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_specialization'])) {
    $specializationId = intval($_POST['edit_specialization_id']);
    $specializationName = mysqli_real_escape_string($conn, $_POST['specialization_name']);

    // Validate input
    if (empty($specializationName)) {
        $errorMessage = "Specialization name is required.";
    } else {
        // Check if the new name already exists (excluding the current one)
        $checkQuery = "SELECT specialization_name
                       FROM specializations
                       WHERE specialization_name = '$specializationName'
                       AND specialization_id != $specializationId";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $errorMessage = "Specialization '$specializationName' already exists.";
        } else {
            // Update specialization name
            $updateQuery = "UPDATE specializations
                            SET specialization_name = '$specializationName',
                                updated_at = NOW()
                            WHERE specialization_id = $specializationId";

            if (mysqli_query($conn, $updateQuery)) {
                $successMessage = "Specialization updated successfully!";

                // Refetch the updated specialization data
                $editSpecializationResult = mysqli_query($conn, "SELECT specialization_id, specialization_name
                                                                 FROM specializations
                                                                 WHERE specialization_id = $specializationId");
                $specializationData = mysqli_fetch_assoc($editSpecializationResult);
            } else {
                $errorMessage = "Error updating specialization: " . mysqli_error($conn);
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Specialization</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Specialization</h1>

        <p class="mb-4"><a href="?page=specializations" class="text-blue-500 hover:underline"><i
                    class="fas fa-arrow-left mr-2"></i> Back to Specializations</a></p>

        <?php if ($errorMessage): ?>
            <div class="bg-red-200 border-red-600 text-red-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="bg-green-200 border-green-600 text-green-600 px-4 py-3 rounded-md mb-4" role="alert">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($specializationData): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <form class="space-y-4" method="post" action="">
                    <input type="hidden" name="edit_specialization" value="1">
                    <input type="hidden" name="edit_specialization_id"
                        value="<?php echo htmlspecialchars($specializationData['specialization_id']); ?>">

                    <div>
                        <label for="specialization_name"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Specialization Name</label>
                        <input type="text" name="specialization_name" id="specialization_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($specializationData['specialization_name']); ?>" required>
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Save Changes
                    </button>
                </form>
            </div>
        <?php elseif (isset($_GET['id'])): ?>
            <p class="text-gray-600">Loading specialization information...</p>
        <?php else: ?>
            <p class="text-gray-600">No specialization selected for editing.</p>
        <?php endif; ?>

    </div>
</body>

</html>