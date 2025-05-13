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

// Fetch current admin user data
$adminId = $_SESSION['user_id'];
$adminQuery = "SELECT user_id, first_name, last_name, email FROM users WHERE user_id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);
$adminData = mysqli_fetch_assoc($adminResult);

if (!$adminData) {
    $errorMessage = "Error fetching admin user data.";
}

// Handle form submission for updating profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

    // Validate input
    if (empty($firstName) || empty($lastName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "All fields are required and the email must be valid.";
    } else {
        // Check if the new email already exists for another user
        $checkEmailQuery = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $adminId";
        $checkEmailResult = mysqli_query($conn, $checkEmailQuery);
        if (mysqli_num_rows($checkEmailResult) > 0) {
            $errorMessage = "This email address is already in use.";
        } else {
            // Update user data
            $updateQuery = "UPDATE users
                            SET first_name = '$firstName',
                                last_name = '$lastName',
                                email = '$email',
                                updated_at = NOW()
                            WHERE user_id = $adminId";

            if (mysqli_query($conn, $updateQuery)) {
                $successMessage = "Profile updated successfully!";
                // Refresh session data for first and last name
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                // Refetch updated admin data
                $adminResult = mysqli_query($conn, $adminQuery);
                $adminData = mysqli_fetch_assoc($adminResult);
            } else {
                $errorMessage = "Error updating profile: " . mysqli_error($conn);
            }
        }
    }
}

// Handle form submission for updating password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $currentPassword = mysqli_real_escape_string($conn, $_POST['current_password']);
    $newPassword = mysqli_real_escape_string($conn, $_POST['new_password']);
    $confirmPassword = mysqli_real_escape_string($conn, $_POST['confirm_password']);

    // Validate input
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errorMessage = "All password fields are required.";
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = "New password must be at least 6 characters long.";
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = "New password and confirm password do not match.";
    } else {
        // Verify current password
        $getPasswordQuery = "SELECT password FROM users WHERE user_id = $adminId";
        $getPasswordResult = mysqli_query($conn, $getPasswordQuery);
        $userData = mysqli_fetch_assoc($getPasswordResult);

        if ($userData && password_verify($currentPassword, $userData['password'])) {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update password in the database
            $updatePasswordQuery = "UPDATE users
                                    SET password = '$hashedPassword',
                                        updated_at = NOW()
                                    WHERE user_id = $adminId";

            if (mysqli_query($conn, $updatePasswordQuery)) {
                $successMessage = "Password updated successfully!";
            } else {
                $errorMessage = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $errorMessage = "Incorrect current password.";
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Admin Settings</h1>

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

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Profile Information</h2>
            <?php if ($adminData): ?>
                <form class="space-y-4" method="post" action="">
                    <input type="hidden" name="update_profile" value="1">
                    <div>
                        <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First
                            Name</label>
                        <input type="text" name="first_name" id="first_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($adminData['first_name']); ?>" required>
                    </div>
                    <div>
                        <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last
                            Name</label>
                        <input type="text" name="last_name" id="last_name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($adminData['last_name']); ?>" required>
                    </div>
                    <div>
                        <label for="email"
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                        <input type="email" name="email" id="email"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                            value="<?php echo htmlspecialchars($adminData['email']); ?>" required>
                    </div>
                    <button type="submit"
                        class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                        Update Profile
                    </button>
                </form>
            <?php else: ?>
                <p class="text-gray-600">Could not load profile information.</p>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Change Password</h2>
            <form class="space-y-4" method="post" action="">
                <input type="hidden" name="update_password" value="1">
                <div>
                    <label for="current_password"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Current Password</label>
                    <input type="password" name="current_password" id="current_password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                </div>
                <div>
                    <label for="new_password" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">New
                        Password</label>
                    <input type="password" name="new_password" id="new_password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                </div>
                <div>
                    <label for="confirm_password"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Confirm New
                        Password</label>
                    <input type="password" name="confirm_password" id="confirm_password"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                </div>
                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Change Password
                </button>
            </form>
        </div>

    </div>
</body>

</html>