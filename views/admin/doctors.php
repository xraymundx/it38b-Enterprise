<?php
// Include database connection
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'administrator') {
    header('Location: /login.php');
    exit();
}

// Ensure database connection is established
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection not established properly. Please check your configuration.");
}

// Fetch roles (excluding administrator if needed)
$rolesQuery = "SELECT role_id, role_name FROM roles WHERE role_name = 'doctor'";
$rolesResult = mysqli_query($conn, $rolesQuery);
$roles = mysqli_fetch_all($rolesResult, MYSQLI_ASSOC);

// Fetch specializations
$specializationsQuery = "SELECT specialization_id, specialization_name FROM specializations ORDER BY specialization_name";
$specializationsResult = mysqli_query($conn, $specializationsQuery);
$specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);

// Fetch all doctors with their user information and specialization
$doctorsQuery = "SELECT d.doctor_id, u.user_id, u.username, u.email, u.first_name, u.last_name, s.specialization_name
                 FROM doctors d
                 JOIN users u ON d.user_id = u.user_id
                 JOIN specializations s ON d.specialization_id = s.specialization_id
                 ORDER BY u.last_name, u.first_name";
$doctorsResult = mysqli_query($conn, $doctorsQuery);
$doctors = mysqli_fetch_all($doctorsResult, MYSQLI_ASSOC);

// Handle form submission for adding a new doctor
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $roleId = 2; // Assuming '2' is the role_id for 'doctor'
    $specializationId = isset($_POST['specialization_id']) ? intval($_POST['specialization_id']) : null;
    $availabilityTimes = $_POST['availability'] ?? [];

    // Validate inputs (add more robust validation as needed)
    if (empty($username) || empty($email) || empty($password) || empty($firstName) || empty($lastName) || !is_numeric($specializationId)) {
        $errorMessage = "All fields are required.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the users table
        $insertUserQuery = "INSERT INTO users (username, email, password_hash, first_name, last_name, role_id)
                            VALUES ('$username', '$email', '$passwordHash', '$firstName', '$lastName', $roleId)";

        if (mysqli_query($conn, $insertUserQuery)) {
            $newUserId = mysqli_insert_id($conn);

            // Insert doctor into the doctors table
            $insertDoctorQuery = "INSERT INTO doctors (user_id, specialization_id) VALUES ($newUserId, $specializationId)";
            if (mysqli_query($conn, $insertDoctorQuery)) {
                $newDoctorId = mysqli_insert_id($conn);

                // Insert availability times
                foreach ($availabilityTimes as $day => $times) {
                    if (is_array($times) && isset($times['start_time']) && isset($times['end_time']) && !empty($times['start_time']) && !empty($times['end_time'])) {
                        $startTime = mysqli_real_escape_string($conn, $times['start_time']);
                        $endTime = mysqli_real_escape_string($conn, $times['end_time']);
                        $insertAvailabilityQuery = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time)
                                                    VALUES ($newDoctorId, '$day', '$startTime', '$endTime')";
                        mysqli_query($conn, $insertAvailabilityQuery);
                    }
                }

                $successMessage = "Doctor added successfully!";
            } else {
                $errorMessage = "Error adding doctor details: " . mysqli_error($conn);
                // Optionally delete the user if doctor insertion fails
                mysqli_query($conn, "DELETE FROM users WHERE user_id = $newUserId");
            }
        } else {
            $errorMessage = "Error adding user: " . mysqli_error($conn);
        }
    }
}

// Handle deletion of a doctor
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deleteDoctorId = intval($_GET['delete_id']);

    // First, get the user_id associated with the doctor
    $getUserQuery = "SELECT user_id FROM doctors WHERE doctor_id = $deleteDoctorId";
    $userResult = mysqli_query($conn, $getUserQuery);
    if ($userResult && $user = mysqli_fetch_assoc($userResult)) {
        $userIdToDelete = $user['user_id'];

        // Begin transaction to ensure data integrity
        mysqli_begin_transaction($conn);

        // Delete from doctor_schedule
        $deleteScheduleQuery = "DELETE FROM doctor_schedule WHERE doctor_id = $deleteDoctorId";
        mysqli_query($conn, $deleteScheduleQuery);

        // Delete from doctors table
        $deleteDoctorQuery = "DELETE FROM doctors WHERE doctor_id = $deleteDoctorId";
        mysqli_query($conn, $deleteDoctorQuery);

        // Delete from users table
        $deleteUserQuery = "DELETE FROM users WHERE user_id = $userIdToDelete";
        if (mysqli_query($conn, $deleteUserQuery)) {
            mysqli_commit($conn);
            $successMessage = "Doctor deleted successfully!";
        } else {
            mysqli_rollback($conn);
            $errorMessage = "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        $errorMessage = "Doctor not found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Manage Doctors</h1>

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
            <button data-modal-target="add-doctor-modal" data-modal-toggle="add-doctor-modal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Add New Doctor
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
                            Email
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Specialization
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Availability
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($doctor['doctor_id']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($doctor['email']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($doctor['specialization_name']); ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php
                                $availabilityQuery = "SELECT day_of_week, start_time, end_time FROM doctor_schedule WHERE doctor_id = " . $doctor['doctor_id'];
                                $availabilityResult = mysqli_query($conn, $availabilityQuery);
                                if ($availabilityResult && mysqli_num_rows($availabilityResult) > 0) {
                                    while ($avail = mysqli_fetch_assoc($availabilityResult)) {
                                        echo '<span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs mr-1 mb-1">' .
                                            htmlspecialchars(ucfirst($avail['day_of_week'])) . ': ' .
                                            htmlspecialchars(date('h:i A', strtotime($avail['start_time']))) . ' - ' .
                                            htmlspecialchars(date('h:i A', strtotime($avail['end_time']))) .
                                            '</span>';
                                    }
                                } else {
                                    echo '<span class="text-gray-500 italic text-xs">No availability set</span>';
                                }
                                ?>
                            </td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <a href="?page=edit_doctor&id=<?php echo $doctor['doctor_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                <a href="?page=doctors&delete_id=<?php echo $doctor['doctor_id']; ?>"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to delete this doctor?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-doctor-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add New Doctor
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="add-doctor-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_doctor" value="1">
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
                            <input type="password" name="password" id="password" placeholder="••••••••"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
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
                            <label for="specialization_id"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Specialization</label>
                            <select name="specialization_id" id="specialization_id"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                                <option value="">Select Specialization</option>
                                <?php foreach ($specializations as $specialization): ?>
                                    <option value="<?php echo htmlspecialchars($specialization['specialization_id']); ?>">
                                        <?php echo htmlspecialchars($specialization['specialization_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Availability</label>
                            <div id="availability-fields">
                                <div class="flex space-x-4 mb-2">
                                    <select name="availability[monday][start_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Monday Start Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="availability[monday][end_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Monday End Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4 mb-2">
                                    <select name="availability[tuesday][start_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Tuesday Start Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="availability[tuesday][end_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Tuesday End Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4 mb-2">
                                    <select name="availability[wednesday][start_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Wednesday Start Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="availability[wednesday][end_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Wednesday End Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4 mb-2">
                                    <select name="availability[thursday][start_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Thursday Start Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="availability[thursday][end_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Thursday End Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="flex space-x-4 mb-2">
                                    <select name="availability[friday][start_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Friday Start Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="availability[friday][end_time]"
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                        <option value="">Friday End Time</option>
                                        <?php for ($i = 7; $i <= 18; $i++): ?>
                                            <option value="<?php echo sprintf('%02d:00', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:00', $i))); ?></option>
                                            <option value="<?php echo sprintf('%02d:30', $i); ?>">
                                                <?php echo date('h:i A', strtotime(sprintf('%02d:30', $i))); ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit"
                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Doctor
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript to handle dynamic availability fields (if needed)
        // For now, the basic structure for each day is included directly in the HTML.
        // You can enhance this with JavaScript to add/remove availability slots per day if required.
    </script>
</body>

</html>