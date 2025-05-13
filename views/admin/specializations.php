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

// Fetch all specializations
$specializationsQuery = "SELECT * FROM specializations ORDER BY specialization_name";
$specializationsResult = mysqli_query($conn, $specializationsQuery);
$specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);

// Handle form submission for adding a new specialization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_specialization'])) {
    $specializationName = mysqli_real_escape_string($conn, $_POST['specialization_name']);

    if (empty($specializationName)) {
        $errorMessage = "Specialization name is required.";
    } else {
        $checkQuery = "SELECT specialization_name FROM specializations WHERE specialization_name = '$specializationName'";
        $checkResult = mysqli_query($conn, $checkQuery);
        if (mysqli_num_rows($checkResult) > 0) {
            $errorMessage = "Specialization '$specializationName' already exists.";
        } else {
            $insertQuery = "INSERT INTO specializations (specialization_name) VALUES ('$specializationName')";
            if (mysqli_query($conn, $insertQuery)) {
                $successMessage = "Specialization added successfully!";
                // Refresh specializations list
                $specializationsResult = mysqli_query($conn, $specializationsQuery);
                $specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);
            } else {
                $errorMessage = "Error adding specialization: " . mysqli_error($conn);
            }
        }
    }
}

// Handle deletion of a specialization
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);
    $checkDoctorQuery = "SELECT COUNT(*) AS count FROM doctors WHERE specialization_id = $deleteId";
    $checkDoctorResult = mysqli_query($conn, $checkDoctorQuery);
    $doctorCount = mysqli_fetch_assoc($checkDoctorResult)['count'];

    if ($doctorCount > 0) {
        $errorMessage = "Cannot delete specialization. It is currently assigned to $doctorCount doctor(s).";
    } else {
        $deleteQuery = "DELETE FROM specializations WHERE specialization_id = $deleteId";
        if (mysqli_query($conn, $deleteQuery)) {
            $successMessage = "Specialization deleted successfully!";
            // Refresh specializations list
            $specializationsResult = mysqli_query($conn, $specializationsQuery);
            $specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);
        } else {
            $errorMessage = "Error deleting specialization: " . mysqli_error($conn);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Specializations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Manage Specializations</h1>

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
            <button data-modal-target="add-specialization-modal" data-modal-toggle="add-specialization-modal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Add New Specialization
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
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($specializations as $specialization): ?>
                        <tr>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($specialization['specialization_id']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($specialization['specialization_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <a href="?page=edit_specialization&id=<?php echo $specialization['specialization_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2"><i class="fas fa-edit"></i> Edit</a>
                                <a href="?page=specializations&delete_id=<?php echo $specialization['specialization_id']; ?>"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to delete this specialization? This will fail if any doctors are currently assigned to it.');"><i
                                        class="fas fa-trash-alt"></i> Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-specialization-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add New Specialization
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="add-specialization-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_specialization" value="1">
                        <div>
                            <label for="specialization_name"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Specialization
                                Name</label>
                            <input type="text" name="specialization_name" id="specialization_name"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                placeholder="Enter specialization name" required>
                        </div>
                        <button type="submit"
                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Specialization
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Flowbite modals
        import { Modal } from 'flowbite';

        const $modalElement = document.getElementById('add-specialization-modal');
        const modal = new Modal($modalElement);
    </script>
</body>

</html>