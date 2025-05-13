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

// Fetch all patients with their user information
$patientsQuery = "SELECT
                    p.patient_id,
                    u.first_name,
                    u.last_name,
                    p.date_of_birth,
                    p.gender,
                    u.phone_number AS contact_number,
                    u.email,
                    COALESCE(pd.address, '') AS address -- Assuming address might be in patient_descriptions
                FROM patients p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN patient_descriptions pd ON p.patient_id = pd.patient_id
                ORDER BY u.last_name, u.first_name";
$patientsResult = mysqli_query($conn, $patientsQuery);
$patients = mysqli_fetch_all($patientsResult, MYSQLI_ASSOC);

// Handle form submission for adding a new patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
    $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
    $username = mysqli_real_escape_string($conn, $_POST['username']); // Added username
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password']; // You'll need to handle password hashing
    $dob = mysqli_real_escape_string($conn, $_POST['date_of_birth']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $contactNumber = mysqli_real_escape_string($conn, $_POST['contact_number']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $roleId = 4; // Assuming '4' is the role_id for 'patient'

    // Validate inputs (add more robust validation as needed)
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password) || empty($dob) || empty($gender) || empty($contactNumber) || empty($address)) {
        $errorMessage = "All fields are required.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the users table first
        $insertUserQuery = "INSERT INTO users (username, email, password_hash, first_name, last_name, phone_number, role_id)
                            VALUES ('$username', '$email', '$passwordHash', '$firstName', '$lastName', '$contactNumber', $roleId)";

        if (mysqli_query($conn, $insertUserQuery)) {
            $newUserId = mysqli_insert_id($conn);

            // Insert patient into the patients table
            $insertPatientQuery = "INSERT INTO patients (user_id, date_of_birth, gender)
                                   VALUES ($newUserId, '$dob', '$gender')";

            if (mysqli_query($conn, $insertPatientQuery)) {
                $newPatientId = mysqli_insert_id($conn);

                // Insert patient description (address)
                $insertDescriptionQuery = "INSERT INTO patient_descriptions (patient_id, address)
                                           VALUES ($newPatientId, '$address')";
                mysqli_query($conn, $insertDescriptionQuery);

                $successMessage = "Patient added successfully!";
                // Refresh patients list
                $patientsResult = mysqli_query($conn, $patientsQuery);
                $patients = mysqli_fetch_all($patientsResult, MYSQLI_ASSOC);
            } else {
                $errorMessage = "Error adding patient details: " . mysqli_error($conn);
                // Optionally delete the user if patient insertion fails
                mysqli_query($conn, "DELETE FROM users WHERE user_id = $newUserId");
            }
        } else {
            $errorMessage = "Error adding user: " . mysqli_error($conn);
        }
    }
}

// Handle deletion of a patient
if (isset($_GET['delete_id']) && is_numeric($_GET['delete_id'])) {
    $deletePatientId = intval($_GET['delete_id']);

    // Begin transaction to ensure data integrity (consider related tables like appointments)
    mysqli_begin_transaction($conn);

    // Get the user_id associated with the patient
    $getUserQuery = "SELECT user_id FROM patients WHERE patient_id = $deletePatientId";
    $userResult = mysqli_query($conn, $getUserQuery);
    if ($userResult && $user = mysqli_fetch_assoc($userResult)) {
        $userIdToDelete = $user['user_id'];

        // Delete from appointments table (if applicable)
        $deleteAppointmentsQuery = "DELETE FROM appointments WHERE patient_id = $deletePatientId";
        mysqli_query($conn, $deleteAppointmentsQuery);

        // Delete from patient_descriptions table
        $deleteDescriptionQuery = "DELETE FROM patient_descriptions WHERE patient_id = $deletePatientId";
        mysqli_query($conn, $deleteDescriptionQuery);

        // Delete from patients table
        $deletePatientQuery = "DELETE FROM patients WHERE patient_id = $deletePatientId";
        mysqli_query($conn, $deletePatientQuery);

        // Delete from users table
        $deleteUserQuery = "DELETE FROM users WHERE user_id = $userIdToDelete";
        if (mysqli_query($conn, $deleteUserQuery)) {
            mysqli_commit($conn);
            $successMessage = "Patient deleted successfully!";
            // Refresh patients list
            $patientsResult = mysqli_query($conn, $patientsQuery);
            $patients = mysqli_fetch_all($patientsResult, MYSQLI_ASSOC);
        } else {
            mysqli_rollback($conn);
            $errorMessage = "Error deleting user: " . mysqli_error($conn);
        }
    } else {
        $errorMessage = "Patient not found.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>

<body class="bg-gray-100">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-semibold mb-6 text-gray-800">Manage Patients</h1>

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
            <button data-modal-target="add-patient-modal" data-modal-toggle="add-patient-modal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Add New Patient
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
                            Date of Birth
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Gender
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Contact
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Email
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Address
                        </th>
                        <th
                            class="px-5 py-3 border-b-2 border-gray-200 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['patient_id']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['date_of_birth']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars(ucfirst($patient['gender'])); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['contact_number']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['email']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <?php echo htmlspecialchars($patient['address']); ?></td>
                            <td class="px-5 py-3 border-b border-gray-200 text-sm">
                                <a href="?page=edit_patient&id=<?php echo $patient['patient_id']; ?>"
                                    class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                <a href="?page=patients&delete_id=<?php echo $patient['patient_id']; ?>"
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Are you sure you want to delete this patient?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-patient-modal" tabindex="-1" aria-hidden="true"
        class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative w-full max-w-md max-h-full">
            <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Add New Patient
                    </h3>
                    <button type="button"
                        class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                        data-modal-hide="add-patient-modal">
                        <span class="material-symbols-outlined">close</span>
                        <span class="sr-only">Close modal</span>
                    </button>
                </div>
                <div class="p-4 md:p-5">
                    <form class="space-y-4" method="post" action="">
                        <input type="hidden" name="add_patient" value="1">
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
                            <input type="text" name="last_name" id="last__name"
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
                            <label for="date_of_birth"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Date of
                                Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="gender"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Gender</label>
                            <select name="gender" id="gender"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="contact_number"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Contact
                                Number</label>
                            <input type="tel" name="contact_number" id="contact_number"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required>
                        </div>
                        <div>
                            <label for="address"
                                class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Address</label>
                            <textarea name="address" id="address" rows="3"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500"
                                required></textarea>
                        </div>
                        <button type="submit"
                            class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">
                            Add Patient
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>

</html>