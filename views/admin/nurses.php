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

// Fetch all nurses with their user information
$nursesQuery = "SELECT
                    n.nurse_id,
                    u.user_id,
                    u.first_name,
                    u.last_name,
                    u.username,
                    u.email,
                    u.phone_number AS contact_number
                FROM nurses n
                JOIN users u ON n.user_id = u.user_id
                ORDER BY u.last_name, u.first_name";
$nursesResult = mysqli_query($conn, $nursesQuery);
$nurses = mysqli_fetch_all($nursesResult, MYSQLI_ASSOC);

// Handle form submission for adding a new nurse
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_nurse'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // You'll need to handle password hashing securely
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $roleId = 3; // Assuming '3' is the role_id for 'nurse'

    // Validate inputs (add more robust validation as needed)
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($contactNumber)) {
        $errorMessage = "All fields are required.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the users table first
        $insertUserQuery = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone_number, role_id)
                            VALUES ('$username', '$email', '$passwordHash', '$firstName', '$lastName', '$contactNumber', $roleId)";

        if (mysqli_query($conn, $insertUserQuery)) {
            $newUserId = mysqli_insert_id($conn);

            // Insert nurse into the nurses table
            $insertNurseQuery = "INSERT INTO nurses (user_id)
                                   VALUES ($newUserId)";

            if (mysqli_query($conn, $insertNurseQuery)) {
                $successMessage = "Nurse added successfully!";
                // Refresh nurses list
                $nursesResult = mysqli_query($conn, $nursesQuery);
                $nurses = mysqli_fetch_all($nursesResult, MYSQLI_ASSOC);
            } else {
                $errorMessage = "Error adding nurse details: " . mysqli_error($conn);
                // Optionally delete the user if nurse insertion fails
                mysqli_query($conn, "DELETE FROM users WHERE user_id = $newUserId");
            }
        } else {
            $errorMessage = "Error adding user: " . mysqli_error($conn);
        }
    }
}

// Handle deletion of a nurse (same as before)
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteNurseId = intval($_GET['delete_id']);

    mysqli_begin_transaction($conn);

    $getUserQuery = "SELECT user_id FROM nurses WHERE nurse_id = $deleteNurseId";
    $userResult = mysqli_query($conn, $getUserQuery);
    if ($userResult && $user = mysqli_fetch_assoc($userResult)) {
        $userIdToDelete = $user['user_id'];

        $deleteNurseQuery = "DELETE FROM nurses WHERE nurse_id = $deleteNurseId";
        if (mysqli_query($conn, $deleteNurseQuery)) {
            $deleteUserQuery = "DELETE FROM users WHERE user_id = $userIdToDelete";
            if (mysqli_query($conn, $deleteUserQuery)) {
                mysqli_commit($conn);
                $successMessage = "Nurse removed successfully!";
                $nursesResult = mysqli_query($conn, $nursesQuery);
                $nurses = mysqli_fetch_all($nursesResult, MYSQLI_ASSOC);
            } else {
                mysqli_rollback($conn);
                $errorMessage = "Error deleting user: " . mysqli_error($conn);
            }
        } else {
            mysqli_rollback($conn);
            $errorMessage = "Error deleting nurse record: " . mysqli_error($conn);
        }
    } else {
        $errorMessage = "Nurse not found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Nurses</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Manage Nurses</h1>

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

        <div class="mb-6">
            <button data-modal-target="add-nurse-modal" data-modal-toggle="add-nurse-modal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Add New Nurse
            </button>
        </div>

        <div class="overflow-x-auto bg-white rounded-lg shadow-md">
            <table class="min-w-full leading-normal">
                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            ID
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Name
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Username
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Email
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Contact
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nurses as $nurse): ?>
                        <tr>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($nurse['nurse_id']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($nurse['first_name'] . ' ' . $nurse['last_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($nurse['username']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($nurse['email']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($nurse['contact_number']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <a href="?page=edit_nurse&id=<?php echo $nurse['nurse_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                <a href="?page=nurses&delete_id=<?php echo $nurse['nurse_id']; ?>"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to remove this nurse? This will also delete their user account.');">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-nurse-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add New Nurse
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="add-nurse-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_nurse" value="1">
                        <div>
                            <label for="first_name"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First Name</label>
                            <input type="text" name="first_name" id="first_name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="last_name"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last Name</label>
                            <input type="text" name="last_name" id="last_name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="username"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
                            <input type="text" name="username" id="username"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="email"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                            <input type="email" name="email" id="email"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="password"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Password</label>
                            <input type="password" name="password" id="password"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="contact_number"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact
                                Number</label>
                            <input type="tel" name="contact_number" id="contact_number"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <button type="submit"
                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Nurse
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>