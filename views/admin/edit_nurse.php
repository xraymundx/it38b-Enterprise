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

// Initialize variables for feedback
$errorMessage = '';
$successMessage = '';
$nurseData = null;

// --- Fetch Nurse Data for Editing ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $editNurseId = intval($_GET['id']);
    $editNurseQuery = "SELECT
                            n.nurse_id,
                            u.user_id,
                            u.first_name,
                            u.last_name,
                            u.username,
                            u.email,
                            u.phone_number AS contact_number
                        FROM nurses n
                        JOIN users u ON n.user_id = u.user_id
                        WHERE n.nurse_id = $editNurseId";
    $editNurseResult = mysqli_query($conn, $editNurseQuery);
    $nurseData = mysqli_fetch_assoc($editNurseResult);

    if (!$nurseData) {
        $errorMessage = "Nurse not found.";
    }
} else {
    $errorMessage = "Invalid or missing Nurse ID.";
}

// --- Handle Form Submission for Editing Nurse ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_nurse'])) {
    $nurseId = intval($_POST['edit_nurse_id']);
    $userId = intval($_POST['edit_user_id']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);

    // Validate inputs (add more robust validation as needed)
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($contactNumber)) {
        $errorMessage = "All fields are required.";
    } else {
        // Update user information in the users table
        $updateUserQuery = "UPDATE users
                            SET first_name = '$firstName',
                                last_name = '$lastName',
                                username = '$username',
                                email = '$email',
                                phone_number = '$contactNumber',
                                updated_at = NOW()
                            WHERE user_id = $userId";

        if (mysqli_query($conn, $updateUserQuery)) {
            $successMessage = "Nurse information updated successfully!";

            // Refetch the updated nurse data
            $editNurseResult = mysqli_query($conn, "SELECT
                                                        n.nurse_id,
                                                        u.user_id,
                                                        u.first_name,
                                                        u.last_name,
                                                        u.username,
                                                        u.email,
                                                        u.phone_number AS contact_number
                                                    FROM nurses n
                                                    JOIN users u ON n.user_id = u.user_id
                                                    WHERE n.nurse_id = $nurseId");
            $nurseData = mysqli_fetch_assoc($editNurseResult);

        } else {
            $errorMessage = "Error updating nurse information: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Nurse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Nurse</h1>

        <p class="mb-4"><a href="?page=nurses" class="text-blue-500 hover:underline"><i
                    class="fas fa-arrow-left mr-2"></i> Back to Nurses</a></p>

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

        <?php if ($nurseData): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <form class="space-y-4" method="post" action="">
                    <input type="hidden" name="edit_nurse" value="1">
                    <input type="hidden" name="edit_nurse_id"
                        value="<?php echo htmlspecialchars($nurseData['nurse_id']); ?>">
                    <input type="hidden" name="edit_user_id" value="<?php echo htmlspecialchars($nurseData['user_id']); ?>">

                    <div>
                        <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First
                            Name</label>
                        <input type="text" name="first_name" id="first_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($nurseData['first_name']); ?>" required>
                    </div>
                    <div>
                        <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last
                            Name</label>
                        <input type="text" name="last_name" id="last_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($nurseData['last_name']); ?>" required>
                    </div>
                    <div>
                        <label for="username"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
                        <input type="text" name="username" id="username"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($nurseData['username']); ?>" required>
                    </div>
                    <div>
                        <label for="email"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                        <input type="email" name="email" id="email"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($nurseData['email']); ?>" required>
                    </div>
                    <div>
                        <label for="contact_number"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact Number</label>
                        <input type="tel" name="contact_number" id="contact_number"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($nurseData['contact_number']); ?>" required>
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Save Changes
                    </button>
                </form>
            </div>
        <?php elseif (isset($_GET['id'])): ?>
            <p class="text-gray-600">Loading nurse information...</p>
        <?php else: ?>
            <p class="text-gray-600">No nurse selected for editing.</p>
        <?php endif; ?>

    </div>
</body>

</html>