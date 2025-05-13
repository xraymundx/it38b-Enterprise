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

// Get the doctor ID from the query string
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ?page=doctors');
    exit();
}
$doctorId = intval($_GET['id']);

// Fetch doctor details
$doctorQuery = "SELECT d.doctor_id, u.user_id, u.username, u.email, u.first_name, u.last_name, d.specialization_id
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                WHERE d.doctor_id = $doctorId";
$doctorResult = mysqli_query($conn, $doctorQuery);
$doctor = mysqli_fetch_assoc($doctorResult);

if (!$doctor) {
    header('Location: ?page=doctors&error=Doctor not found');
    exit();
}

// Fetch roles (excluding administrator if needed)
$rolesQuery = "SELECT role_id, role_name FROM roles WHERE role_name = 'doctor'";
$rolesResult = mysqli_query($conn, $rolesQuery);
$roles = mysqli_fetch_all($rolesResult, MYSQLI_ASSOC);

// Fetch specializations
$specializationsQuery = "SELECT specialization_id, specialization_name FROM specializations ORDER BY specialization_name";
$specializationsResult = mysqli_query($conn, $specializationsQuery);
$specializations = mysqli_fetch_all($specializationsResult, MYSQLI_ASSOC);

// Fetch current availability for the doctor
$availabilityQuery = "SELECT day_of_week, start_time, end_time FROM doctor_schedule WHERE doctor_id = $doctorId";
$availabilityResult = mysqli_query($conn, $availabilityQuery);
$currentAvailability = [];
while ($row = mysqli_fetch_assoc($availabilityResult)) {
    $currentAvailability[$row['day_of_week']][] = [
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
    ];
}

// Handle form submission for updating doctor details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_doctor'])) {
    $userId = intval($_POST['user_id']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $specializationId = isset($_POST['specialization_id']) ? intval($_POST['specialization_id']) : null;
    $availabilityTimes = $_POST['availability'] ?? [];

    // Validate inputs (add more robust validation as needed)
    if (empty($username) || empty($email) || empty($firstName) || empty($lastName) || !is_numeric($specializationId)) {
        $errorMessage = "All fields are required.";
    } else {
        // Update user information
        $updateUserQuery = "UPDATE users SET
                            username = '$username',
                            email = '$email',
                            first_name = '$firstName',
                            last_name = '$lastName'
                            WHERE user_id = $userId";

        if (mysqli_query($conn, $updateUserQuery)) {
            // Update doctor's specialization
            $updateDoctorQuery = "UPDATE doctors SET
                                specialization_id = $specializationId
                                WHERE doctor_id = $doctorId";

            if (mysqli_query($conn, $updateDoctorQuery)) {
                // Clear existing availability
                $deleteAvailabilityQuery = "DELETE FROM doctor_schedule WHERE doctor_id = $doctorId";
                mysqli_query($conn, $deleteAvailabilityQuery);

                // Insert new availability times
                foreach ($availabilityTimes as $day => $times) {
                    if (is_array($times)) {
                        foreach ($times as $timeSlot) {
                            if (isset($timeSlot['start_time']) && isset($timeSlot['end_time']) && !empty($timeSlot['start_time']) && !empty($timeSlot['end_time'])) {
                                $startTime = mysqli_real_escape_string($conn, $timeSlot['start_time']);
                                $endTime = mysqli_real_escape_string($conn, $timeSlot['end_time']);
                                $insertAvailabilityQuery = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time)
                                                            VALUES ($doctorId, '$day', '$startTime', '$endTime')";
                                mysqli_query($conn, $insertAvailabilityQuery);
                            }
                        }
                    }
                }

                $successMessage = "Doctor information updated successfully!";
                // Refresh doctor data after update
                $doctorResult = mysqli_query($conn, $doctorQuery);
                $doctor = mysqli_fetch_assoc($doctorResult);
                $availabilityResult = mysqli_query($conn, $availabilityQuery);
                $currentAvailability = [];
                while ($row = mysqli_fetch_assoc($availabilityResult)) {
                    $currentAvailability[$row['day_of_week']][] = [
                        'start_time' => $row['start_time'],
                        'end_time' => $row['end_time'],
                    ];
                }
            } else {
                $errorMessage = "Error updating doctor details: " . mysqli_error($conn);
            }
        } else {
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
    <title>Edit Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Edit Doctor</h1>

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
                <input type="hidden" name="update_doctor" value="1">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($doctor['user_id']); ?>">
                <div>
                    <label for="username"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Username</label>
                    <input type="text" name="username" id="username"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($doctor['username']); ?>" required>
                </div>
                <div>
                    <label for="email"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Email</label>
                    <input type="email" name="email" id="email"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($doctor['email']); ?>" required>
                </div>
                <div>
                    <label for="first_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">First
                        Name</label>
                    <input type="text" name="first_name" id="first_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($doctor['first_name']); ?>" required>
                </div>
                <div>
                    <label for="last_name" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Last
                        Name</label>
                    <input type="text" name="last_name" id="last_name"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        value="<?php echo htmlspecialchars($doctor['last_name']); ?>" required>
                </div>
                <div>
                    <label for="specialization_id"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Specialization</label>
                    <select name="specialization_id" id="specialization_id"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                        required>
                        <option value="">Select Specialization</option>
                        <?php foreach ($specializations as $specialization): ?>
                            <option value="<?php echo htmlspecialchars($specialization['specialization_id']); ?>" <?php if ($doctor['specialization_id'] == $specialization['specialization_id'])
                                   echo 'selected'; ?>>
                                <?php echo htmlspecialchars($specialization['specialization_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Availability</label>
                    <div id="availability-fields">
                        <?php
                        $daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                        foreach ($daysOfWeek as $day):
                            ?>
                            <div class="mb-4 border-b pb-2">
                                <label
                                    class="block text-sm font-medium text-gray-700 capitalize"><?php echo ucfirst($day); ?></label>
                                <?php if (isset($currentAvailability[$day])): ?>
                                    <?php foreach ($currentAvailability[$day] as $index => $slot): ?>
                                        <div class="flex space-x-4 mb-2">
                                            <select name="availability[<?php echo $day; ?>][<?php echo $index; ?>][start_time]"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <option value="">Start Time</option>
                                                <?php for ($i = 0; $i < 24; $i++): ?>
                                                    <?php for ($j = 0; $j < 60; $j += 30): ?>
                                                        <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                                        <option value="<?php echo $time; ?>" <?php if ($slot['start_time'] == $time)
                                                               echo 'selected'; ?>><?php echo date('h:i A', strtotime($time)); ?></option>
                                                    <?php endfor; ?>
                                                <?php endfor; ?>
                                            </select>
                                            <select name="availability[<?php echo $day; ?>][<?php echo $index; ?>][end_time]"
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                                <option value="">End Time</option>
                                                <?php for ($i = 0; $i < 24; $i++): ?>
                                                    <?php for ($j = 0; $j < 60; $j += 30): ?>
                                                        <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                                        <option value="<?php echo $time; ?>" <?php if ($slot['end_time'] == $time)
                                                               echo 'selected'; ?>><?php echo date('h:i A', strtotime($time)); ?></option>
                                                    <?php endfor; ?>
                                                <?php endfor; ?>
                                            </select>
                                            <button type="button" class="text-red-500 hover:text-red-700 remove-time-slot">
                                                <span class="material-symbols-outlined">delete</span>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="flex space-x-4 mb-2">
                                        <select name="availability[<?php echo $day; ?>][0][start_time]"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">Start Time</option>
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <?php for ($j = 0; $j < 60; $j += 30): ?>
                                                    <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                                    <option value="<?php echo $time; ?>"><?php echo date('h:i A', strtotime($time)); ?>
                                                    </option>
                                                <?php endfor; ?>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="availability[<?php echo $day; ?>][0][end_time]"
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                            <option value="">End Time</option>
                                            <?php for ($i = 0; $i < 24; $i++): ?>
                                                <?php for ($j = 0; $j < 60; $j += 30): ?>
                                                    <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                                    <option value="<?php echo $time; ?>"><?php echo date('h:i A', strtotime($time)); ?>
                                                    </option>
                                                <?php endfor; ?>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="button" class="text-green-500 hover:text-green-700 add-time-slot"
                                            data-day="<?php echo $day; ?>">
                                            <span class="material-symbols-outlined">add</span>
                                        </button>
                                    </div>
                                <?php endif; ?>
                                <div id="additional-slots-<?php echo $day; ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit"
                    class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                    Update Doctor
                </button>
            </form>
            <div class="mt-4">
                <a href="?page=doctors" class="text-gray-600 hover:text-gray-800">Back to Doctors</a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const availabilityFields = document.getElementById('availability-fields');
            const daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

            availabilityFields.addEventListener('click', function (event) {
                if (event.target.classList.contains('add-time-slot')) {
                    const day = event.target.getAttribute('data-day');
                    const container = document.getElementById(`additional-slots-${day}`);
                    const index = container.children.length;

                    const newSlot = document.createElement('div');
                    newSlot.classList.add('flex', 'space-x-4', 'mb-2');
                    newSlot.innerHTML = `
                        <select name="availability[${day}][${index}][start_time]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">Start Time</option>
                            <?php for ($i = 0; $i < 24; $i++): ?>
                                <?php for ($j = 0; $j < 60; $j += 30): ?>
                                    <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                    <option value="<?php echo $time; ?>"><?php echo date('h:i A', strtotime($time)); ?></option>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </select>
                        <select name="availability[${day}][${index}][end_time]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">End Time</option>
                            <?php for ($i = 0; $i < 24; $i++): ?>
                                <?php for ($j = 0; $j < 60; $j += 30): ?>
                                    <?php $time = sprintf('%02d:%02d', $i, $j); ?>
                                    <option value="<?php echo $time; ?>"><?php echo date('h:i A', strtotime($time)); ?></option>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </select>
                        <button type="button" class="text-red-500 hover:text-red-700 remove-time-slot">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    `;
                    container.appendChild(newSlot);
                } else if (event.target.classList.contains('remove-time-slot')) {
                    event.target.closest('.flex').remove();
                }
            });
        });
    </script>
</body>

</html>